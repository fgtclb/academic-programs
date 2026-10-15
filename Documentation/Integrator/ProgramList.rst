..  _integrator-program-list:

===========
Course List
===========

..  image:: /Images/Icons/programs.svg
    :width: 48px
    :alt: Icon of the Course List content element

:CType:
    ``academicprograms_programlist``

:Plugin:
    `AcademicPrograms`, plugin `ProgramList`, action
    :php:`ProgramController::listAction()`

:Set:
    `fgtclb/academic-programs-program-list`, included in
    `fgtclb/academic-programs`

The :guilabel:`Course List` shows the program pages of a storage as cards: the
title of each program, linked to its page, and the facts the site setting
:typoscript:`plugin.tx_academicprograms.card.fields` names, the degree unless a
site names others (see :ref:`program-facts`). Above the cards the visitor finds
a sorting and one select per category type, and the list follows a change of
either without reloading the page. A list that finds nothing says
"No programs found."

It is the element for the overview page of the programs of a faculty or of the
whole institution. With its filter and sorting hidden and a few default
categories it is a fixed list instead, all master programs on a landing page
for example. For a compact entry on the home page that leads to such a list,
use the :ref:`Course Finder <integrator-program-finder>`.

..  figure:: /Images/Screenshots/program-list-default.png
    :alt: A form with sorting and category selects and a "More filters" toggle above the number of programs found and a grid of program cards, each with title and degree
    :class: with-border with-shadow

    The Course List with its sorting, the first four filters and the program
    cards.

A program is a page of the page type :guilabel:`Academic program`. The list
finds these pages, not records in a folder, and links every card to the page.
The :ref:`Course Data <integrator-program-details>` element shows the facts of
a program on its own page, and the header of a program page links back to the
list page the site setting :typoscript:`plugin.tx_academicprograms.page.listPid`
names, see :ref:`program-page-layout`.

The element renders the template :file:`Program/List.html` with the partials
below :file:`Program/`, among them :file:`SortingAndFilters.html` for the form
and :file:`Item.html` for a card. :ref:`styling-list` shows the markup. The
list is rendered on every request rather than from the page cache, because the
programs it shows depend on the selection in the URL and on program pages
elsewhere in the page tree. Speaking URLs for a filtered list are described in
:ref:`configuration-route-enhancers`.

..  _integrator-program-list-configuration:

Settings
========

The tab :guilabel:`Configuration` of the content element holds every option.

..  confval:: Hide category filtering
    :name: program-list-hide-filter
    :type: checkbox
    :Default: off

    Leaves out the category selects of the form, together with the active
    filter tags and the reset link of :ref:`configuration-active-filters`. The
    visitor can no longer change the categories, and the list shows what
    :confval:`Default categories <program-list-categories>` presets.

    The categories are preset only for a request without a selection of the
    visitor. While the sorting stays visible, a visitor who changes it gets
    the list without the default categories. Hide the sorting as well when the
    default categories must hold.

..  confval:: Filter types
    :name: program-list-filter-types
    :type: select, several, ordered
    :Default: empty

    The category types the form offers a select for, in the order chosen
    here. Empty, the site setting
    :typoscript:`plugin.tx_academicprograms.filter.categoryTypes` decides, and
    without that every category type with a category. See
    :ref:`program-list-filter-types` below.

..  confval:: Include subcategories
    :name: program-list-include-subcategories
    :type: checkbox
    :Default: off

    A selected category finds the programs that carry the category itself or
    one of its subcategories, and the filter offers a category as soon as a
    listed program carries one of its subcategories. See
    :ref:`program-list-subcategories` below.

..  confval:: Hide sorting
    :name: program-list-hide-sorting
    :type: checkbox
    :Default: off

    Leaves out the two sorting selects, :guilabel:`Sorting field` and
    :guilabel:`Sorting direction`. The list is then sorted as
    :confval:`Default sorting <program-list-sorting>` says, also after the
    visitor filtered it. With the filter hidden as well, the element renders no
    form at all.

..  figure:: /Images/Screenshots/program-list-preset.png
    :alt: The number of programs found above a grid of program cards, without any form
    :class: with-border with-shadow

    A Course List with its filter and its sorting hidden renders no form,
    only the number of programs found and the cards. The element of the
    development instance shows hidden records, so the hidden Certificate
    program is among them.

