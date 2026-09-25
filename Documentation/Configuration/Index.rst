:navigation-title: Configuration

..  _configuration:

=============
Configuration
=============

This extension ships its frontend TypoScript and its backend page TSconfig in
two forms: as TYPO3 **site sets**, and as classic **static templates** plus
**page TSconfig files** that are selected on a page. Both forms read the very
same files, so they configure an installation identically.

Pick one of them per site and stay with it — see
:ref:`Do not combine both <one-mechanism-per-site>` for what happens otherwise.

..  _configuration-components:

What the sets contain
=====================

This extension ships two content elements, so it ships two component sets and
one aggregate set that depends on both.

Both content elements are driven by one Extbase plugin, so they share one
TypoScript block, :typoscript:`plugin.tx_academicprograms`. That block is
shipped once, in :file:`Configuration/TypoScript/`, and every component includes
it. Which component sets a site names therefore decides which content elements
the backend offers, not how much TypoScript is loaded.

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-programs-program-list`
        -   The :guilabel:`Program List` content element.
    *   -   `fgtclb/academic-programs-program-details`
        -   The :guilabel:`Program Details` content element.
    *   -   `fgtclb/academic-programs`
        -   Everything above. This is the set to use unless you deliberately
            want a subset, and it is the name this extension published before
            the sets were cut per component — a site configuration that depends
            on it needs no change.

Every content element set depends on `fgtclb/academic-base-ctype-group`, the set
of :guilabel:`EXT:academic_base` that labels the content element group all
academic extensions sort their elements into.

..  _configuration-hidden-by-default:

The content elements are hidden by default
==========================================

:guilabel:`EXT:academic_programs` hides both of its content elements for the
whole installation and brings them back per component. Whichever of the two
mechanisms below you use, it is what makes an element selectable in the backend
again — without one of them the content element is not offered, and existing
records keep rendering.

..  warning::

    This changed in version 2.4. Before it, both elements were selectable on
    every page of every installation. Read
    :ref:`Breaking: Site sets and static templates have been restructured
    <breaking-site-sets-and-static-templates-restructured>` before upgrading:
    opening an existing record on a page that does not include the page TSconfig
    of its component can rewrite the type of that record.

What the sets do not control
============================

The page type :guilabel:`Academic program` (doktype 20) and its backend layout
:guilabel:`AcademicProgram` are **not** part of any set, and enabling or not
enabling a set never changes them.

That is deliberate, not an oversight. Both are values stored on :sql:`pages`
records: a page carries `doktype = 20` and `backend_layout = pagets__AcademicProgram`
long before any site configuration is read. Were they delivered by an opt-in
set, every page tree on a site that does not use that set would show
:guilabel:`[ MISSING LABEL ]` for the layout, the layout could not be picked for
a new page, and the page type would disappear from the page tree wizard.

They are therefore registered installation-wide — the page type in TCA
(:file:`Configuration/TCA/Overrides/pages.php`), the backend layout in the
always-included :file:`Configuration/page.tsconfig` — and stay available on every
site of the installation.

What a set does deliver for that page type is its **frontend rendering**: the
:typoscript:`page` object that picks the Fluid template of the page type is part
of the shared TypoScript block, so a site that includes no set of this extension
renders such a page with whatever its own site package defines.

..  _program-page-content:

The content of a program page
=============================

A program page renders the content elements of its main column
(:typoscript:`colPos = 0`) below the program data, in their manual order and in
the language of the page. Any set of this extension, or the static template of
the shared block, delivers that, and no other set is needed for it.

The content is the variable :typoscript:`programContent` of the page object,
defined inside the condition on the program page type, so it exists on program
pages only. It is a :typoscript:`CONTENT` object that renders the records
through the :typoscript:`tt_content` object of the site, and it works for a
:typoscript:`FLUIDTEMPLATE` and a :typoscript:`PAGEVIEW` page object alike.

To render another column, or to slide the content from the parent pages, change
the variable inside the same condition:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    [page && traverse(page, "doktype") == 20]
      page.10.variables.programContent {
        select.where = {#colPos}=1
      }
    [END]

A page template of your own renders it as
:html:`{programContent -> f:format.raw()}`.

..  versionchanged:: 3.0

    Up to 2.x the page template rendered the global object
    :typoscript:`styles.content.getContent`, which only the set
    `fgtclb/academic-programs-content-load` defined for the whole site. The set
    and its static template are removed, see
    :ref:`breaking-programs-content-load-set-removed`.

..  _program-page-layout:

The layout of a program page
============================

A program page renders inside the page layout of the site package, the way the
other pages of the site do: the page template declares a layout and fills its
section :html:`Main`. The layout is :file:`Default` unless a setting names
another one, which is what :composer:`bk2k/bootstrap-package` and most site
packages provide.

..  list-table::
    :header-rows: 1

    *   -   Site setting / constant
        -   Default
        -   Meaning
    *   -   :typoscript:`plugin.tx_academicprograms.page.layout`
        -   `Default`
        -   The Fluid layout of the site package the program page renders its
            section :html:`Main` into. An empty value is read as `Default`.
    *   -   :typoscript:`plugin.tx_academicprograms.page.listPid`
        -   `0`
        -   The page the header of a program page links back to, the program
            list. `0` renders no link.

Both are site settings of the aggregate set `fgtclb/academic-programs`, and
constants of the same name for a site on the static templates. A site that
depends on a component set alone gets the defaults, but the site settings
editor does not offer the settings there; depend on the aggregate set to
configure them.

The page template reaches both as variables of the page object,
:typoscript:`programPageLayout` and :typoscript:`programListPid`, so they work
on a :typoscript:`FLUIDTEMPLATE` and a :typoscript:`PAGEVIEW` page object alike.

A site package without a layout :file:`Default` gets the fallback layout of this
extension, which renders the section :html:`Main` and nothing else: the page
renders without the header, navigation and footer of the site, as it did up to
2.x, rather than failing. A layout :file:`Default` of the site package wins over
it. The fallback exists for :file:`Default` only: a layout the setting names
has to exist in the site package, or the program page fails as any page with a
missing Fluid layout does.

..  _program-page-partials:

The parts of a program page
---------------------------

The section :html:`Main` renders four partials, each of which can be replaced
on its own:

..  list-table::
    :header-rows: 1

    *   -   Partial
        -   Renders
    *   -   :file:`Program/Page/Header.html`
        -   The link back to the program list, the title and the subtitle, in
            one element with the class `academic-programs-detail__header`.
    *   -   :file:`Program/Page/Media.html`
        -   The first image of the page, through the shared image partial of
            :guilabel:`EXT:academic_base`.
    *   -   :file:`Program/Page/Facts.html`
        -   The facts of the program, through :file:`Program/Facts.html` - see
            :ref:`program-facts`.
    *   -   :file:`Program/Page/Content.html`
        -   The content elements, the variable :typoscript:`programContent`
            described above.

Every partial receives all variables of the page: :html:`{program}`,
:html:`{facts}`, :html:`{images}`, :html:`{programContent}`,
:html:`{programListPid}` and those of the site package's page object.

The templates and partials of the page type are registered at the key `50` of
the page object. Register a directory of your own with a higher key, and its
files win:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    # FLUIDTEMPLATE: a directory holding Program/Page/Header.html
    page.10.partialRootPaths.75 = EXT:my_sitepackage/Resources/Private/Partials/

    # PAGEVIEW: a directory holding Partials/Program/Page/Header.html
    page.10.paths.75 = EXT:my_sitepackage/Resources/Private/

A site package that registers its own paths above `50` needs no line at all -
a :typoscript:`PAGEVIEW` site package at :typoscript:`paths.100`, for example:
a :file:`Partials/Program/Page/Header.html` of its own wins already. An override of
the whole :file:`Pages/AcademicProgram.html` keeps working the same way, and
renders as before.

..  versionchanged:: 3.0

    Up to 2.x the page template declared no layout and rendered every part
    inline, and its paths used the key `100`. See
    :ref:`breaking-program-page-renders-inside-the-site-layout`.

..  _program-facts:

The facts of a program
======================

The program page, the :guilabel:`Program Details` content element and each
card of the :guilabel:`Program List` show facts about a program: its
categories per category type, and the program fields credit points, job
profile, performance scope and prerequisites. Two settings decide which facts
a place shows and in which order:

..  list-table::
    :header-rows: 1

    *   -   Site setting / constant
        -   Default
        -   Facts of
    *   -   :typoscript:`plugin.tx_academicprograms.facts.fields`
        -   empty
        -   the program page and the :guilabel:`Program Details` content
            element
    *   -   :typoscript:`plugin.tx_academicprograms.card.fields`
        -   `degree`
        -   each card of the :guilabel:`Program List`

Both are site settings of the aggregate set `fgtclb/academic-programs` and
constants of the same name for a site on the static templates, like the
settings of :ref:`program-page-layout`.

Each is a comma separated list, shown in its order. An item is

*   the identifier of a category type of the group `programs`:
    `admission_restriction`, `application_period`, `begin_program`, `costs`,
    `paying`, `degree`, `department`, `standard_period`, `location`,
    `program_type`, `teaching_language`, `topic`, or a type another extension
    registers for the group - its label is looked up as
    `sys_category.programs.<identifier>` in :file:`locallang.xlf` of this
    extension, so such a type needs that label added, for example through
    :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']`, or its
    row shows no label;
