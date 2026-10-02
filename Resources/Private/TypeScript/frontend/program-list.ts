/**
 * The program list updated in place: a change of a filter or of the sorting
 * posts the form as the browser would, follows the redirect to the filter URL
 * and replaces the lists with the ones of that page. The address bar shows the
 * filter URL afterwards, and the back and forward buttons step through the
 * selections.
 *
 * The server stays the only place that turns a selection into a URL and a
 * list: the module neither builds a URL nor renders a program. Every list of
 * the page is replaced, not only the one that changed: all program lists share
 * one plugin namespace, so the filter URL filters each of them, and the page
 * shows what a reload of that URL shows.
 *
 * ## How the parts are found
 *
 * By data attributes, so an override of the templates keeps the module working
 * as long as it keeps them:
 *
 * - "data-academic-programs-list" on the wrapper of a list, the uid of its
 *   content element, which finds the same list in the page of the filter URL.
 *   "data-academic-programs-list-count-one" and "-count-other" on it are the
 *   patterns of the sentence announced after an update, "%d" standing for the
 *   number of programs.
 * - "data-academic-programs-list-content" on the region that is replaced, the
 *   form and the results, with "data-academic-programs-list-total", the number
 *   of programs it shows. The form in it is the one the module drives, also
 *   the form of an override from before that carries no attribute of its own.
 * - "data-academic-programs-list-status" on an empty element with the role
 *   "status" outside that region, so that it is still the same element when the
 *   sentence is written into it.
 * - "data-academic-programs-list-submit" on the submit button or the element
 *   around it, which the module hides: it is there for a visitor without
 *   JavaScript.
 * - "data-academic-programs-list-form" on the form, and
 *   "data-academic-programs-list-select" on each select. A form outside such a
 *   region that carries either of them submits itself on a change and reloads
 *   the page, as the inline handlers did before.
 *
 * A select with an inline "onchange" handler comes from an override that
 * keeps the old behaviour, and the module leaves its changes to that handler.
 * The other selects of the same form are driven as usual, so an override of
 * one of the two filter partials leaves the selects of the other one working.
 *
 * ## When it reloads after all
 *
 * A request that fails, an error status, or a page without the list submits
 * the form the normal way, so the visitor gets the page a reload gives. Going
 * back or forward requests the URL of that history entry and reloads it when
 * that fails. A request still running when the next one starts is aborted.
 */
const LIST = 'data-academic-programs-list';
const COUNT_ONE = 'data-academic-programs-list-count-one';
const COUNT_OTHER = 'data-academic-programs-list-count-other';
const CONTENT = 'data-academic-programs-list-content';
const TOTAL = 'data-academic-programs-list-total';
const STATUS = 'data-academic-programs-list-status';
const SUBMIT = 'data-academic-programs-list-submit';
const FORM = 'data-academic-programs-list-form';
const SELECT = 'data-academic-programs-list-select';

/** The key in the history state that marks an entry this module can restore. */
const HISTORY_KEY = 'academicProgramsList';

/** The running lists, keyed by their wrapper, and the forms left to reload the page. */
const lists = new WeakMap<HTMLElement, ProgramList>();
const reloadingForms = new WeakSet<HTMLFormElement>();
const started: ProgramList[] = [];

/** The request running for any list. One at a time: they share one address bar. */
let running: AbortController | null = null;
/** The URL the lists show, without its fragment. */
let shownUrl = '';
let historyStarted = false;

const withoutFragment = (url: string): string => url.split('#')[0] ?? url;

/** A select whose own inline handler submits the form, from an override that predates the module. */
const hasInlineHandler = (select: HTMLSelectElement): boolean => select.hasAttribute('onchange');

const hideSubmitButtons = (root: ParentNode): void => {
    root.querySelectorAll<HTMLElement>(`[${SUBMIT}]`).forEach((button): void => {
        button.hidden = true;
    });
};

