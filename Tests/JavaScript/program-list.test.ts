import assert from "node:assert/strict";
import { afterEach, beforeEach, describe, it } from "node:test";
import { recordedNavigations, resetBody, settle } from "../../../../../Build/tests/dom.mjs";
import { installFetch } from "../../../../../Build/tests/fetch.mjs";
import type { FetchDouble } from "../../../../../Build/tests/fetch.mjs";

/**
 * The program list updated in place, driven against the markup of
 * "Resources/Private/Templates/Program/List.html" and its partials
 * "SortingAndFilters", "DemandSorting", "DemandCategories" and "ItemList",
 * reduced to what the module reads: the wrapper with the uid of the content
 * element and the two count sentences, the content region with its count, the
 * form, its submit button, the "More filters" disclosure, the results and the
 * status element. "f:translate" becomes the text it resolves to.
 *
 * "AcademicProgramsListInPlaceTest" asserts the same attributes on the rendered
 * list. The programs are the ones of its fixture: Applied Physics is a
 * full-time bachelor (1, 3), Molecular Chemistry a master (2), Regional
 * Teaching has no category.
 *
 * A change posts the form, and the page a test hands back stands for the page
 * the redirect of the list ended at, with its url.
 */
const SPECIFIER = "@fgtclb/academic-programs/frontend/program-list.js";
const NAMESPACE = "tx_academicprograms_programlist";
const PAGE_URL = "https://example.test/programs";
const ACTION = `${PAGE_URL}?${NAMESPACE}%5Baction%5D=list&${NAMESPACE}%5Bcontroller%5D=Program&cHash=a1`;

const filterUrl = (degree: string, sorting = "title"): string =>
  `${PAGE_URL}?${NAMESPACE}%5Bdemand%5D%5BfilterCollection%5D%5Bcategories%5D=${degree}` +
  `&${NAMESPACE}%5Bdemand%5D%5BsortingDirection%5D=asc&${NAMESPACE}%5Bdemand%5D%5BsortingField%5D=${sorting}`;

const PROGRAMS: Record<string, string[]> = {
  "": ["Applied Physics", "Molecular Chemistry", "Regional Teaching"],
  "1": ["Applied Physics"],
  "2": ["Molecular Chemistry"],
};

interface ListFixture {
  uid?: string;
  degree?: string;
  programType?: string;
  /** The filter partials of an override from before, both with their inline handlers. */
  inlineHandlers?: boolean;
  /** An override of the sorting partial alone, with its inline handlers. */
  inlineSortingOnly?: boolean;
  /** A list that hides its filter and its sorting, and renders no form. */
  withoutForm?: boolean;
  /** The list template of an override from before: no content region. */
  withoutContentRegion?: boolean;
  /** The form partial of an override from before: no attribute on the form, no button. */
  formPartialFromBefore?: boolean;
}

const option = (value: string, label: string, selected: boolean): string =>
  `<option value="${value}"${selected ? ' selected="selected"' : ""}>${label}</option>`;

/** A select of the filter partials: marked, or with the inline handler of an override from before. */
const select = (property: string, id: string, options: string, inlineHandlers: boolean): string =>
  `<select${inlineHandlers ? ' onchange="this.form.submit()"' : " data-academic-programs-list-select"} id="${id}"` +
  ` class="form-select" name="${NAMESPACE}[demand][${property}]">${options}</select>`;

