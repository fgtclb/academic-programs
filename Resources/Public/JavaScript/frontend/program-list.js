/* Generated from Resources/Private/TypeScript — do not edit. */
const LIST = "data-academic-programs-list";
const COUNT_ONE = "data-academic-programs-list-count-one";
const COUNT_OTHER = "data-academic-programs-list-count-other";
const CONTENT = "data-academic-programs-list-content";
const TOTAL = "data-academic-programs-list-total";
const STATUS = "data-academic-programs-list-status";
const SUBMIT = "data-academic-programs-list-submit";
const FORM = "data-academic-programs-list-form";
const SELECT = "data-academic-programs-list-select";
const HISTORY_KEY = "academicProgramsList";
const lists = /* @__PURE__ */ new WeakMap();
const reloadingForms = /* @__PURE__ */ new WeakSet();
const started = [];
let running = null;
let shownUrl = "";
let historyStarted = false;
const withoutFragment = (url) => url.split("#")[0] ?? url;
const hasInlineHandler = (select) => select.hasAttribute("onchange");
const hideSubmitButtons = (root) => {
  root.querySelectorAll(`[${SUBMIT}]`).forEach((button) => {
    button.hidden = true;
  });
};
const findList = (root, uid) => Array.from(root.querySelectorAll(`[${LIST}]`)).find(
  (element) => element.getAttribute(LIST) === uid
) ?? null;
const request = async (url, init2) => {
  running == null ? void 0 : running.abort();
  const controller = new AbortController();
  running = controller;
  try {
    const response = await fetch(url, { ...init2, signal: controller.signal });
    const html = await response.text();
    return { response, html };
  } catch (error) {
    if (running !== controller) {
      return null;
    }
    throw error;
  } finally {
    if (running === controller) {
      running = null;
    }
  }
};
const replaceLists = (page, changed) => {
  let replaced = false;
  started.forEach((list) => {
    const announces = changed === list || changed === null && !replaced;
    if (list.replace(page, announces) && (changed === null || changed === list)) {
      replaced = true;
    }
  });
  return replaced;
};
class ProgramList {
  element;
  constructor(element) {
    this.element = element;
    element.addEventListener("change", (event) => {
      const form = this.drivenForm(event.target);
      if (form !== null && event.target instanceof HTMLSelectElement && !hasInlineHandler(event.target)) {
        void this.submit(form);
      }
    });
    element.addEventListener("submit", (event) => {
      const form = this.drivenForm(event.target);
      if (form !== null) {
        event.preventDefault();
        void this.submit(form);
      }
    });
  }
  uid() {
    return this.element.getAttribute(LIST) ?? "";
  }
  content() {
    return this.element.querySelector(`[${CONTENT}]`);
  }
  /** The form in the region of this list the event came from. */
  drivenForm(target) {
    var _a;
    if (!(target instanceof Element)) {
      return null;
    }
    const form = target instanceof HTMLFormElement ? target : target.closest("form");
    return form !== null && ((_a = this.content()) == null ? void 0 : _a.contains(form)) === true ? form : null;
  }
  async submit(form) {
    let result;
    try {
      result = await request(form.action, { method: "POST", body: new FormData(form) });
    } catch {
      form.submit();
      return;
    }
    if (result === null) {
      return;
    }
    const { response, html } = result;
    if (!response.ok || !replaceLists(new DOMParser().parseFromString(html, "text/html"), this)) {
      form.submit();
      return;
    }
    const url = withoutFragment(response.url);
    if (url !== "" && url !== withoutFragment(window.location.href)) {
      window.history.pushState({ [HISTORY_KEY]: true }, "", url);
    }
    shownUrl = withoutFragment(window.location.href);
  }
  /**
   * Replaces the content region by the one of the same list in the page, and
   * announces the number of programs when asked to. False when the page has
   * no such list, and nothing is changed then.
   */
  replace(page, announce) {
    var _a, _b;
    const current = this.content();
    const incoming = ((_a = findList(page, this.uid())) == null ? void 0 : _a.querySelector(`[${CONTENT}]`)) ?? null;
    if (!this.element.isConnected || current === null || incoming === null) {
      return false;
    }
    const active = document.activeElement;
    const focusedName = active instanceof HTMLSelectElement && current.contains(active) ? active.name : "";
    const currentDetails = Array.from(current.querySelectorAll("details"));
    const replacement = document.importNode(incoming, true);
    hideSubmitButtons(replacement);
    Array.from(replacement.querySelectorAll("details")).forEach((details, index) => {
      var _a2;
      if (((_a2 = currentDetails[index]) == null ? void 0 : _a2.open) === true) {
        details.open = true;
      }
    });
    current.replaceWith(replacement);
    if (focusedName !== "") {
      (_b = replacement.querySelector(`select[name="${CSS.escape(focusedName)}"]`)) == null ? void 0 : _b.focus();
    }
    if (announce) {
      this.announce(replacement);
    }
    return true;
  }
  announce(content) {
    const one = this.element.getAttribute(COUNT_ONE) ?? "";
    const other = this.element.getAttribute(COUNT_OTHER) ?? "";
    const total = Number.parseInt(content.getAttribute(TOTAL) ?? "", 10);
    if (one === "" || other === "" || Number.isNaN(total)) {
      return;
    }
    const sentence = (total === 1 ? one : other).replace("%d", String(total));
    this.element.querySelectorAll(`[${STATUS}]`).forEach((status) => {
      if (!content.contains(status)) {
        status.textContent = sentence;
      }
    });
  }
}
const restore = async () => {
  const url = withoutFragment(window.location.href);
  shownUrl = url;
  let result;
  try {
    result = await request(url, { method: "GET" });
  } catch {
    window.location.reload();
    return;
  }
  if (result === null) {
    return;
  }
  if (!result.response.ok || !replaceLists(new DOMParser().parseFromString(result.html, "text/html"), null)) {
    window.location.reload();
  }
};
const startHistory = () => {
  if (historyStarted) {
    return;
  }
  historyStarted = true;
  shownUrl = withoutFragment(window.location.href);
  const state = window.history.state;
  window.history.replaceState(
    { ...typeof state === "object" && state !== null ? state : {}, [HISTORY_KEY]: true },
    ""
  );
  window.addEventListener("popstate", (event) => {
    const state2 = event.state;
    if (typeof state2 !== "object" || state2 === null || !(HISTORY_KEY in state2)) {
      return;
    }
    if (withoutFragment(window.location.href) === shownUrl) {
      return;
    }
    void restore();
  });
};
const startReloadingForm = (form) => {
  if (reloadingForms.has(form) || form.closest(`[${LIST}] [${CONTENT}]`) !== null) {
    return;
  }
  reloadingForms.add(form);
  hideSubmitButtons(form);
  form.addEventListener("change", (event) => {
    if (event.target instanceof HTMLSelectElement && !hasInlineHandler(event.target)) {
      form.submit();
    }
  });
};
const init = () => {
  document.querySelectorAll(`[${LIST}]`).forEach((element) => {
    const content = element.querySelector(`[${CONTENT}]`);
    if (lists.has(element) || content === null) {
      return;
    }
    hideSubmitButtons(content);
    const list = new ProgramList(element);
    lists.set(element, list);
    started.push(list);
    startHistory();
  });
  document.querySelectorAll(`form[${FORM}]`).forEach(startReloadingForm);
  document.querySelectorAll(`select[${SELECT}]`).forEach((select) => {
    if (select.form !== null) {
      startReloadingForm(select.form);
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
