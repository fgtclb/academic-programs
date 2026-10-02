.. _feature-1790946476:

============================================================
Feature: Active filter tags, a reset link and a result count
============================================================

Description
===========

A visitor who filtered the :guilabel:`Program List` saw the selects and nothing else. Three
settings add what was missing, each off by default:

*   :typoscript:`plugin.tx_academicprograms.filter.showActiveFilters` shows one tag per
    selected category. Each tag links to the list without that
    selection, every other selection and the sorting kept, in the URL shape
    of :ref:`feature-1790226102`.
*   :typoscript:`plugin.tx_academicprograms.filter.showReset` shows a
    :guilabel:`Reset all filters` link to the page without any list argument,
    where the list shows what the content element presets. It is offered
    after the visitor selected something, while a category is selected
    or preset, also once the visitor removed the preset.
*   :typoscript:`plugin.tx_academicprograms.filter.showResultCount` shows the number of
    programs found, with a singular and a plural label.

They are site settings of the aggregate set and constants of the static
template, see :ref:`configuration-active-filters`. The tags and the reset link
are not shown where the content element hides the filter.

Impact
======

*   Nothing changes until a site switches a setting on.
*   :file:`Program/SortingAndFilters.html` renders the new partials
    :file:`Program/ActiveFilters.html` and :file:`Program/ResultCount.html`.
    A project that overrides :file:`Program/SortingAndFilters.html` shows
    neither until it renders them too, with :html:`{_all}`. The list action
    assigns the new variable :html:`{visitorSelection}` for the reset link: whether
    the request carried a list argument at all.
*   A project that renders tags, a reset link or a count in its own templates
    can switch the settings on and remove its own markup. The classes are
    `academic-programs-active-filters` and `academic-programs-result-count`, without styles.

.. index:: Frontend, TypoScript, NotScanned