*   or one of the program fields `creditPoints`, `jobProfile`,
    `performanceScope` and `prerequisites`.

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicprograms:
        facts:
          fields: 'degree,creditPoints,standard_period,location'
        card:
          fields: 'degree,standard_period'

An item that is neither is skipped, and so is a repeated one. A fact the
program has no value for is not shown: a category type without a category, an
empty text field, credit points of `0`.

An empty list shows what the place showed before the settings existed:

*   the program page every category type, followed by credit points, job
    profile, performance scope and prerequisites;
*   the details content element every category type;
*   the card the degree.

"Every category type" follows the order of the category type configuration of
the group, the order every other output of category types follows. A list
that names category types shows them in the order it names them.

The facts are rendered by two partials, which a project can override like any
other partial of this extension:

..  list-table::
    :header-rows: 1

    *   -   Partial
        -   Renders
    *   -   :file:`Program/Facts.html`
        -   The list, :html:`<ul class="academic-programs-facts">`, from
            :html:`{facts}`. The card hands in the additional classes
            :html:`listClass` and :html:`itemClass`.
    *   -   :file:`Program/Facts/Item.html`
        -   One fact, :html:`{fact}`: an icon if it has one, the label and the
            categories or the value. A program field value is the rich text of
            the field, rendered as it is stored.

