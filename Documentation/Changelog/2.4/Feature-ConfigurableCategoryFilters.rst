..  _feature-1790383004:

====================================================
Feature: Choose, order and label the program filters
====================================================

Description
===========

The filter form of the :guilabel:`Program List` offered one select for every
category type of the group `programs` that had a category: all of them at once,
in the order the types are registered in, and each with the same "All options"
label. Anything else needed an override of the partial
:file:`Program/DemandCategories.html`.

Three settings change that, for the whole site. Each is a constant and, from
TYPO3 v13 on, a site setting of the aggregate set, with the same name:

..  list-table::
    :header-rows: 1

    *   -   Setting
        -   Default
        -   Meaning
    *   -   :typoscript:`plugin.tx_academicprograms.filter.categoryTypes`
        -   empty
        -   The category types to offer, in this order, as a comma separated
            list of type identifiers. Empty offers every type with a category,
            in the order the types are registered in.
            From 3.0 on, the :guilabel:`Program List` and
            :guilabel:`Program Finder` content elements have a field of their
            own that overrides it for one element.
    *   -   :typoscript:`plugin.tx_academicprograms.filter.visibleCount`
        -   0
        -   How many filters the form shows right away. The others follow in a
            :guilabel:`More filters` section, a native :html:`<details>`
            element the visitor opens, which is open already while one of its
            filters has a value. 0 shows every filter.
    *   -   :typoscript:`plugin.tx_academicprograms.filter.hideDisabledOptions`
        -   0
        -   Leaves out a category none of the listed programs carries,
            instead of offering it as a disabled option. A selected category is
            always offered.

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicprograms:
        filter:
          visibleCount: 2
          hideDisabledOptions: true

The "All" option of each filter first reads a label of its type,
:xml:`sys_category.programs.allOptions.<type>`, and falls back to
:xml:`sys_category.programs.allOptions` where there is none. The extension ships
no label per type; a site adds them in TypoScript:

..  code-block:: typoscript

    plugin.tx_academicprograms._LOCAL_LANG.default.sys_category.programs.allOptions.degree = All degrees

From 3.0 on, the :guilabel:`Program Finder` follows the list in the options
without results, among the programs in its own storage, and in the label of the
"All" option. It always shows all of its selects.

Impact
======

Without configuration the filters render as before, markup included.

On TYPO3 v12 and v13, TypoScript label overrides of the filters are now read
from another path, see :ref:`important-1790383007`.

The partial :file:`Program/DemandCategories.html` loops the new variables
:html:`{filterTypes.visible}` and :html:`{filterTypes.more}`. Where
:html:`{filterTypes}` does not reach it — a project controller that overrides
:php:`listAction()`, or a template that renders the partial with arguments of
its own instead of :html:`{_all}` — it offers every type with a category, as
before, and the filter types and the visible count have no effect there. To use
them, let the overriding action call the parent action, and pass
:html:`filterTypes` on to the partial.

A project that overrides :file:`Program/DemandCategories.html` keeps its own
markup and none of this reaches it. To use the settings, loop
:html:`{filterTypes.visible}` and :html:`{filterTypes.more}` as the shipped
partial does, and pass
:html:`hideDisabledOptions="{settings.filter.hideDisabledOptions}"` to
:html:`<ct:form.filterSelect>`.

..  index:: Frontend, Fluid, TypoScript, ext:academic_programs
