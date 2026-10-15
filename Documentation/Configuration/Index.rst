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

The content elements and the options of their backend forms are described in
:ref:`integrator`.

..  _configuration-components:

What the sets contain
=====================

This extension ships three content elements, so it ships three component sets
and one aggregate set that depends on all of them.

The content elements are Extbase plugins of one extension, so they share one
TypoScript block, :typoscript:`plugin.tx_academicprograms`. That block is
shipped once, in :file:`Configuration/TypoScript/`, and every component includes
it. Which component sets a site names therefore decides which content elements
the backend offers, not how much TypoScript is loaded.

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-programs-program-list`
        -   The :ref:`Course List <integrator-program-list>` content element.
    *   -   `fgtclb/academic-programs-program-details`
        -   The :ref:`Course Data <integrator-program-details>` content
            element.
    *   -   `fgtclb/academic-programs-program-finder`
        -   The :ref:`Course Finder <integrator-program-finder>` content
            element.
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

:guilabel:`EXT:academic_programs` hides its content elements for the
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

The section :html:`Main` renders five partials, each of which can be replaced
on its own:

..  list-table::
    :header-rows: 1

    *   -   Partial
        -   Renders
    *   -   :file:`Program/Page/Header.html`
        -   The title and the subtitle, in one element
            :html:`<header class="ace-header">`. The page template renders the
            link back to the program list after it.
    *   -   :file:`Program/Page/Media.html`
        -   The first image of the page, through the shared image partial of
            :guilabel:`EXT:academic_base`.
    *   -   :file:`Program/Page/CallToAction.html`
        -   The application link, see :ref:`program-application-link`.
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

..  _program-application-link:

The application link of a program page
======================================

The :guilabel:`Program` tab of a program page offers two fields after the
credit points:

..  list-table::
    :header-rows: 1

    *   -   Field
        -   Column
        -   Meaning
    *   -   :guilabel:`Application link`
        -   `application_link`
        -   A page of the installation or an external URL, picked in the link
            browser. Without it, the page shows no application link.
    *   -   :guilabel:`Label of the application link`
        -   `application_link_label`
        -   Up to 60 characters. Without a label, the link reads "Apply now",
            translated into the language of the page.

Both fields belong to the program page type alone, and both are translated
with the page, so a translated program page can link a translated application
form.

The partial :file:`Program/Page/CallToAction.html` renders the link as a
button after the header, the image and the link back to the list, before the
facts. It is a partial
of its own rather than part of the header, so an override of
:file:`Program/Page/Header.html` keeps the link. A link whose target cannot be
linked, such as a hidden page, renders nothing, the label included. A page
without a translation counts as such only in a language that does not fall
back to another one.

Templates reach both values as :html:`{program.applicationLink}` and
:html:`{program.applicationLinkLabel}`, on the program page and in a list item
of the :guilabel:`Course List`. The label is empty where the editor left it
empty. The default list item does not render the link. An override of
:file:`Program/Item.html` renders the partial of the program page, which needs
nothing but the program, and gets the same button with the same rules:

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Partials/Program/Item.html

    <f:render partial="Program/Page/CallToAction" arguments="{program: program}" />

The link carries the classes `btn btn-primary ace-apply` in both places,
without an element around it. The button classes of Bootstrap stay on
purpose, and `ace-apply` tells the link from the link back to the list and
from other buttons. An override of :file:`Program/Page/CallToAction.html`
changes the button of the program page and of every list item that renders
it.

..  _program-facts:

The facts of a program
======================

The program page, the :guilabel:`Course Data` content element and each
card of the :guilabel:`Course List` show facts about a program: its
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
        -   the program page and the :guilabel:`Course Data` content
            element
    *   -   :typoscript:`plugin.tx_academicprograms.card.fields`
        -   `degree`
        -   each card of the :guilabel:`Course List`

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
    extension, and a type that has none there is named by the title it is
    registered with (see :ref:`configuration-labels-category-types`);
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
        -   The list, :html:`<ul class="ace-list ace-attributes">`, from
            :html:`{facts}`.
    *   -   :file:`Program/Facts/Item.html`
        -   One fact, :html:`{fact}`: an icon if it has one, the label and the
            categories or the value, in an element with the class
            :html:`ace-value`. A rich text value is rendered as it is stored,
            and its element gets the class :html:`ce-bodytext` as well, any
            other value escaped, with its line breaks kept.

:html:`{fact}` carries :html:`identifier`, :html:`labelKey` (a key of
:file:`locallang.xlf` of this extension), :html:`iconIdentifier` (empty for a
fact without an icon), :html:`isCategoryType`, :html:`categories` for a
category type, :html:`value` for a program field and :html:`isRichText`.

..  _program-facts-rich-text:

:html:`isRichText` is true for the job profile, the performance scope and the
prerequisites while their field has the rich text editor on program pages,
which it has as shipped. It follows the TCA of :sql:`pages` for the program page
type, so a site package that switches the editor off, for the field or in the
``columnsOverrides`` of the program page type only, changes the output without
a template of its own:

..  code-block:: php
    :caption: EXT:my_sitepackage/Configuration/TCA/Overrides/pages.php

    <?php

    use FGTCLB\AcademicPrograms\Enumeration\PageTypes;

    $GLOBALS['TCA']['pages']['types'][PageTypes::TYPE_ACADEMIC_PROGRAM]
        ['columnsOverrides']['job_profile']['config']['enableRichtext'] = false;

The job profile is then a plain text field on program pages, and the facts show
what an editor typed there as text: an ampersand or an angle bracket is shown
as it is, and a line break becomes a line break. Credit points and category
type facts are never rich text.

..  versionchanged:: 3.0

    The facts mark rich text and escape every other value, see
    :ref:`feature-programs-facts-know-rich-text`.

:file:`Program/Facts/Item.html` renders the icon with the ``ab:icon``
ViewHelper of :guilabel:`academic_base`, from its frontend icon registry. The
credit points fact has the icon `tx-academicprograms-info-credit-points`, which
this extension registers in its :file:`Configuration/FrontendIcons.php`. A
category type fact has the icon of its type, `category_types.programs.<type>`,
which :guilabel:`category_types` registers from
:file:`Configuration/CategoryTypes.yaml`: the ``frontendIcon`` of the type when
it declares one, its ``icon`` otherwise. The other three program fields have
no icon.

A site package replaces one of these icons for the frontend by registering its
identifier in its own :file:`Configuration/FrontendIcons.php`, which has the
format of :file:`Icons.php`. The site package has to depend on
:guilabel:`academic_programs`, so its entry is read after the shipped one:

..  code-block:: php
    :caption: EXT:my_sitepackage/Configuration/FrontendIcons.php

    <?php

    use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

    return [
        'tx-academicprograms-info-credit-points' => [
            'provider' => CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/CreditPoints.svg',
        ],
        'category_types.programs.degree' => [
            'provider' => CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/Degree.svg',
        ],
    ];

A replacement in :file:`Configuration/Icons.php` does not reach the facts, and
a replaced category type icon keeps its declared drawing in the backend.

..  versionchanged:: 3.0

    The credit points icon is a frontend icon and the facts render their icons
    from the frontend icon registry, see
    :ref:`breaking-programs-credit-points-icon-is-a-frontend-icon`.

A template of your own gets the facts from the variable :html:`{facts}` on the
program page and in the details content element. Anywhere else, the view
helper builds them for a program:

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Partials/Program/Item.html

    <html xmlns:ace="http://typo3.org/ns/FGTCLB/AcademicPrograms/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <f:variable name="cardFacts"
                value="{ace:program.facts(program: program, fields: settings.card.fields, mostSpecificOnly: settings.facts.mostSpecificOnly)}" />
    <f:render partial="Program/Facts" arguments="{facts: cardFacts}" />

    </html>

..  versionchanged:: 3.0

    Up to 2.x the facts were fixed in the templates, and the partial
    :file:`Program/Categories.html` rendered the categories. See
    :ref:`breaking-program-categories-partial-removed`.

..  _program-facts-most-specific-category:

Only the most specific category
-------------------------------

Editors often assign a parent category together with its subcategory, "Bachelor"
and "Bachelor of Science" for example, so that a filter on the parent finds the
program. The facts then show both. The setting
:typoscript:`plugin.tx_academicprograms.facts.mostSpecificOnly` leaves a
category out when the program carries a descendant of it of the same category
type as well:

..  list-table::
    :header-rows: 1

    *   -   Site setting / constant
        -   Default
        -   Applies to
    *   -   :typoscript:`plugin.tx_academicprograms.facts.mostSpecificOnly`
        -   `false`
        -   the program page, the :guilabel:`Course Data` content element
            and each card of the :guilabel:`Course List`

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicprograms:
        facts:
          mostSpecificOnly: true

*   Only the categories assigned to the program are compared. A level that is
    not assigned breaks the line: with "Bachelor" and a subcategory two levels
    below it assigned, but not the level between them, both are shown.
*   A parent of another category type is shown, under its own type.
*   A program that carries the parent alone shows the parent.
*   Filters are not affected. The list and the finder find a program by every
    category it carries, and offer the same options with the setting on.

The view helper takes the setting as its argument :html:`mostSpecificOnly`, see
above.

..  _configuration-filter-settings:

The filters of the program list
===============================

The filter form of the :guilabel:`Course List` offers one select per category
type of the group `programs`. Which types an element offers is its field
:guilabel:`Filter types`, see :ref:`program-list-filter-types`. Three settings
decide the filters for the whole site:

..  list-table::
    :header-rows: 1

    *   -   Site setting and constant
        -   Default
        -   Meaning
    *   -   :typoscript:`plugin.tx_academicprograms.filter.categoryTypes`
        -   empty
        -   The category types every program list and finder of the site
            offers while its field :guilabel:`Filter types` is empty, in this
            order, as a comma separated list of type identifiers, for example
            `degree,location,program_type`. Empty, the list offers every type
            with a category, the finder the degree and the topic.
    *   -   :typoscript:`plugin.tx_academicprograms.filter.visibleCount`
        -   0
        -   How many filters the form shows right away. The others follow in a
            :guilabel:`More filters` section the visitor opens, which is open
            already while one of its filters has a value. 0 shows every filter.
            The finder always shows all of its selects.
    *   -   :typoscript:`plugin.tx_academicprograms.filter.hideDisabledOptions`
        -   0
        -   Leaves out a category no listed program carries, instead of
            offering it as a disabled option — in the finder, one no program
            in its storage carries. A selected category is always offered. A
            filter whose categories are all left out still renders, with its
            "All" option only.

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicprograms:
        filter:
          categoryTypes: 'degree,location,program_type'
          visibleCount: 2
          hideDisabledOptions: true

The settings are site settings of the aggregate set `fgtclb/academic-programs`
and constants of the same name for a site on the static templates, like the
settings of :ref:`program-page-layout`. A site that depends on the component
set `fgtclb/academic-programs-program-list` alone gets the defaults, but the
site settings editor does not offer the settings there.

:html:`{filterTypes.more}` holds the types behind :guilabel:`More filters`.
Without :html:`{filterTypes}` the partial ignores the visible count as it
ignores the filter types.

The first option of each select, in the list and in the finder, the one that
selects no category, reads the label
:xml:`sys_category.programs.allOptions.<type>` of this extension, and
falls back to :xml:`sys_category.programs.allOptions` ("All options") where a
type has none. The extension ships no label per type. A site adds them in
TypoScript, for every content element of the extension or for one plugin:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    plugin.tx_academicprograms._LOCAL_LANG {
      default.sys_category.programs.allOptions.degree = All degrees
      de.sys_category.programs.allOptions.degree = Alle Abschlüsse
    }

    # Only in the program finder:
    plugin.tx_academicprograms_programfinder._LOCAL_LANG.default.sys_category.programs.allOptions.degree = All degrees

A language file override works as well, as for any label of this extension:
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` on TYPO3 v13,
:php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` on TYPO3 v14,
each pointing from
:file:`EXT:academic_programs/Resources/Private/Language/locallang.xlf` to a file
of the site package.

..  versionadded:: 2.4

    The site setting of the filter types, the visible count, the options
    without results and the "All" label per type. Up to 2.3 the form offered
    every type with a category, all of them right away and with one "All"
    label, and anything else needed an override of the partial.

..  _configuration-active-filters:

Active filters, reset link and result count
===========================================

Three switches add to the filter form of the
:guilabel:`Course List`, for the whole site. All
three are off by default.

..  list-table::
    :header-rows: 1

    *   -   Site setting and constant
        -   Default
        -   Meaning
    *   -   :typoscript:`plugin.tx_academicprograms.filter.showActiveFilters`
        -   0
        -   One tag per selected category, below the form. Each tag
            links to the list without that selection, every other selection
            and the sorting kept.
    *   -   :typoscript:`plugin.tx_academicprograms.filter.showReset`
        -   0
        -   A :guilabel:`Reset all filters` link to the page without any list
            argument, so the list shows what the content element presets.
            Offered after the visitor selected something, while a category
            is selected or preset by the content element, also once the
            visitor removed the preset. On the page as the editor preset it,
            the link would lead to the page shown.
    *   -   :typoscript:`plugin.tx_academicprograms.filter.showResultCount`
        -   0
        -   The number of programs found, for example "12 programs found".

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicprograms:
        filter:
          showActiveFilters: true
          showReset: true
          showResultCount: true

The settings are site settings of the aggregate set `fgtclb/academic-programs` and constants of
the same names, like the filter settings above.

*   A tag shows a category in the language of the page.
*   A tag stays a tag when the editor preset its category: removing it shows the
    list without that category, and the reset link brings the preset back.
*   Where the content element hides the filter, the visitor cannot change it, and
    neither tags nor the reset link are shown. The count is.
*   Every selected category is a tag, also one whose type the form does not
    offer (see the filter types above), for example a preset one. Removing it
    works like for any other tag.
*   The tags and the reset link come from the partial
    :file:`Program/ActiveFilters.html`, the count from
    :file:`Program/ResultCount.html`. Both are rendered by
    :file:`Program/SortingAndFilters.html`, so a project that overrides that
    partial does not show them until it renders them as well. The partials use
    the classes `ace-active-filters` on the list of tags, `ace-active-filter` on
    a tag, `ace-link` on the links and `ace-count` on the count, and bring no
    styles.
*   The reset link needs the variable :html:`{visitorSelection}`, which the list
    action assigns. A template that renders the partials with arguments of its
    own has to pass it on, or the list offers no reset link.
*   The labels are :xml:`filter.activeFilters.label`,
    :xml:`filter.activeFilters.remove`, :xml:`filter.reset`,
    :xml:`list.resultCount.singular` and :xml:`list.resultCount.plural` of this
    extension. The count labels take the number as `%d`, the remove label the
    category title as `%s`.

..  versionadded:: 3.0

    The three settings and the two partials.

..  _configuration-plugin-options:

The options of the content elements
===================================

What the :guilabel:`Course List`, the :guilabel:`Course Data` and the
:guilabel:`Course Finder` show, every option of their backend forms, the
matching of subcategories and how the list and the finder work in the browser
are described in :ref:`integrator`.

..  _configuration-category-tree-root:

Category tree root
==================

Editors pick the categories of a program page, the :guilabel:`Default
categories` of the :guilabel:`Course List` and the :guilabel:`Preselected
categories` of the :guilabel:`Course Finder` from a category tree. By default
that is the whole category tree of the installation, although the program
categories are usually one branch of it.

The site setting :typoscript:`plugin.tx_academicprograms.categoryRootUids`
names the categories these three trees start at, as a comma separated list of
uids. Editors are then offered those categories and the categories below them,
on the pages of that site:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicprograms:
        categoryRootUids: '12'

One uid shows that category as the top of the tree, and it can be selected
itself. Several uids show each of them as a top level entry below the root of
the tree, which cannot be selected.

The setting is empty by default. An empty setting, a value that names no
category uid, and a page that belongs to no site keep the whole tree, exactly
as before the setting existed. Only the program page type is affected: the
categories of a standard page keep the whole tree.

The setting is read by the backend from the site configuration, so it has no
TypoScript constant, and a site on the static templates sets it in its site
configuration too. It is declared with the aggregate set
`fgtclb/academic-programs`. A site that does not depend on that set, because it
depends on a component set only or uses the static templates, is not offered
the setting in the site settings editor and writes it to its
:file:`settings.yaml` itself, **in the nested form shown above**. TYPO3 keeps a
setting that no set of the site declares only when it is written as a tree, and
a dotted key such as `plugin.tx_academicprograms.categoryRootUids: '12'` has no
effect there. The settings editor keeps such a value when it saves the other
settings of the site.

A program page may carry a category outside of the configured branch, for
example one assigned before the setting was made. The tree does not show it.
Saving the page without touching the tree keeps it, but as soon as an editor
changes the selection in the tree, the field is written from what the tree
shows, and the category is removed without notice. Set the root before the
editors start, or remove such categories first.

Page TSconfig still wins. A site that sets
:typoscript:`TCEFORM.pages.categories.config.treeConfig.startingPoints` for the
same purpose keeps that value until the line is removed.

..  versionadded:: 3.0

    Up to 2.x a project restricted the tree in its own TCA or page TSconfig,
    usually with a uid per environment. See
    :ref:`feature-1790613217` for what such a project removes.

..  _configuration-crop-variants:

The crop variants of the program page media
===========================================

The image cropper of the media of a program page offers three crop variants. A
template requests one by its name, through the `cropVariant` argument of
:html:`<f:image>` or of the image partial of :guilabel:`EXT:academic_base`:

..  list-table::
    :header-rows: 1

    *   -   Name
        -   Aspect ratio
    *   -   `default`
        -   Free, 16:9, 3:2, 4:3 and 1:1
    *   -   `landscape`
        -   16:9
    *   -   `portrait`
        -   3:4

`default` is the variant TYPO3 offers when a file field configures none, with
the same ratios, and the templates of this extension render it. A crop an editor
stored before the update is stored under that name and keeps its meaning.

The variants belong to the program page type. The media of a standard page keeps
what TYPO3 offers.

An image stores a crop for the new variants once an editor opens it in the
backend form and saves the record. Until then a template that requests
`landscape` or `portrait` renders the image uncropped. Where the image already
has a crop for `default`, the cropper starts `landscape` from that crop, fitted
into its ratio, and `portrait` from the whole image, fitted and centred; an
image without a crop starts both from the whole image.

A site that does not want a variant disables it in TCA, on this field only. The
extension configures the variants in its own TCA overrides, so the site package
has to depend on academic_programs for its line to load later:

..  code-block:: php
    :caption: Configuration/TCA/Overrides of the site package

    $GLOBALS['TCA']['pages']['types'][20]['columnsOverrides']['media']['config']['overrideChildTca']['columns']['crop']['config']['cropVariants']['portrait']['disabled'] = true;

Page TSconfig is not the way to do that.
`TCEFORM.sys_file_reference.crop.config.cropVariants` reaches every image below
the page it is set on, and on an image field that configures no variants of its
own it leaves the cropper with no variant at all, not even `default`.

A project that defines crop variants of its own for the program page media does
so at the same path. A variant it sets by name replaces the one of the same name
and leaves the others; assigning the whole array replaces all of them.

Crop variants a project configures on the media field of every page, or on
`sys_file_reference` for every image, are merged with these on the program page:
the values of this extension win key by key, and a ratio the project adds to a
variant of the same name stays. A project that restricted `default` to a fixed
ratio that way therefore finds all the ratios of the TYPO3 default offered on
program pages again, and the free ratio preselected on an image without a crop.

..  _configuration-content-element-header:

The header of the content elements
==================================

The header and the subheader an editor enters on a :guilabel:`Course List`,
:guilabel:`Course Data` or :guilabel:`Course Finder` content element are
rendered by the content element layout of the site, as for any other content
element. The layouts of :guilabel:`EXT:fluid_styled_content` and of the
bootstrap package do that, and the plugins render no header of their own.

A site whose content element layout renders no header, because its element
templates render it instead, lets the plugins render it:

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicprograms.renderContentElementHeader = 1

On a site that uses the site set, that is the site setting :guilabel:`Render the
content element header in the plugins` of `fgtclb/academic-programs`. The
templates then render the header partial of :guilabel:`EXT:fluid_styled_content`
above their output, for every header layout except :guilabel:`Hidden`. Do not
switch it on where the layout renders the header: the header then appears twice.

The extension does not require :guilabel:`EXT:fluid_styled_content`. It adds the
partial path of that extension below every other one, so a site package that
ships a :file:`Header/All.html` of its own renders that one instead, and a site
without :guilabel:`EXT:fluid_styled_content` provides the partial that way.

For the header layout :guilabel:`Default`, the partial takes the heading level
from :typoscript:`plugin.tx_academicprograms.settings.defaultHeaderType`, which
is mapped from the constant :typoscript:`styles.content.defaultHeaderType` of
:guilabel:`EXT:fluid_styled_content`. A site that does not include the
TypoScript of :guilabel:`EXT:fluid_styled_content` sets the setting itself;
without it, such a header renders as an empty :html:`<header>` element.

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
        -   The TypoScript of the :guilabel:`Course List` content element.
    *   -   :guilabel:`Academic Programs: Program Details (academic_programs)`
        -   The same for :guilabel:`Course Data`.
    *   -   :guilabel:`Academic Programs: Program Finder (academic_programs)`
        -   The same for :guilabel:`Course Finder`.
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
        -   Makes the :guilabel:`Course List` content element selectable, and
            configures its entry in the new content element wizard.
    *   -   :guilabel:`Academic Programs: Program Details (academic_programs)`
        -   The same for :guilabel:`Course Data`.
    *   -   :guilabel:`Academic Programs: Program Finder (academic_programs)`
        -   The same for :guilabel:`Course Finder`.
    *   -   :guilabel:`Academic Programs: All components (academic_programs)`
        -   Every component this extension ships, in one entry.

The setting is inherited by every page below the one it is set on.

..  _configuration-javascript:

Load the script or bring your own
=================================

Two content elements of this extension load a module on the page that carries
them: the :guilabel:`Course List`
:js:`@fgtclb/academic-programs/frontend/program-list.js`, which updates the
list in place, see :ref:`program-list-in-place`, and the
:guilabel:`Course Finder`
:js:`@fgtclb/academic-programs/frontend/program-finder.js`, which narrows the
options in the browser, see :ref:`program-finder-narrowing`. The program
details and the program page load no script.

Both are switched off together, per site, for an installation that brings its
own:

..  list-table::
    :header-rows: 1

    *   -   Site setting and constant
        -   Default
        -   Off means
    *   -   :yaml:`plugin.tx_academicprograms.assets.js`
        -   :yaml:`true`
        -   The page loads neither module.

With the site set, it is a site setting of `fgtclb/academic-programs`, like
every other setting of this extension:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin.tx_academicprograms.assets.js: false

With the static template, it is the TypoScript constant of the same name:

..  code-block:: typoscript
    :caption: Constants of the root sys_template record

    plugin.tx_academicprograms.assets.js = 0

**Both elements keep working without a script.** The filter form of the list
is submitted with its button and the page reloads with the filtered list, and
the finder submits to the list without narrowing its options first. A script of
your own finds the parts of both by the data attributes the two sections above
name.

The list template and the three partials of its form,
:file:`Program/SortingAndFilters.html`, :file:`Program/DemandSorting.html` and
:file:`Program/DemandCategories.html`, each register the list module and each
check the switch. A project that overrides one of them keeps whatever its copy
loads, until it wraps its own :html:`<f:asset.module>` in
:html:`<f:if condition="{settings.assets.js}">`.

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
facts lists, the three filter settings of the program list and the program
finder, the :ref:`content element header
<configuration-content-element-header>` switch and the
:ref:`script switch <configuration-javascript>`.

Nothing else is damaged: the :guilabel:`Constants` and :guilabel:`Setup` fields
of the :sql:`sys_template` record, the page TSconfig of a page and the page
TSconfig files selected on a page are all applied afterwards and still win. Use
one mechanism per site and the question does not arise.

..  _configuration-integration:

Search, permissions and the wizard
==================================

The `Integration chapter of academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Integration/Index.html>`__
covers what an installation runs beside the academic extensions: an index
queue for program pages with EXT:solr, the tables, fields and content types
an editor group needs as a preset for b13/permission-sets, and how to move,
rename or order the academic content elements in the new content element
wizard.

..  toctree::
   :maxdepth: 5
   :titlesonly:

   RouteEnhancers/Index
   Labels/Index
