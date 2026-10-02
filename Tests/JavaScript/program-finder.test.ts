import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { resetBody, settle } from "../../../../../Build/tests/dom.mjs";

/**
 * The program finder narrowed in the browser, driven against the markup of
 * "Resources/Private/Templates/Program/Finder.html", reduced to what the module
 * reads: the data attributes of the form, the category selects, the count
 * inside the submit button and the status element. "f:translate" becomes the
 * text it resolves to, and "ct:form.filterSelect" the options it renders.
 *
 * The data is the one of the functional fixture
 * "Tests/Functional/Plugins/Fixtures/AcademicProgramsFinder/records.csv", and
 * "AcademicProgramsFinderTest::theFormCarriesTheProgramsOfItsStorage()" asserts
 * the same attributes and the same programs on the rendered page. Applied
 * Physics carries the Bachelor of Science (1) and Engineering (4), Molecular
 * Chemistry the Master of Science (2) and Life sciences (5), Mechanical
 * Engineering the Master of Science and Engineering. No program in storage
 * carries the Diploma (3) or the Doctorate (8), so the server renders both
 * disabled.
 */
const SPECIFIER = "@fgtclb/academic-programs/frontend/program-finder.js";

const PROGRAMS = [[1, 4], [2, 5], [2, 4]];

interface FinderFixture {
  programs?: number[][];
  degree?: string;
  topic?: string;
  extraSelect?: string;
}

const option = (value: string, label: string, state: { selected?: boolean; disabled?: boolean } = {}): string =>
  `<option value="${value}"${state.selected ? ' selected="selected"' : ""}${state.disabled ? ' disabled="disabled"' : ""}>${label}</option>`;

const finderMarkup = (fixture: FinderFixture = {}): string =>
  '<form class="academic-programs-finder" action="/programs" method="post"' +
  ` data-academic-programs-finder-programs='${JSON.stringify(fixture.programs ?? PROGRAMS)}'` +
  ' data-academic-programs-finder-count-one="Show %d program"' +
  ' data-academic-programs-finder-count-other="Show %d programs">' +
  '<select name="tx_academicprograms_programlist[demand][filterCollection][degree]"' +
  ' data-academic-programs-finder-select="" class="form-select">' +
  option("", "All options") +
  option("1", "Bachelor of Science", { selected: fixture.degree === "1" }) +
  option("2", "Master of Science", { selected: fixture.degree === "2" }) +
  option("3", "Diploma", { disabled: true }) +
  option("8", "Doctorate", { disabled: true }) +
  "</select>" +
  '<select name="tx_academicprograms_programlist[demand][filterCollection][topic]"' +
  ' data-academic-programs-finder-select="" class="form-select">' +
  option("", "All options") +
  option("4", "Engineering", { selected: fixture.topic === "4" }) +
  option("5", "Life sciences", { selected: fixture.topic === "5" }) +
  "</select>" +
  (fixture.extraSelect ?? "") +
  '<button type="submit" class="btn btn-primary">' +
  "<span data-academic-programs-finder-count>Show programs</span>" +
  "</button>" +
  '<p class="visually-hidden" role="status" aria-live="polite" data-academic-programs-finder-status></p>' +
  "</form>";

/**
 * Puts the markup in the document and starts the module on it. The import
 * starts the module the first time, as "f:asset.module" does after the
 * document was parsed. Node hands every later test the same module instance,
 * so the exported initialiser starts it on the new markup.
 */
const startOn = async (markup: string): Promise<void> => {
  resetBody(markup);
  const { init } = await import(SPECIFIER);
  init();
  await settle();
};

const start = async (fixture: FinderFixture = {}): Promise<void> => startOn(finderMarkup(fixture));

const select = (name: string): HTMLSelectElement => {
  const element = document.querySelector<HTMLSelectElement>(`select[name$="[${name}]"]`);
  assert.ok(element, `The finder has no select "${name}".`);
  return element;
};

/** The options of a select as "label" or "label (disabled)", the "All" option left out. */
const options = (name: string): string[] =>
  Array.from(select(name).options)
    .filter((element): boolean => element.value !== "")
    .map((element): string => element.textContent + (element.disabled ? " (disabled)" : ""));

const choose = async (name: string, value: string): Promise<void> => {
  const element = select(name);
  element.value = value;
  element.dispatchEvent(new Event("change", { bubbles: true }));
  await settle();
};

const countText = (): string => document.querySelector("[data-academic-programs-finder-count]")?.textContent ?? "";