const formMarkup = (fixture: ListFixture): string => {
  const degree = fixture.degree ?? "";
  const programType = fixture.programType ?? "";
  const inline = fixture.inlineHandlers ?? false;
  const inlineSorting = inline || (fixture.inlineSortingOnly ?? false);
  const before = fixture.formPartialFromBefore ?? false;

  return (
    `<form action="${ACTION}" method="post" name="demand" class="academic-programs-filtersorting"` +
    `${before ? "" : " data-academic-programs-list-form"}>` +
    `<input type="hidden" name="${NAMESPACE}[__referrer][@extension]" value="AcademicPrograms" />` +
    '<div class="row">' +
    '<div class="col-12 col-md-6 col-lg-4 col-xl-3">' +
    '<label for="sortingField" class="form-label">Sort by</label>' +
    select(
      "sortingField",
      "sortingField",
      option("title", "Title", true) + option("credit_points", "Credit points", false),
      inlineSorting,
    ) +
    "</div>" +
    '<div class="col-12 col-md-6 col-lg-4 col-xl-3">' +
    '<label for="degree" class="form-label">Degree</label>' +
    select(
      "filterCollection][degree",
      "degree",
      option("", "All options", degree === "") +
        option("1", "Bachelor of Science", degree === "1") +
        option("2", "Master of Science", degree === "2"),
      inline,
    ) +
    "</div>" +
    `<details class="col-12 academic-programs-more-filters"${programType !== "" ? " open" : ""}>` +
    "<summary>More filters</summary>" +
    '<div class="row"><div class="col-12 col-md-6 col-lg-4 col-xl-3">' +
    '<label for="program_type" class="form-label">Type of program</label>' +
    select(
      "filterCollection][program_type",
      "program_type",
      option("", "All options", programType === "") + option("3", "Full-time", programType === "3"),
      inline,
    ) +
    "</div></div>" +
    "</details>" +
    (before
      ? ""
      : '<div class="col-12 col-md-6 col-lg-4 col-xl-3 align-self-end" data-academic-programs-list-submit>' +
        '<button type="submit" class="btn btn-primary">Show programs</button>' +
        "</div>") +
    "</div>" +
    "</form>"
  );
};

const itemsMarkup = (degree: string): string =>
  '<div class="row academic-programs-itemlist">' +
  (PROGRAMS[degree] ?? [])
    .map((title): string => `<div class="col-12 col-md-6 col-lg-4 col-xl-3"><h3 class="program">${title}</h3></div>`)
    .join("") +
  "</div>";

const listMarkup = (fixture: ListFixture = {}): string => {
  const degree = fixture.degree ?? "";
  const count = (PROGRAMS[degree] ?? []).length;
  const content = (fixture.withoutForm === true ? "" : formMarkup(fixture)) + itemsMarkup(degree);

  return (
    `<div class="academic-programs-list" data-academic-programs-list="${fixture.uid ?? "10"}"` +
    ' data-academic-programs-list-count-one="%d program found"' +
    ' data-academic-programs-list-count-other="%d programs found">' +
    (fixture.withoutContentRegion === true
      ? content
      : `<div data-academic-programs-list-content data-academic-programs-list-total="${count}">${content}</div>`) +
    '<p class="visually-hidden" role="status" aria-live="polite" data-academic-programs-list-status></p>' +
    "</div>"
  );
};

const pageMarkup = (...lists: string[]): string =>
  `<!doctype html><html lang="en"><body><main>${lists.join("")}</main></body></html>`;

let requests: FetchDouble | null = null;

const fetchDouble = (): FetchDouble => {
  assert.ok(requests !== null);

  return requests;
};

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

const list = (uid = "10"): HTMLElement => {
  const element = document.querySelector<HTMLElement>(`[data-academic-programs-list="${uid}"]`);
  assert.ok(element, `The page has no list ${uid}.`);

  return element;
};

const field = (name: string, uid = "10"): HTMLSelectElement => {
  const element = list(uid).querySelector<HTMLSelectElement>(`select[name$="[${name}]"]`);
  assert.ok(element, `List ${uid} has no select "${name}".`);

  return element;
};

const programs = (uid = "10"): string[] =>
  Array.from(list(uid).querySelectorAll(".program")).map((element): string => element.textContent ?? "");

const status = (uid = "10"): string => list(uid).querySelector("[data-academic-programs-list-status]")?.textContent ?? "";

/** The column around the submit button, which carries the marker. */
const submitButton = (uid = "10"): HTMLElement => {
  const button = list(uid).querySelector<HTMLElement>("[data-academic-programs-list-submit]");
  assert.ok(button);

  return button;
};

const timesSubmitted = (uid = "10"): string | null =>
  list(uid).querySelector("form")?.getAttribute("data-test-submitted") ?? null;

