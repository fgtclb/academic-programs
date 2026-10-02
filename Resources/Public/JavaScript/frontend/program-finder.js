/* Generated from Resources/Private/TypeScript — do not edit. */
const FORM = "data-academic-programs-finder-programs";
const COUNT_LABEL_ONE = "data-academic-programs-finder-count-one";
const COUNT_LABEL_OTHER = "data-academic-programs-finder-count-other";
const SELECT = "data-academic-programs-finder-select";
const COUNT = "data-academic-programs-finder-count";
const STATUS = "data-academic-programs-finder-status";
const readPrograms = (form) => {
  let parsed;
  try {
    parsed = JSON.parse(form.getAttribute(FORM) ?? "");
  } catch {
    return null;
  }
  if (!Array.isArray(parsed)) {
    return null;
  }
  return parsed.map(
    (categories) => new Set(
      Array.isArray(categories) ? categories.map((uid) => String(uid)) : []
    )
  );
};
const readCountLabels = (form) => {
  const one = form.getAttribute(COUNT_LABEL_ONE) ?? "";
  const other = form.getAttribute(COUNT_LABEL_OTHER) ?? "";
  return one !== "" && other !== "" ? { one, other } : null;
};
class ProgramFinder {
  form;
  programs;
  countLabels;
  selects;
  /** The options the server rendered disabled: no program carries them at all. */
  disabledByServer = /* @__PURE__ */ new WeakSet();
  count = null;
  constructor(form, programs) {
    this.form = form;
    this.programs = programs;
    this.countLabels = readCountLabels(form);
    this.selects = Array.from(form.querySelectorAll(`select[${SELECT}]`));
    this.selects.forEach((select) => {
      Array.from(select.options).forEach((option) => {
        if (option.disabled) {
          this.disabledByServer.add(option);
        }
      });
    });
    form.addEventListener("change", (event) => {
      if (event.target instanceof HTMLSelectElement && this.selects.includes(event.target)) {
        this.update(true);
      }
    });
    this.update(false);
  }
  /** The programs that carry every selected category, except the one of a select left out. */
  matching(leftOut) {
    const selected = this.selects.filter((select) => select !== leftOut && select.value !== "").map((select) => select.value);
    return this.programs.filter(
      (categories) => selected.every((uid) => categories.has(uid))
    );
  }
  update(announce) {
    this.selects.forEach((select) => {
      const candidates = this.matching(select);
      Array.from(select.options).forEach((option) => {
        if (option.value === "" || option.selected || this.disabledByServer.has(option)) {
          return;
        }
        option.disabled = !candidates.some((categories) => categories.has(option.value));
      });
    });
    if (this.countLabels === null) {
      return;
    }
    const count = this.matching(null).length;
    const sentence = (count === 1 ? this.countLabels.one : this.countLabels.other).replace("%d", String(count));
    this.form.querySelectorAll(`[${COUNT}]`).forEach((element) => {
      element.textContent = sentence;
    });
    if (announce && count !== this.count) {
      this.form.querySelectorAll(`[${STATUS}]`).forEach((element) => {
        element.textContent = sentence;
      });
    }
    this.count = count;
  }
}
const instances = /* @__PURE__ */ new WeakMap();
const init = () => {
  document.querySelectorAll(`form[${FORM}]`).forEach((form) => {
    if (instances.has(form)) {
      return;
    }
    const programs = readPrograms(form);
    if (programs !== null) {
      instances.set(form, new ProgramFinder(form, programs));
    }
  });
};
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
export {
  init
};