..  confval:: Default sorting
    :name: program-list-sorting
    :type: select
    :Default: Title A-Z

    The order of the list until the visitor picks another one:

    *   :guilabel:`Title A-Z`, :guilabel:`Title Z-A`: by the title of the
        program page.
    *   :guilabel:`Last updated (latest first)`,
        :guilabel:`Last updated (latest last)`: by the date in the field
        :guilabel:`Last Update` of the page properties, which an editor sets
        by hand. Pages without that date come together, first in
        :guilabel:`latest last` and last in :guilabel:`latest first`.
    *   :guilabel:`Page sorting (Result may be unexpected)`,
        :guilabel:`Page sorting, reversed (Result may be unexpected)`: by the
        position of each page among its sibling pages in the page tree. That is
        the order of the page tree only while all program pages share one
        parent page. Program pages of several parents are mixed by their
        sorting values, which follow no visible order.

    A filter submitted while the sorting selects are hidden keeps this order.

    Only the items of the select are supported. A value that is not a field and
    a direction, an empty one or a field alone for example, which the form never
    stores but an import or a TypoScript value for a content element without
    the field can produce, is ignored, and the list sorts by title, A to Z. Of
    a field and a direction, an unknown one is ignored on its own and the other
    one still applies, so `uid desc` sorts by title, Z to A.

..  confval:: Default categories
    :name: program-list-categories
    :type: category tree
    :Default: empty

    Categories of the group `programs` the list is filtered by until the visitor
    selects categories of their own. A program has to carry every category
    selected here, so two categories of the same type find only the programs
    that carry both. Select at most one category per category type unless that
    is meant. With :confval:`Include subcategories
    <program-list-include-subcategories>` switched on, each of them also finds
    the programs that carry one of its subcategories.

    The selects of the form show the default categories as selected, and the
    active filter tags show them as tags the visitor can remove. A selection
    the visitor submits, in the form or through the
    :ref:`Course Finder <integrator-program-finder>`, replaces them. The tree
    starts at the categories the site setting
    :typoscript:`plugin.tx_academicprograms.categoryRootUids` names, see
    :ref:`configuration-category-tree-root`.

..  figure:: /Images/Screenshots/program-list-filtered.png
    :alt: The Degree select set to Bachelor, a tag "Bachelor" with a remove cross, a "Reset all filters" link, the number of programs found and the matching cards
    :class: with-border with-shadow

    The list filtered by a degree, with its tag, the reset link and the
    number of programs found. With Include subcategories, Bachelor also
    finds the programs of the degree Bachelor of Engineering below it.

..  confval:: Show hidden records
    :name: program-list-show-hidden-records
    :type: checkbox
    :Default: off

    Lists hidden program pages as well, to every visitor, whatever the preview
    settings of the backend user say. Only the hidden state of a page is
    ignored: a start or end date, an access restriction to frontend user
    groups and a deleted page still keep a program out of the list. Use it for
    a list on an access restricted page that previews programs before they go
    online.

..  confval:: Startingpoint
    :name: program-list-pages
    :type: page
    :Default: empty

    The pages below which the list looks for program pages. A program page is
    listed when its parent page is one of the pages selected here, or one of
    their subpages down to the depth of :confval:`Recursive
    <program-list-recursive>`. Select the page or folder the program pages live
    in.

    Empty, the list shows every program page of the installation, on every
    site. Only pages of the type :guilabel:`Academic program` are listed,
    whatever else lies below the selected pages.

..  confval:: Recursive
    :name: program-list-recursive
    :type: select
    :Default: 0 levels (only selected page)

    How many levels below the :guilabel:`Startingpoint` the list looks. With
    `0 levels` it lists the program pages directly below the selected pages,
    with `Infinite` every program page anywhere below them. Choose more levels
    when the program pages are sorted into a page or folder per faculty.

    The levels are read as a visitor sees the page tree. The list does not look
    below a hidden page or folder, and :confval:`Show hidden records
    <program-list-show-hidden-records>` does not change that.

..  _program-list-filter-types:

Filter types
============

The filter form offers one select per category type of the group `programs`.
Which types it offers, and in which order, is set in two places:

..  list-table::
    :header-rows: 1

    *   -   Where
        -   Applies to
    *   -   Field :guilabel:`Filter types` of the :guilabel:`Course List`
            content element, tab :guilabel:`Configuration`
        -   That element. The editor picks the types and orders them.
    *   -   Site setting / constant
            :typoscript:`plugin.tx_academicprograms.filter.categoryTypes`,
            default empty
        -   Every program list of the site whose field is empty. A comma
            separated list of type identifiers, for example
            `degree,location,program_type`, see
            :ref:`configuration-filter-settings`.

The field is listed in :typoscript:`ignoreFlexFormSettingsIfEmpty`, so an empty
field keeps the site setting rather than replacing it with an empty value. With
both empty the form offers every category type of the group that has a
category, in the order of the category types of the group. That is what it
offered before the setting existed.

A type is offered only when at least one category of that type exists. A
category of an offered type that no listed program carries is still offered,
as a disabled option, unless the site hides options without results, see
:ref:`configuration-filter-settings`. A type the project removed from the group
later is ignored, and so is an identifier that is no type of the group. The
field only offers the types the group has, including those a project adds in
its own :file:`Configuration/CategoryTypes.yaml`.

The filter types decide what the form offers, not what the list accepts: a
link that filters by a category of a type the form does not offer still
filters the list.

The field is labelled with the title of each type. A type a project adds shows
the title its :file:`CategoryTypes.yaml` gives it, and the filter select in the
frontend is labelled with :xml:`sys_category.programs.<identifier>` when the
extension or the site has that label, and with the registered title otherwise,
see :ref:`configuration-labels-category-types`.

The partial :file:`Program/DemandCategories.html` renders the selects from the
variable :html:`{filterTypes.visible}`, the identifiers of the offered types in
their order. Where that variable does not reach the partial, in a template that
renders the partial with arguments of its own instead of :html:`{_all}`, it
offers every type with a category, as before, and the field and the setting
have no effect. To use them, pass :html:`filterTypes` on in a template that
renders the partial. A project that overrides the partial itself and still
loops :html:`{categories.allCategoriesByType}` keeps its own list as well.

The :guilabel:`Course Finder` reads the same setting when its own field is
empty, see :ref:`program-finder`.

..  versionadded:: 3.0

    The field :guilabel:`Filter types` of the content element.

..  _program-list-subcategories:

Matching subcategories
======================

Categories of the group `programs` can form a tree, a degree for example:

..  code-block:: text
    :caption: A degree tree

    Bachelor
        Bachelor of Science
            Bachelor of Science with Honours
        Bachelor of Engineering
    Master
        Master of Science

By default a selected category finds only the programs that carry that very
category. A visitor who filters by "Bachelor" does not find a program that
carries only "Bachelor of Science", and the filter offers "Bachelor" as a
disabled option as long as no listed program carries it.

The field :guilabel:`Include subcategories` of the :guilabel:`Course List`
content element, tab :guilabel:`Configuration`, off by default, changes both:

*   A selected category finds every program that carries the category itself
    or a category anywhere below it. That applies to a category the visitor
    selects in the filter and to the :guilabel:`Default categories` of the
    element.
*   The filter offers a category as soon as a listed program carries it or one
    of its subcategories. With
    :typoscript:`plugin.tx_academicprograms.filter.hideDisabledOptions` set,
    such a category is kept as well.

Several selections still all have to match: "Bachelor" and "Berlin" find the
programs with a Bachelor degree of any kind in Berlin, not every Bachelor
program and every program in Berlin.

Only categories the visitor can see take part. A hidden category, a category of
a type outside the group `programs`, and everything below either of them are
not part of the tree. A category that names one of its own subcategories as its
parent does not break the list: each of the two finds the programs of the
other.

With the field switched on, assign each program only its most specific
category, "Bachelor of Science" rather than "Bachelor of Science" and
"Bachelor". The parent no longer has to be assigned for the filter, and no
longer shows up in the facts of the program.

The :guilabel:`Course Finder` has the same field for the options it offers,
see :ref:`program-finder`. The finder does not decide which programs are
found, the list on its target page does. Switch the field on in both elements.

..  versionadded:: 3.0

    The field :guilabel:`Include subcategories`. Up to 2.x a category matched
    only itself, and projects assigned both levels and hid the parent in the
    program output again.

..  _program-list-in-place:

Updating the list without a reload
==================================

When a visitor changes a filter or the sorting of the
:guilabel:`Course List`, the list updates in place: the results, the filter
form, the active filters and the result count are replaced by the ones of the
filtered list, without reloading the page. There is no setting for it.