:html:`{fact}` carries :html:`identifier`, :html:`labelKey` (a key of
:file:`locallang.xlf` of this extension), :html:`iconIdentifier` (empty for a
fact without an icon), :html:`isCategoryType`, :html:`categories` for a
category type and :html:`value` for a program field. The icon of the credit
points fact is `tx-academicprograms-info-credit-points`; the category types
use their own icons, the other three program fields have none.

A template of your own gets the facts from the variable :html:`{facts}` on the
program page and in the details content element. Anywhere else, the view
helper builds them for a program:

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Partials/Program/Item.html

    <html xmlns:ace="http://typo3.org/ns/FGTCLB/AcademicPrograms/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <f:variable name="cardFacts"
                value="{ace:program.facts(program: program, fields: settings.card.fields)}" />
    <f:render partial="Program/Facts" arguments="{facts: cardFacts}" />

    </html>

..  versionchanged:: 3.0

    Up to 2.x the facts were fixed in the templates, and the partial
    :file:`Program/Categories.html` rendered the categories. See
    :ref:`breaking-program-categories-partial-removed`.

..  _program-list-filter-types:

The filters of the program list
===============================

The filter form of the :guilabel:`Program List` offers one select per category
type of the group `programs`. Which types it offers, and in which order, is set
in two places:

..  list-table::
    :header-rows: 1

    *   -   Where
        -   Applies to
    *   -   Field :guilabel:`Filter types` of the :guilabel:`Program List`
            content element, tab :guilabel:`Filter`
        -   That element. The editor picks the types and orders them.
    *   -   Site setting / constant
            :typoscript:`plugin.tx_academicprograms.filter.categoryTypes`,
            default empty
        -   Every program list of the site whose field is empty. A comma
            separated list of type identifiers, for example
            `degree,location,program_type`.

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicprograms:
        filter:
          categoryTypes: 'degree,location'

The setting is a site setting of the aggregate set `fgtclb/academic-programs`
and a constant of the same name for a site on the static templates, like the
settings of :ref:`program-page-layout`. A site that depends on the
:guilabel:`Program List` component set alone gets the default, but the site
settings editor does not offer the setting there.

With both empty the form offers every category type of the group that has a
category, in the order the types are registered in — what it offered before
the setting existed.

A type is offered only when at least one category of that type exists. A
category of an offered type that no listed program carries is still offered,
as a disabled option. A type the project removed from the group later is
ignored, and so is an identifier that is no type of the group. The field only
offers the types the group has, including those a project adds in its own
:file:`Configuration/CategoryTypes.yaml`.

The filter types decide what the form offers, not what the list accepts: a
link that filters by a category of a type the form does not offer still
filters the list.