const statusElement = (): Element => {
  const element = document.querySelector("[data-academic-programs-finder-status]");
  assert.ok(element, "The finder has no status element.");
  return element;
};

const ALL_DEGREES = ["Bachelor of Science", "Master of Science", "Diploma (disabled)", "Doctorate (disabled)"];

describe("the program finder", () => {
  it("disables a topic no program of the selected degree carries, and restores it", async () => {
    await start();

    assert.deepEqual(options("topic"), ["Engineering", "Life sciences"]);
    assert.equal(countText(), "Show 3 programs");

    await choose("degree", "1");
    assert.deepEqual(options("topic"), ["Engineering", "Life sciences (disabled)"]);
    assert.equal(countText(), "Show 1 program");

    await choose("degree", "");
    assert.deepEqual(options("topic"), ["Engineering", "Life sciences"]);
    assert.equal(countText(), "Show 3 programs");
  });

  it("narrows the degree by the topic, and counts the programs both selections find", async () => {
    await start();

    await choose("topic", "5");
    assert.deepEqual(options("degree"), [
      "Bachelor of Science (disabled)",
      "Master of Science",
      "Diploma (disabled)",
      "Doctorate (disabled)",
    ]);
    assert.equal(countText(), "Show 1 program");

    await choose("topic", "4");
    assert.deepEqual(options("degree"), ALL_DEGREES);
    assert.equal(countText(), "Show 2 programs");
  });

  it("never enables an option the server rendered disabled", async () => {
    // A listener may hand the finder programs that carry a category its categories leave
    // disabled. The server decides which options are offered, the module only narrows them.
    await start({ programs: [...PROGRAMS, [3, 4]] });

    assert.deepEqual(options("degree"), ALL_DEGREES);
    await choose("topic", "4");
    assert.deepEqual(options("degree"), ALL_DEGREES);
  });

  it("narrows by a preselected category as soon as it starts", async () => {
    await start({ degree: "1" });

    assert.deepEqual(options("topic"), ["Engineering", "Life sciences (disabled)"]);
    assert.equal(countText(), "Show 1 program");
  });

  it("keeps a preselected category selectable that another preselection excludes", async () => {
    await start({ degree: "1", topic: "5" });

    assert.deepEqual(options("degree"), ALL_DEGREES);
    assert.deepEqual(options("topic"), ["Engineering", "Life sciences"]);
    assert.equal(countText(), "Show 0 programs");
  });

  it("leaves a select without the attribute alone, and does not match its value", async () => {
    await start({
      extraSelect:
        '<select name="tx_academicprograms_programlist[demand][sorting]">' +
        '<option value="title asc" selected="selected">Title</option>' +
        '<option value="title desc">Title, descending</option>' +
        "</select>",
    });

    assert.deepEqual(options("sorting"), ["Title", "Title, descending"]);
    assert.deepEqual(options("topic"), ["Engineering", "Life sciences"]);
    assert.equal(countText(), "Show 3 programs");

    await choose("sorting", "title desc");
    assert.equal(statusElement().textContent, "");
  });

  it("announces a changed count in the status element, and nothing on load", async () => {
    await start();

    assert.equal(statusElement().textContent, "");

    await choose("degree", "2");
    assert.equal(statusElement().textContent, "Show 2 programs");

    await choose("topic", "5");
    assert.equal(statusElement().textContent, "Show 1 program");
  });

  it("announces nothing new when a change keeps the count", async () => {
    await start();

    await choose("degree", "1");
    assert.equal(statusElement().textContent, "Show 1 program");
    statusElement().textContent = "";

    await choose("topic", "4");
    assert.equal(countText(), "Show 1 program");
    assert.equal(statusElement().textContent, "");
  });

  it("narrows without counting when a count pattern is missing", async () => {
    await startOn(finderMarkup().replace(' data-academic-programs-finder-count-one="Show %d program"', ""));

    await choose("degree", "1");
    assert.deepEqual(options("topic"), ["Engineering", "Life sciences (disabled)"]);
    assert.equal(countText(), "Show programs");
    assert.equal(statusElement().textContent, "");
  });

  it("leaves a finder alone whose programs it cannot read", async () => {
    await startOn(finderMarkup().replace(`'${JSON.stringify(PROGRAMS)}'`, "'not json'"));

    await choose("degree", "1");
    assert.deepEqual(options("topic"), ["Engineering", "Life sciences"]);
    assert.equal(countText(), "Show programs");
    assert.equal(statusElement().textContent, "");
  });
});
