..  _feature-1790345444:

==================================================
Feature: Integrators choose the facts of a program
==================================================

Description
===========

The program page, the :guilabel:`Program Details` content element and each
card of the :guilabel:`Program List` show facts about a program: its
categories per category type, and the program fields credit points, job
profile, performance scope and prerequisites. Which facts a place showed, and
in which order, was fixed in its template, and credit points appeared on the
program page only, after all categories and without an icon.

Two settings now decide it, each a comma separated list of facts in display
order:

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

An item is the identifier of a category type of the group `programs`, for
example `degree`, `standard_period` or `location`, or one of the program
fields `creditPoints`, `jobProfile`, `performanceScope` and `prerequisites`:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicprograms:
        facts:
          fields: 'degree,creditPoints,standard_period,location'
        card:
          fields: 'degree,standard_period'

The credit points fact has an icon of its own,
`tx-academicprograms-info-credit-points`.

All three places render their facts through the partials
:file:`Program/Facts.html` and :file:`Program/Facts/Item.html`, so a project
that changes how a fact looks overrides one of them for all three places.

See :ref:`program-facts` for the details.

Impact
======

Without configuration each place shows the facts it showed before: the
program page every category type followed by the four program fields, the
details element every category type, the card the degree. The markup of those
facts changes, see :ref:`breaking-program-categories-partial-removed`.

A fact the program has no value for is skipped, as before, and so is an item
the list names that is no category type of the group and no program field.

..  index:: Frontend, Fluid, TypoScript, ext:academic_programs