/** The list with the uid in a page, compared as a string rather than built into a selector. */
const findList = (root: ParentNode, uid: string): HTMLElement | null =>
    Array.from(root.querySelectorAll<HTMLElement>(`[${LIST}]`)).find(
        (element): boolean => element.getAttribute(LIST) === uid,
    ) ?? null;

/**
 * Starts a request, aborting the one still running. The result is null when
 * this request was aborted in the meantime, and the caller then does nothing.
 */
const request = async (url: string, init: RequestInit): Promise<{ response: Response; html: string } | null> => {
    running?.abort();
    const controller = new AbortController();
    running = controller;
    try {
        const response = await fetch(url, { ...init, signal: controller.signal });
        const html = await response.text();

        return { response, html };
    } catch (error: unknown) {
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

/**
 * Replaces every started list the page carries. The changed list says the
 * number of programs, or with null the first list replaced, so that a screen
 * reader hears one sentence. True when the changed list, or with null any
 * list, was replaced.
 */
const replaceLists = (page: Document, changed: ProgramList | null): boolean => {
    let replaced = false;
    started.forEach((list): void => {
        const announces = changed === list || (changed === null && !replaced);
        if (list.replace(page, announces) && (changed === null || changed === list)) {
            replaced = true;
        }
    });

    return replaced;
};

class ProgramList {
    private readonly element: HTMLElement;

    public constructor(element: HTMLElement) {
        this.element = element;

        element.addEventListener('change', (event: Event): void => {
            const form = this.drivenForm(event.target);
            if (form !== null && event.target instanceof HTMLSelectElement && !hasInlineHandler(event.target)) {
                void this.submit(form);
            }
        });
        element.addEventListener('submit', (event: Event): void => {
            const form = this.drivenForm(event.target);
            if (form !== null) {
                event.preventDefault();
                void this.submit(form);
            }
        });
    }

    private uid(): string {
        return this.element.getAttribute(LIST) ?? '';
    }

    private content(): HTMLElement | null {
        return this.element.querySelector<HTMLElement>(`[${CONTENT}]`);
    }

    /** The form in the region of this list the event came from. */
    private drivenForm(target: EventTarget | null): HTMLFormElement | null {
        if (!(target instanceof Element)) {
            return null;
        }
        const form = target instanceof HTMLFormElement ? target : target.closest<HTMLFormElement>('form');

        return form !== null && this.content()?.contains(form) === true ? form : null;
    }

    private async submit(form: HTMLFormElement): Promise<void> {
        let result: { response: Response; html: string } | null;
        try {
            result = await request(form.action, { method: 'POST', body: new FormData(form) });
        } catch {
            form.submit();

            return;
        }
        if (result === null) {
            return;
        }
        const { response, html } = result;
        if (!response.ok || !replaceLists(new DOMParser().parseFromString(html, 'text/html'), this)) {
            form.submit();

            return;
        }

        const url = withoutFragment(response.url);
        if (url !== '' && url !== withoutFragment(window.location.href)) {
            window.history.pushState({ [HISTORY_KEY]: true }, '', url);
        }
        shownUrl = withoutFragment(window.location.href);
    }

    /**
     * Replaces the content region by the one of the same list in the page, and
     * announces the number of programs when asked to. False when the page has
     * no such list, and nothing is changed then.
     */
    public replace(page: Document, announce: boolean): boolean {
        const current = this.content();
        const incoming = findList(page, this.uid())?.querySelector<HTMLElement>(`[${CONTENT}]`) ?? null;
        if (!this.element.isConnected || current === null || incoming === null) {
            return false;
        }

        // What a visitor did to the region that a reload would undo: the field that had the
        // focus, and the "More filters" they opened.
        const active = document.activeElement;
        const focusedName = active instanceof HTMLSelectElement && current.contains(active) ? active.name : '';
        const currentDetails = Array.from(current.querySelectorAll('details'));

        const replacement = document.importNode(incoming, true);
        hideSubmitButtons(replacement);
        Array.from(replacement.querySelectorAll('details')).forEach((details, index): void => {
            if (currentDetails[index]?.open === true) {
                details.open = true;
            }
        });
        current.replaceWith(replacement);

        if (focusedName !== '') {
            replacement.querySelector<HTMLSelectElement>(`select[name="${CSS.escape(focusedName)}"]`)?.focus();
        }
        if (announce) {
            this.announce(replacement);
        }

        return true;
    }

    private announce(content: HTMLElement): void {
        const one = this.element.getAttribute(COUNT_ONE) ?? '';
        const other = this.element.getAttribute(COUNT_OTHER) ?? '';
        const total = Number.parseInt(content.getAttribute(TOTAL) ?? '', 10);
        if (one === '' || other === '' || Number.isNaN(total)) {
            return;
        }
        const sentence = (total === 1 ? one : other).replace('%d', String(total));
        this.element.querySelectorAll(`[${STATUS}]`).forEach((status): void => {
            if (!content.contains(status)) {
                status.textContent = sentence;
            }
        });
    }
}

/**
 * Back and forward: the lists show the page of the URL the history entry
 * holds. Every list of that page is replaced, as a reload would replace them.
 */
const restore = async (): Promise<void> => {
    const url = withoutFragment(window.location.href);
    shownUrl = url;
    let result: { response: Response; html: string } | null;
    try {
        result = await request(url, { method: 'GET' });
    } catch {
        window.location.reload();

        return;
    }
    if (result === null) {
        return;
    }
    if (!result.response.ok || !replaceLists(new DOMParser().parseFromString(result.html, 'text/html'), null)) {
        window.location.reload();
    }
};

const startHistory = (): void => {
    if (historyStarted) {
        return;
    }
    historyStarted = true;
    shownUrl = withoutFragment(window.location.href);
    const state: unknown = window.history.state;
    window.history.replaceState(
        { ...(typeof state === 'object' && state !== null ? state : {}), [HISTORY_KEY]: true },
        '',
    );
    window.addEventListener('popstate', (event: PopStateEvent): void => {
        const state: unknown = event.state;
        if (typeof state !== 'object' || state === null || !(HISTORY_KEY in state)) {
            return;
        }
        // A fragment link adds an entry with the same URL. Going back over it changes nothing.
        if (withoutFragment(window.location.href) === shownUrl) {
            return;
        }
        void restore();
    });
};

/** A form outside a region, which a change submits as the inline handlers did. */
const startReloadingForm = (form: HTMLFormElement): void => {
    if (reloadingForms.has(form) || form.closest(`[${LIST}] [${CONTENT}]`) !== null) {
        return;
    }
    reloadingForms.add(form);
    hideSubmitButtons(form);
    form.addEventListener('change', (event: Event): void => {
        if (event.target instanceof HTMLSelectElement && !hasInlineHandler(event.target)) {
            form.submit();
        }
    });
};

/**
 * Starts the module for every program list of the document and every marked
 * form outside of one, skipping the ones that already run. A list without a
 * form is started too: the filter URL filters it as well, so it is replaced
 * with the others.
 *
 * Exported so that the "testJs" suite can drive a fixture with it. A browser
 * reaches it through the statements below.
 */
const init = (): void => {
    document.querySelectorAll<HTMLElement>(`[${LIST}]`).forEach((element): void => {
        const content = element.querySelector<HTMLElement>(`[${CONTENT}]`);
        if (lists.has(element) || content === null) {
            return;
        }
        hideSubmitButtons(content);
        const list = new ProgramList(element);
        lists.set(element, list);
        started.push(list);
        startHistory();
    });
    document.querySelectorAll<HTMLFormElement>(`form[${FORM}]`).forEach(startReloadingForm);
    document.querySelectorAll<HTMLSelectElement>(`select[${SELECT}]`).forEach((select): void => {
        if (select.form !== null) {
            startReloadingForm(select.form);
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

export { init };