/** Selects a value the way a visitor does, which fires a "change" event that bubbles. */
const choose = async (name: string, value: string, uid = "10"): Promise<void> => {
  const element = field(name, uid);
  element.focus();
  element.value = value;
  element.dispatchEvent(new Event("change", { bubbles: true }));
  await settle(10);
};

/**
 * Goes back one history entry and waits for the list to be requested and
 * replaced. Fails rather than waiting forever when there is no entry to go
 * back to, which is what a module that never added one leaves.
 */
const goBack = async (): Promise<void> => {
  let timer: ReturnType<typeof setTimeout> | undefined;
  const popped = new Promise((resolve): void => {
    window.addEventListener("popstate", resolve, { once: true });
  });
  const timedOut = new Promise((_resolve, reject): void => {
    timer = setTimeout((): void => reject(new Error("No popstate event within a second.")), 1000);
  });
  window.history.back();
  try {
    await Promise.race([popped, timedOut]);
  } finally {
    clearTimeout(timer);
  }
  await settle(10);
};

beforeEach(() => {
  // One window per test file: every test starts on the page of the list.
  window.history.replaceState(null, "", PAGE_URL);
  requests = installFetch();
});

afterEach(() => {
  requests?.restore();
  requests = null;
});

describe("the page as it was loaded", () => {
  // The first test of the file on purpose: the module marks the history entry of the page
  // once, when it starts for the first time, and node keeps one module instance per file.
  it("is restored when the visitor goes back to it", async () => {
    await startOn(listMarkup());
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));
    await choose("degree", "2");
    fetchDouble().respondWithPage(pageMarkup(listMarkup()), PAGE_URL);

    await goBack();

    assert.equal(window.location.href, PAGE_URL);
    assert.equal(fetchDouble().lastCall()?.url, PAGE_URL);
    assert.deepEqual(programs(), ["Applied Physics", "Molecular Chemistry", "Regional Teaching"]);
  });
});

