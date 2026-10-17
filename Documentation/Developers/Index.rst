..  _developers:

==============
For developers
==============

The program list and the program finder dispatch two PSR-14 events each, a
program page one, and every content element of this extension the plugin view
event of :guilabel:`academic_base`. They are the supported way to change what
a plugin queries and what a page renders, and they replace what projects did
before: subclassing :php:`ProgramController`, querying the database from
ViewHelpers, or replacing the data processor of the program page.

Which classes of this extension are public API, and what that promises, is
stated for all academic extensions on the :ref:`extension points page of
academic_base <fgtclb/academic-base:developers-extension-points>`.

Nothing changes in an installation that has no listener: without one, the
plugins query and render, and program pages show, exactly what they did
before.

..  _developers-program-list-events:

Events
======

..  list-table::
    :header-rows: 1
    :widths: 25 30 30 15

    *   -   Event
        -   Dispatched
        -   What a listener can change
        -   Owner
    *   -   :ref:`ModifyProgramDemandEvent <developers-event-modify-program-demand-event>`
        -   by the :guilabel:`Course List` and the :guilabel:`Course Finder`,
            after the demand is built from the content element settings and
            the request and before the programs are queried
        -   replace the demand
        -   this extension
    *   -   :ref:`ModifyProgramListEvent <developers-event-modify-program-list-event>`
        -   by the same two, after the query and before the view variables are
            assigned
        -   replace the programs, replace the applicable categories, assign
            further view variables
        -   this extension
    *   -   :ref:`ModifyProgramDataEvent <developers-event-modify-program-data-event>`
        -   on a program page, by the data processor :typoscript:`program-data`,
            after the data of the page is built from the page record and before
            the facts are built from it
        -   replace or change the program data the page template and the facts
            show
        -   this extension
    *   -   :ref:`ModifyPluginViewEvent <developers-event-modify-plugin-view-event>`
        -   by all three content elements, once each time they render, after
            the action assigned its own variables
        -   assign further view variables
        -   :guilabel:`academic_base`

The list and the finder dispatch them in this order: the demand event, the
list event, the plugin view event. The :guilabel:`Course Data` element
dispatches only the plugin view event, and a program page only the program
data event.

..  toctree::
    :hidden:
    :titlesonly:
    :glob:

    Events/*