*   The address bar shows the URL of the filtered list afterwards, the same URL
    a reload would show, see :ref:`feature-1790226102`. The list can still be
    bookmarked, shared and reloaded, and the back and forward buttons of the
    browser step through the selections.
*   The page shows what a reload of that URL shows. All program lists of a page
    read the selection from the same URL arguments, so with two lists on a page
    a change in one of them filters both, with a reload as without one. That
    holds for a list that hides its filter and its sorting as well.
*   The select the visitor changed keeps the focus, and an opened
    :guilabel:`More filters` stays open.
*   Screen reader users hear the number of programs found after an update,
    through a visually hidden element with the role `status` and the classes
    `ace-status visually-hidden`. It reads the labels
    :xml:`list.resultCount.singular` and :xml:`list.resultCount.plural`, and
    loading the page announces nothing. The element is hidden by the class
    `visually-hidden` of Bootstrap, so a site without Bootstrap provides that
    class itself.
*   Without JavaScript the form shows a submit button with the label
    :xml:`list.submit`, and submitting it opens the filtered list. With
    JavaScript the column of the button gets the attribute :html:`hidden`. A
    stylesheet that gives that column a `display` of its own needs
    :css:`[hidden] { display: none }` as well, as Bootstrap has it.
*   Where the list cannot be updated in place, because the request fails or the
    answer lacks the list, the form is submitted as before and the page reloads.
*   The selects carry no inline :html:`onchange` handler any more, so a site
    with a strict content security policy no longer has to allow inline event
    handlers for the list.

The module is :js:`@fgtclb/academic-programs/frontend/program-list.js`, loaded
on a page whose list renders a filter or a sorting. A site that brings its own
script switches it off, see :ref:`configuration-javascript`. The module finds
its parts by attributes, so an override of the templates keeps the update in
place by keeping them:

..  list-table::
    :header-rows: 1

    *   -   Attribute
        -   On
        -   Carries
    *   -   `data-academic-programs-list`
        -   The wrapper of the list in :file:`Program/List.html`
        -   The uid of the content element, which finds the same list in the
            filtered page.
    *   -   `data-academic-programs-list-count-one`, `data-academic-programs-list-count-other`
        -   The wrapper
        -   The sentences announced after an update, `%d` standing for the
            number. Without both of them nothing is announced.
    *   -   `data-academic-programs-list-content`
        -   An element inside the wrapper that holds the form and the results
        -   Nothing. It is the part that is replaced.
            `data-academic-programs-list-total` on the same element carries the
            number of programs it shows.
    *   -   `data-academic-programs-list-status`
        -   An empty element with `role="status"` inside the wrapper, outside
            the replaced part
        -   Nothing. The sentence is written into it after an update.
    *   -   `data-academic-programs-list-form`
        -   The form in :file:`Program/SortingAndFilters.html`
        -   Nothing. A form outside the replaced part is submitted on a change,
            and the page reloads.
    *   -   `data-academic-programs-list-select`
        -   Each select in :file:`Program/DemandCategories.html` and
            :file:`Program/DemandSorting.html`
        -   Nothing. Like the form attribute, for a form that carries neither
            the attribute nor a place in the replaced part.
    *   -   `data-academic-programs-list-submit`
        -   The column around the submit button
        -   Nothing. The module hides it.

The template, the form partial and the two filter partials each load the
module, so that it runs whichever of them a project overrides. It is loaded
once. A template override from before 3.0 keeps working:

*   An override of :file:`Program/DemandCategories.html` or
    :file:`Program/DemandSorting.html` that keeps the inline :html:`onchange`
    handlers reloads the page on a change of its selects, as before. The
    module leaves those selects to their handlers and drives the others, so
    the selects of the partial that is not overridden keep working. It hides
    the submit button either way.
*   An override of :file:`Program/List.html` without the wrapper reloads the
    page on a change.
*   An override of :file:`Program/SortingAndFilters.html` is updated in place,
    as long as :file:`Program/List.html` is the one of the extension. It has no
    submit button for a visitor without JavaScript until it renders one.
*   An override of both :file:`Program/List.html` and
    :file:`Program/SortingAndFilters.html` reloads the page on a change,
    through the attribute of the selects of the filter partials.

..  versionadded:: 3.0
