..  _important-1790982307:

==================================================
Important: Overrides of the program list templates
==================================================

Description
===========

The program list updates in place when a filter or the sorting changes, see
:ref:`feature-1790982306`. For that, the templates of the list changed:

*   :file:`Templates/Program/List.html` wraps the list in an element with the
    uid of the content element, puts the form and the results into a region of
    their own, and renders an empty status element after it.
*   :file:`Partials/Program/SortingAndFilters.html` marks the form and renders a
    submit button in a marked column.
*   :file:`Partials/Program/DemandCategories.html` and
    :file:`Partials/Program/DemandSorting.html` no longer render
    :html:`onchange="this.form.submit()"` on their selects, and mark them
    instead.
*   All four load the module, so that it runs whichever of them a project
    overrides.

The attributes are listed in :ref:`program-list-in-place`.

Impact
======

An override from before keeps working, with a page reload where the list
cannot be updated in place:

*   An override of :file:`DemandCategories.html` or :file:`DemandSorting.html`
    that keeps the inline handlers reloads the page on a change of its selects,
    as before. The selects of the other partial are updated in place. Remove
    the handlers to get the update in place for all of them. With JavaScript,
    the submit button of the extension's :file:`SortingAndFilters.html` stays
    hidden.
*   An override of :file:`List.html` reloads the page on a change. Add the
    wrapper, the region and the status element to get the update in place.
*   An override of :file:`SortingAndFilters.html` is updated in place, because
    :file:`List.html` loads the module as well. Add the submit button inside an
    element with the attribute `data-academic-programs-list-submit`, so that a
    visitor without JavaScript can submit the form.
*   An override of both :file:`List.html` and :file:`SortingAndFilters.html`
    reloads the page on a change, through the attribute the selects of the
    filter partials carry. Add the parts of both to get the update in place.

A project that reloaded the list through a script of its own, a turbo frame for
example, can drop that script.

..  index:: Frontend, Fluid, JavaScript, ext:academic_programs