describe("the program list updated in place", () => {
  it("hides the submit button, which is there for a visitor without JavaScript", async () => {
    await startOn(listMarkup());

    assert.equal(submitButton().hidden, true);
    assert.equal(fetchDouble().calls.length, 0);
  });

  it("posts the form to its action when a filter changes", async () => {
    await startOn(listMarkup());
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));

    await choose("degree", "2");

    const call = fetchDouble().lastCall();
    assert.equal(fetchDouble().calls.length, 1);
    assert.equal(call?.method, "POST");
    assert.equal(call?.url, ACTION);
    assert.ok(call?.body instanceof FormData);
    assert.equal(call.body.get(`${NAMESPACE}[demand][filterCollection][degree]`), "2");
    assert.equal(call.body.get(`${NAMESPACE}[demand][sortingField]`), "title");
    assert.equal(call.body.get(`${NAMESPACE}[__referrer][@extension]`), "AcademicPrograms");
  });

  it("shows the form and the results of the page the redirect ended at", async () => {
    await startOn(listMarkup());
    assert.deepEqual(programs(), ["Applied Physics", "Molecular Chemistry", "Regional Teaching"]);
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));

    await choose("degree", "2");

    assert.deepEqual(programs(), ["Molecular Chemistry"]);
    assert.equal(field("degree").value, "2");
    assert.equal(list().querySelector("[data-academic-programs-list-content]")?.getAttribute("data-academic-programs-list-total"), "1");
    // The button of the new form is hidden as well.
    assert.equal(submitButton().hidden, true);
    assert.equal(timesSubmitted(), null);
  });

  it("updates every list of the page, as a reload of the filter URL does", async () => {
    // Both lists share the plugin namespace, so the filter URL filters both of them.
    await startOn(listMarkup({ uid: "10" }) + listMarkup({ uid: "11" }));
    fetchDouble().respondWithPage(
      pageMarkup(listMarkup({ uid: "10", degree: "2" }), listMarkup({ uid: "11", degree: "2" })),
      filterUrl("2"),
    );

    await choose("degree", "2", "10");

    assert.deepEqual(programs("10"), ["Molecular Chemistry"]);
    assert.deepEqual(programs("11"), ["Molecular Chemistry"]);
    assert.equal(field("degree", "11").value, "2");
    // Only the list the visitor changed says so.
    assert.equal(status("10"), "1 program found");
    assert.equal(status("11"), "");
  });

  it("updates a list without a form as well, which the filter URL filters too", async () => {
    await startOn(listMarkup({ uid: "10" }) + listMarkup({ uid: "11", withoutForm: true }));
    fetchDouble().respondWithPage(
      pageMarkup(listMarkup({ uid: "10", degree: "2" }), listMarkup({ uid: "11", degree: "2", withoutForm: true })),
      filterUrl("2"),
    );

    await choose("degree", "2", "10");

    assert.deepEqual(programs("11"), ["Molecular Chemistry"]);
    assert.equal(status("11"), "");
  });

  it("submits the form the normal way when the page lacks the changed list, even if it carries another", async () => {
    await startOn(listMarkup({ uid: "10" }) + listMarkup({ uid: "11" }));
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ uid: "11", degree: "2" })), filterUrl("2"));

    await choose("degree", "2", "10");

    assert.equal(timesSubmitted("10"), "1");
    assert.equal(window.location.href, PAGE_URL);
  });

  it("gives the focus back to the select that had it", async () => {
    await startOn(listMarkup());
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));

    await choose("degree", "2");

    assert.equal(document.activeElement, field("degree"));
  });

  it("keeps the more filters open that the visitor opened", async () => {
    await startOn(listMarkup());
    const details = list().querySelector("details");
    assert.ok(details);
    details.open = true;
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));

    await choose("degree", "2");

    assert.equal(list().querySelector("details")?.open, true);
  });

  it("leaves the more filters closed that the visitor did not open", async () => {
    await startOn(listMarkup());
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));

    await choose("degree", "2");

    assert.equal(list().querySelector("details")?.open, false);
  });

  it("shows the filter URL in the address bar and adds one history entry", async () => {
    await startOn(listMarkup());
    const entries = window.history.length;
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));

    await choose("degree", "2");

    assert.equal(window.location.href, filterUrl("2"));
    assert.equal(window.history.length, entries + 1);
    assert.deepEqual(window.history.state, { academicProgramsList: true });
  });

  it("adds no history entry when the selection leads to the URL already shown", async () => {
    await startOn(listMarkup());
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));
    await choose("degree", "2");
    const entries = window.history.length;
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));

    await choose("degree", "2");

    assert.equal(window.history.length, entries);
    assert.equal(fetchDouble().calls.length, 2);
  });

  it("announces the number of programs found after an update, and nothing on load", async () => {
    await startOn(listMarkup());
    assert.equal(status(), "");

    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));
    await choose("degree", "2");
    assert.equal(status(), "1 program found");

    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "" })), filterUrl(""));
    await choose("degree", "");
    assert.equal(status(), "3 programs found");
  });

  it("updates the list when the sorting changes", async () => {
    await startOn(listMarkup());
    fetchDouble().respondWithPage(pageMarkup(listMarkup()), filterUrl("", "credit_points"));

    await choose("sortingField", "credit_points");

    assert.equal(fetchDouble().calls.length, 1);
    assert.equal(window.location.href, filterUrl("", "credit_points"));
  });

  it("updates the list instead of submitting it when the form is submitted", async () => {
    await startOn(listMarkup());
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));
    const form = list().querySelector("form");
    assert.ok(form);
    field("degree").value = "2";

    let submission: Event | null = null;
    const record = (event: Event): void => {
      submission = event;
    };
    document.addEventListener("submit", record);
    form.requestSubmit();
    document.removeEventListener("submit", record);
    await settle(10);

    // The browser's own submission is what the update replaces.
    assert.equal((submission as Event | null)?.defaultPrevented, true);
    assert.equal(fetchDouble().calls.length, 1);
    assert.deepEqual(programs(), ["Molecular Chemistry"]);
    assert.deepEqual(recordedNavigations(), []);
  });

  it("starts once per list, however often it is started", async () => {
    await startOn(listMarkup());
    const { init } = await import(SPECIFIER);
    init();
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));

    await choose("degree", "2");

    assert.equal(fetchDouble().calls.length, 1);
  });
});