The field is labelled with the title of each type. A type a project adds shows
the title its :file:`CategoryTypes.yaml` gives it, and the filter select in the
frontend is labelled with :xml:`sys_category.programs.<identifier>` of this
extension's :file:`locallang.xlf`, as before.

The partial :file:`Program/DemandCategories.html` renders the selects from the
variable :html:`{filterTypes.visible}`, the identifiers of the offered types in
their order. Where that variable does not reach the partial — a project
controller that overrides :php:`listAction()`, or a template that renders the
partial with arguments of its own instead of :html:`{_all}` — it offers every
type with a category, as before, and the field and the setting have no effect.
To use them, let the overriding action call :php:`parent::listAction()`, and
pass :html:`filterTypes` on in a template that renders the partial. A project that
overrides the partial itself and still loops
:html:`{categories.allCategoriesByType}` keeps its own list as well.

..  versionadded:: 3.0

    Up to 2.x the form offered every type with a category, and a different
    set or order needed an override of the partial.

..  _site-set:

Include the site set
====================

Add the set to the :file:`config.yaml` of the site that should offer the content
elements:

..  code-block:: diff
    :caption: config/sites/my-site/config.yaml (diff)

     base: 'https://example.com/'
     rootPageId: 1
    +dependencies:
    +  - fgtclb/academic-programs

See also `TYPO3 Explained, Using a site set as dependency in a site
<https://docs.typo3.org/permalink/t3coreapi:site-sets-usage>`__.

..  _static-templates:

Include static templates
========================

For an installation that still configures its frontend through
:sql:`sys_template` records, the same files are registered as static templates
and as selectable page TSconfig files.

..  tip::

    On TYPO3 v13 and v14 we recommend the site set — and if you use it, do not
    press the backend button :guilabel:`Create a root TypoScript record` on that
    site. The :sql:`sys_template` record it creates carries the flag
    :guilabel:`Clear` for constants and setup, and that flag discards everything
    the site sets contributed. An installation that is already in that state
    gets its configuration back by selecting the static templates below in that
    very record.

..  _static-typoscript:

Include static TypoScript
-------------------------

Edit the :sql:`sys_template` record of the site root and add the entry to
:guilabel:`Include static (from extensions)`:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Programs: Program List (academic_programs)`
        -   The TypoScript of the :guilabel:`Program List` content element.
    *   -   :guilabel:`Academic Programs: Program Details (academic_programs)`
        -   The same for :guilabel:`Program Details`.
    *   -   :guilabel:`Academic Programs: All components (academic_programs)`
        -   Every component this extension ships, in one entry.
    *   -   :guilabel:`Academic Programs: Shared plugin settings and page
            rendering (academic_programs)`
        -   The shared :typoscript:`plugin.tx_academicprograms` block and the
            :typoscript:`page` object of the page type, on their own. This is
            the entry an installation stored before the configuration was cut
            per component, and it keeps working — but it does not make any
            content element selectable, which the page TSconfig below does.

..  _static-pagetsconfig:

Include static page TSconfig
----------------------------

Edit the page record of the site root, tab :guilabel:`Resources`, field
:guilabel:`Page TSconfig`, and add the entry:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Programs: Program List (academic_programs)`
        -   Makes the :guilabel:`Program List` content element selectable, and
            configures its entry in the new content element wizard.
    *   -   :guilabel:`Academic Programs: Program Details (academic_programs)`
        -   The same for :guilabel:`Program Details`.
    *   -   :guilabel:`Academic Programs: All components (academic_programs)`
        -   Every component this extension ships, in one entry.

The setting is inherited by every page below the one it is set on.

..  _one-mechanism-per-site:

Do not combine both
===================

A site that uses the site set **and** the static template reads the shipped
files twice. The site set is applied before the :sql:`sys_template` record, so
the second read happens after the site settings and after
:file:`config/sites/<site>/constants.typoscript` — and it resets every constant
the extension ships a default for back to that default. For this extension that
is the :typoscript:`plugin.tx_academicprograms` constants block: the three Fluid
root paths, the page layout and the list page of the program page, the two
facts lists and the filter types of the program list.

Nothing else is damaged: the :guilabel:`Constants` and :guilabel:`Setup` fields
of the :sql:`sys_template` record, the page TSconfig of a page and the page
TSconfig files selected on a page are all applied afterwards and still win. Use
one mechanism per site and the question does not arise.

..  toctree::
   :maxdepth: 5
   :titlesonly:

   RouteEnhancers/Index
