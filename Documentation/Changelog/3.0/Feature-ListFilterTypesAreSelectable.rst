..  _feature-1790361244:

===========================================================
Feature: Integrators choose the filters of the program list
===========================================================

Description
===========

The filter form of the :guilabel:`Program List` offered one select for every
category type of the group `programs` that had a category, always in the order
the types are registered in. Offering fewer filters, or the same filters in
another order, needed an override of the partial
:file:`Program/DemandCategories.html`.

Two places now decide it:

..  list-table::
    :header-rows: 1

    *   -   Where
        -   Applies to
    *   -   Field :guilabel:`Filter types` of the :guilabel:`Program List`
            content element, tab :guilabel:`Configuration`
        -   That element. The editor picks the category types and arranges
            them in the order the form offers them.
    *   -   Site setting / constant
            :typoscript:`plugin.tx_academicprograms.filter.categoryTypes`,
            default empty
        -   Every program list of the site whose field is empty, as a comma
            separated list of type identifiers.

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicprograms:
        filter:
          categoryTypes: 'degree,location'

The field offers the types of the group `programs`, including a type a project
adds in its own :file:`Configuration/CategoryTypes.yaml` and without one it
removes there.

The partial :file:`Program/DemandCategories.html` loops the new variable
:html:`{filterTypes.visible}`, the identifiers of the offered types in their
order. Only where that variable does not reach it does it still loop
:html:`{categories.allCategoriesByType}`, see below.

See :ref:`program-list-filter-types`.

Impact
======

Without configuration the form offers the same filters in the same order as
before. Existing content elements have an empty field.

A chosen type without any category is not offered, and neither is a type the
project removed after an element was saved. The filter types decide what the
form offers, not what the list accepts: a link that filters by a category of a
type the form does not offer still filters the list.

Where :html:`{filterTypes}` does not reach the shipped partial — a project
controller that overrides :php:`listAction()` without assigning it, or an
override of :file:`Program/List.html` or :file:`Program/SortingAndFilters.html`
that renders the partial with arguments of its own instead of :html:`{_all}` —
the partial offers every type with a category, as before, and the field and
the setting have no effect there. To use them, let the overriding action call
:php:`parent::listAction()`, and pass :html:`filterTypes` on in a template
that renders the partial.

A project that overrides :file:`Program/DemandCategories.html` and loops
:html:`{categories.allCategoriesByType}` keeps its own list and ignores the new
field and setting. To use them, loop :html:`{filterTypes.visible}` and read the
categories of a type as :html:`{categories.{categoryKey}}`, as the shipped
partial does.

..  index:: Backend, Frontend, FlexForm, Fluid, TypoScript, ext:academic_programs
