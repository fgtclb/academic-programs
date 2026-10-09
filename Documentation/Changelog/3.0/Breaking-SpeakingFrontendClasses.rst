..  _breaking-programs-speaking-frontend-classes:

==================================================
Breaking: Program templates carry speaking classes
==================================================

Description
===========

The frontend templates of this extension carried the classes of the theme they
were developed with - cards, list groups, form controls, spacing and display
utilities - next to block and element classes of the extension. They carry
speaking classes now, named after what an element is and shared by all academic
extensions: `ace-item`, `ace-list`, `ace-attribute`, `ace-label`, `ace-value`,
`ace-link` and so on. The outermost element of a plugin keeps the class of the
plugin. The classes of Bootstrap that a site builds on stay on purpose where
the templates render them: `container` on the outermost element of a page of
the page type, the grid classes `row` and `col-*`, the button classes `btn` and
`btn-*` and `visually-hidden`. No other theme class is rendered any more, and
the extension ships no styles for the new classes.

The header partials take the class of the heading as the argument `class`,
in place of `positionClass`. An override that renders one of them with
`positionClass` renders the heading without that class.

The facts partial :file:`Program/Facts.html` no longer takes the arguments
`listClass` and `itemClass`. The list and its items carry the same classes on
the program page, in the details element and on the card.

..  list-table::
    :header-rows: 1

    *   -   Element
        -   Before
        -   After
    *   -   Item list
        -   `row academic-programs-itemlist`
        -   `ace-itemlist` around the `row`
    *   -   Item
        -   `card flex-column-reverse academic-programs-item mt-4` with `card-content` and `card-body`
        -   `<article class="ace-item">` with `ace-item-content`
    *   -   Attributes of an item
        -   `list-group list-group-flush` with `list-group-item` entries
        -   `ace-list ace-attributes` with `ace-list-item ace-attribute` entries, `ace-icon`, `ace-label` and `ace-value` inside
    *   -   No result
        -   a plain `<span>`
        -   `ace-empty`
    *   -   Filter form
        -   `academic-programs-filtersorting`
        -   `ace-form`, the fields in an `ace-filters`
    *   -   Filter cell
        -   the grid column only
        -   `ace-filter ace-field ace-select-wrap` inside the column
    *   -   Label and select
        -   `form-label`, `form-select`
        -   `ace-label`, `ace-control ace-select`
    *   -   More filters
        -   `<details class="col-12 academic-programs-more-filters">`
        -   `<details class="ace-more">` inside a `col-12`, with `<summary class="ace-toggle">`
    *   -   Result count
        -   `academic-programs-result-count`
        -   `ace-count`
    *   -   Active filters
        -   `academic-programs-active-filters` with `__tags`, `__tag`, `__remove`
        -   `<ul class="ace-list ace-active-filters">` with `ace-list-item ace-active-filter` entries, the remove mark `ace-icon`
    *   -   Reset link
        -   `academic-programs-active-filters__reset`
        -   `ace-link`
    *   -   Page
        -   `academic-programs-detail container`
        -   `academic-programs-page container`
    *   -   Header and media
        -   `d-flex flex-column-reverse`
        -   `ace-hero`
    *   -   Page header
        -   `academic-programs-detail__header`
        -   `<header class="ace-header">` with `ace-title` on the heading
    *   -   Subtitle
        -   `academic-programs-detail__subtitle`
        -   `ace-subtitle`
    *   -   Facts
        -   `academic-programs-facts`, `academic-programs-facts__item academic-programs-facts__item--<identifier>`
        -   `ace-list ace-attributes`, `ace-list-item ace-attribute` with :html:`data-academic-programs-fact="<identifier>"`, the value `ace-value`
    *   -   Back link of the page
        -   `academic-programs-detail__back` inside the header
        -   `ace-link`, after the `ace-hero` of the page template
    *   -   Application link
        -   `btn btn-primary` inside `<p class="academic-programs-application">`
        -   `btn btn-primary ace-apply`, without the paragraph
    *   -   Details element
        -   `academic-programs-detail-categories`
        -   `academic-programs-detail`
    *   -   Program finder
        -   `academic-programs-finder` on the form, `row align-items-end` around the selects
        -   `<div class="academic-programs-finder">` around the form `ace-form`, the selects in an `ace-filters` and named like the filter cells above
    *   -   Status of the list and the finder
        -   `visually-hidden`
        -   `ace-status visually-hidden`

Impact
======

*   A site stylesheet that styles the program list, the filters, the finder,
    the facts or the program page through the classes of the column *Before*
    no longer matches.
*   An override that renders :file:`Program/Facts.html` with `listClass` or
    `itemClass` renders the facts without those classes.

Affected Installations
======================

Every installation that renders the program list, the program finder, the
program details element or a page of the program page type.

Migration
=========

#.  Move the selectors of the site stylesheet from the classes in the
    column *Before* to those in the column *After*. A site that styled the
    plugins through the classes of its Bootstrap theme alone gives the new
    classes the look it wants, for example with :css:`@extend` in SCSS or
    with rules of its own.
#.  A template override is compared with the shipped template of 3.0 and
    adopts its markup. An override keeps working as it is, but renders its
    own classes, and nothing in the extension relies on them.
#.  Drop `listClass` and `itemClass` from an override that renders
    :file:`Program/Facts.html`, and replace `positionClass` with `class`
    where an override renders :file:`Program/Header.html`.
#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, ext:academic_programs
