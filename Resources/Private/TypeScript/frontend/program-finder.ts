/**
 * The program finder narrowed in the browser: every option that would find no
 * program together with the selections of the other selects is disabled, and
 * the submit button states how many programs the current selection finds.
 *
 * The data comes with the page, so a change needs no request. The form carries
 * the programs in "data-academic-programs-finder-programs", a JSON list with
 * one entry per program: the uids of the offered categories it carries,
 * ancestors included when the finder includes subcategories. The selections are
 * joined with AND, as in the program list the finder opens.
 *
 * ## How the parts are found
 *
 * By data attributes only, so an override of the template keeps the module
 * working as long as it keeps them:
 *
 * - "data-academic-programs-finder-programs" on the form, the programs as JSON.
 * - "data-academic-programs-finder-count-one" and
 *   "data-academic-programs-finder-count-other" on the form, the patterns of the
 *   count sentence, "%d" standing for the number. Without both of them the
 *   module counts nothing.
 * - "data-academic-programs-finder-select" on each category select. A select
 *   without it, a sorting for example, takes no part.
 * - "data-academic-programs-finder-count" on the element whose text becomes
 *   the count sentence, a span inside the submit button.
 * - "data-academic-programs-finder-status" on an empty element with the role
 *   "status".
 *
 * The status element is written only when the count changes, so loading the
 * page announces nothing and a change that keeps the count says nothing new.
 */
const FORM = 'data-academic-programs-finder-programs';
const COUNT_LABEL_ONE = 'data-academic-programs-finder-count-one';
const COUNT_LABEL_OTHER = 'data-academic-programs-finder-count-other';
const SELECT = 'data-academic-programs-finder-select';
const COUNT = 'data-academic-programs-finder-count';
const STATUS = 'data-academic-programs-finder-status';

interface CountLabels {
    one: string;
    other: string;
}

/**
 * The category uids each program carries, one set per program, or null when the
 * attribute is no list. The module then does not start, and the finder stays
 * the one the server rendered.
 */
const readPrograms = (form: HTMLFormElement): Set<string>[] | null => {
    let parsed: unknown;
    try {
        parsed = JSON.parse(form.getAttribute(FORM) ?? '');
    } catch {
        return null;
    }
    if (!Array.isArray(parsed)) {
        return null;
    }

    return parsed.map(
        (categories: unknown): Set<string> => new Set(
            Array.isArray(categories) ? categories.map((uid: unknown): string => String(uid)) : [],
        ),
    );
};

/** Both patterns of the count sentence, or null when either is missing or empty. */
const readCountLabels = (form: HTMLFormElement): CountLabels | null => {
    const one = form.getAttribute(COUNT_LABEL_ONE) ?? '';
    const other = form.getAttribute(COUNT_LABEL_OTHER) ?? '';

    return one !== '' && other !== '' ? { one, other } : null;
};

class ProgramFinder {
    private readonly form: HTMLFormElement;
    private readonly programs: Set<string>[];
    private readonly countLabels: CountLabels | null;
    private readonly selects: HTMLSelectElement[];
    /** The options the server rendered disabled: no program carries them at all. */
    private readonly disabledByServer: WeakSet<HTMLOptionElement> = new WeakSet();
    private count: number | null = null;

    public constructor(form: HTMLFormElement, programs: Set<string>[]) {
        this.form = form;
        this.programs = programs;
        this.countLabels = readCountLabels(form);
        this.selects = Array.from(form.querySelectorAll<HTMLSelectElement>(`select[${SELECT}]`));
        this.selects.forEach((select): void => {
            Array.from(select.options).forEach((option): void => {
                if (option.disabled) {
                    this.disabledByServer.add(option);
                }
            });
        });

        form.addEventListener('change', (event: Event): void => {
            if (event.target instanceof HTMLSelectElement && this.selects.includes(event.target)) {
                this.update(true);
            }
        });
        // A preselected category narrows the other selects from the start.
        this.update(false);
    }

    /** The programs that carry every selected category, except the one of a select left out. */
    private matching(leftOut: HTMLSelectElement | null): Set<string>[] {
        const selected = this.selects
            .filter((select): boolean => select !== leftOut && select.value !== '')
            .map((select): string => select.value);

        return this.programs.filter(
            (categories): boolean => selected.every((uid): boolean => categories.has(uid)),
        );
    }

    private update(announce: boolean): void {
        this.selects.forEach((select): void => {
            const candidates = this.matching(select);
            Array.from(select.options).forEach((option): void => {
                // The "All" option always finds something, and the selected option stays
                // selectable: a preselection whose categories exclude each other would
                // otherwise leave a select on an option that cannot be changed back.
                if (option.value === '' || option.selected || this.disabledByServer.has(option)) {
                    return;
                }
                option.disabled = !candidates.some((categories): boolean => categories.has(option.value));
            });
        });

        if (this.countLabels === null) {
            return;
        }
        const count = this.matching(null).length;
        const sentence = (count === 1 ? this.countLabels.one : this.countLabels.other).replace('%d', String(count));
        this.form.querySelectorAll(`[${COUNT}]`).forEach((element): void => {
            element.textContent = sentence;
        });
        if (announce && count !== this.count) {
            this.form.querySelectorAll(`[${STATUS}]`).forEach((element): void => {
                element.textContent = sentence;
            });
        }
        this.count = count;
    }
}

/** The running finders, keyed by their form so that a second start skips them. */
const instances = new WeakMap<HTMLFormElement, ProgramFinder>();

/**
 * Starts one instance per finder form of the document, skipping the ones that
 * are already running.
 *
 * Exported so that the "testJs" suite can drive a fixture with it. A browser
 * reaches it through the statements below.
 */
const init = (): void => {
    document.querySelectorAll<HTMLFormElement>(`form[${FORM}]`).forEach((form): void => {
        if (instances.has(form)) {
            return;
        }
        const programs = readPrograms(form);
        if (programs !== null) {
            instances.set(form, new ProgramFinder(form, programs));
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

export { init };