describe("going back and forward", () => {
  it("shows the previous selection again, from the URL of that history entry", async () => {
    await startOn(listMarkup());
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "1" })), filterUrl("1"));
    await choose("degree", "1");
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));
    await choose("degree", "2");
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "1" })), filterUrl("1"));

    await goBack();

    assert.equal(window.location.href, filterUrl("1"));
    const call = fetchDouble().lastCall();
    assert.equal(call?.method, "GET");
    assert.equal(call?.url, filterUrl("1"));
    assert.deepEqual(programs(), ["Applied Physics"]);
    assert.equal(field("degree").value, "1");
    assert.equal(status(), "1 program found");
  });

  it("announces the number once when going back on a page with two lists", async () => {
    await startOn(listMarkup({ uid: "10" }) + listMarkup({ uid: "11" }));
    fetchDouble().respondWithPage(
      pageMarkup(listMarkup({ uid: "10", degree: "1" }), listMarkup({ uid: "11", degree: "1" })),
      filterUrl("1"),
    );
    await choose("degree", "1", "10");
    fetchDouble().respondWithPage(
      pageMarkup(listMarkup({ uid: "10", degree: "2" }), listMarkup({ uid: "11", degree: "2" })),
      filterUrl("2"),
    );
    await choose("degree", "2", "10");
    fetchDouble().respondWithPage(
      pageMarkup(listMarkup({ uid: "10", degree: "1" }), listMarkup({ uid: "11", degree: "1" })),
      filterUrl("1"),
    );

    await goBack();

    assert.deepEqual(programs("11"), ["Applied Physics"]);
    assert.equal(status("10"), "1 program found");
    assert.equal(status("11"), "");
  });

  it("reloads the page when the previous selection cannot be requested", async () => {
    await startOn(listMarkup());
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "1" })), filterUrl("1"));
    await choose("degree", "1");
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));
    await choose("degree", "2");
    fetchDouble().respondWithPage("<p>Server error</p>", filterUrl("1"), 500);

    await goBack();

    assert.deepEqual(recordedNavigations(), [filterUrl("1")]);
    assert.deepEqual(programs(), ["Molecular Chemistry"]);
  });

  it("reloads the page when the previous page lacks the list, even if a removed list is in it", async () => {
    // A list that was on the page before and is no more, removed by another script.
    await startOn(listMarkup({ uid: "11" }));
    await startOn(listMarkup({ uid: "12" }));
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ uid: "12", degree: "1" })), filterUrl("1"));
    await choose("degree", "1", "12");
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ uid: "12", degree: "2" })), filterUrl("2"));
    await choose("degree", "2", "12");
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ uid: "11", degree: "1" })), filterUrl("1"));

    await goBack();

    assert.deepEqual(recordedNavigations(), [filterUrl("1")]);
  });

  it("requests nothing when going back over a fragment of the same page", async () => {
    await startOn(listMarkup());
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));
    await choose("degree", "2");
    // A fragment navigation fires its own events a task later. Going back starts after them.
    const navigated = new Promise((resolve): void => {
      window.addEventListener("hashchange", resolve, { once: true });
    });
    window.location.hash = "#results";
    await navigated;

    await goBack();

    assert.equal(window.location.href, filterUrl("2"));
    assert.equal(fetchDouble().calls.length, 1);
    assert.deepEqual(recordedNavigations(), []);
  });

  it("ignores a history entry it did not add", async () => {
    await startOn(listMarkup());
    window.history.pushState(null, "", `${PAGE_URL}?other=1`);
    window.history.pushState(null, "", `${PAGE_URL}?other=2`);

    await goBack();

    assert.equal(fetchDouble().calls.length, 0);
  });
});

describe("when the list cannot be updated in place", () => {
  it("drops the answer to a change when the next change starts before it arrives", async () => {
    await startOn(listMarkup());
    const first = fetchDouble().respondLater();
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"));

    await choose("degree", "1");
    await choose("degree", "2");
    first.settle(pageMarkup(listMarkup({ degree: "1" })), { raw: true, url: filterUrl("1") });
    await settle(10);

    assert.equal(fetchDouble().calls[0]?.signal?.aborted, true);
    assert.deepEqual(programs(), ["Molecular Chemistry"]);
    assert.equal(window.location.href, filterUrl("2"));
    assert.equal(timesSubmitted(), null);
  });

  it("submits the form the normal way when the request fails", async () => {
    await startOn(listMarkup());
    // Nothing queued: the double rejects the request, as a network failure does.

    await choose("degree", "2");

    assert.equal(timesSubmitted(), "1");
    assert.equal(window.location.href, PAGE_URL);
  });

  it("submits the form the normal way when the page answers with an error", async () => {
    await startOn(listMarkup());
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ degree: "2" })), filterUrl("2"), 500);

    await choose("degree", "2");

    assert.equal(timesSubmitted(), "1");
    assert.deepEqual(programs(), ["Applied Physics", "Molecular Chemistry", "Regional Teaching"]);
  });

  it("submits the form the normal way when the page has no such list", async () => {
    await startOn(listMarkup());
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ uid: "99", degree: "2" })), filterUrl("2"));

    await choose("degree", "2");

    assert.equal(timesSubmitted(), "1");
    assert.equal(window.location.href, PAGE_URL);
  });

  it("submits the form on a change where the template has no content region", async () => {
    await startOn(listMarkup({ withoutContentRegion: true }));

    await choose("degree", "2");

    assert.equal(fetchDouble().calls.length, 0);
    assert.equal(timesSubmitted(), "1");
    assert.equal(submitButton().hidden, true);
  });

  it("updates an override of the form partial from before in place", async () => {
    await startOn(listMarkup({ formPartialFromBefore: true }));
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ formPartialFromBefore: true, degree: "2" })), filterUrl("2"));

    await choose("degree", "2");

    assert.deepEqual(programs(), ["Molecular Chemistry"]);
    assert.equal(timesSubmitted(), null);
  });

  it("submits the form on a change where neither the list template nor the form partial has the parts", async () => {
    // Overrides of both from before, with the filter partials of the extension: their
    // selects are marked, and they no longer submit the form themselves.
    await startOn(listMarkup({ withoutContentRegion: true, formPartialFromBefore: true }));

    await choose("degree", "2");

    assert.equal(fetchDouble().calls.length, 0);
    assert.equal(timesSubmitted(), "1");
  });

  it("updates the list on a change of a select an override of the other filter partial left without a handler", async () => {
    // Only the sorting partial is overridden from before. The category selects of the
    // extension carry no handler and have to keep working.
    await startOn(listMarkup({ inlineSortingOnly: true }));
    fetchDouble().respondWithPage(pageMarkup(listMarkup({ inlineSortingOnly: true, degree: "2" })), filterUrl("2"));

    await choose("sortingField", "credit_points");
    assert.equal(fetchDouble().calls.length, 0);

    await choose("degree", "2");

    assert.equal(fetchDouble().calls.length, 1);
    assert.deepEqual(programs(), ["Molecular Chemistry"]);
  });

  it("submits the form on a change of a select without a handler outside a region, and leaves the others to theirs", async () => {
    await startOn(listMarkup({ inlineSortingOnly: true, withoutContentRegion: true }));

    await choose("sortingField", "credit_points");
    assert.equal(timesSubmitted(), null);

    await choose("degree", "2");

    assert.equal(fetchDouble().calls.length, 0);
    assert.equal(timesSubmitted(), "1");
  });

  it("leaves a form with inline handlers to them, and hides its submit button", async () => {
    await startOn(listMarkup({ inlineHandlers: true }));

    await choose("degree", "2");

    assert.equal(fetchDouble().calls.length, 0);
    assert.equal(timesSubmitted(), null);
    assert.equal(submitButton().hidden, true);
  });

  it("leaves a form with inline handlers outside a region to them, and hides its submit button", async () => {
    await startOn(listMarkup({ inlineHandlers: true, withoutContentRegion: true }));

    await choose("degree", "2");

    assert.equal(fetchDouble().calls.length, 0);
    assert.equal(timesSubmitted(), null);
    assert.equal(submitButton().hidden, true);
  });
});
