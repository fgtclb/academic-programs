..  index:: Configuration; Route enhancers
..  _configuration-route-enhancers:

===============
Route enhancers
===============

This extension ships a route enhancer for the :guilabel:`Course List` in
:file:`Configuration/Routes/List.yaml`. It turns the category filter and the
sorting of the list into path segments in the language of the site:

..  code-block:: text

    /programs/filter/master-of-science-2/last-updated/desc
    /de/studiengaenge/filter/master-of-science-2/zuletzt-aktualisiert/absteigend

TYPO3 does not read the file on its own. A site imports it, and nothing
changes for a site that does not.

Importing it into a site configuration
======================================

Add the file to the :yaml:`imports` of the site that shows the plugin, and
limit the enhancer to the pages that carry it:

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    imports:
      - resource: 'EXT:academic_programs/Configuration/Routes/List.yaml'

    routeEnhancers:
      AcademicPrograms:
        limitToPages: [23]

TYPO3 offers every enhancer of a site to every page unless it carries
:yaml:`limitToPages`, and the first route whose path matches wins. The uids
are those of the pages in the **default language**, one entry covers every
translation of a page.

A site that wrote its own enhancer for the plugin removes it when it imports
this file. Two enhancers for one plugin compete for the same URLs.

The former file
---------------

The enhancer used to be :file:`Configuration/Yaml/Routes.yaml`, with the
sorting route only. That file now imports :file:`Configuration/Routes/List.yaml`,
and the enhancer kept its key :yaml:`AcademicPrograms`. A site that imports the
former path gets the filter routes as well and keeps its
:yaml:`limitToPages`, but should switch to the new path. See
:ref:`important-1791043405`.

What the URLs look like
=======================

A list URL carries up to two arguments, in this order:

..  list-table::
    :header-rows: 1

    *   -   Argument
        -   English
        -   German
    *   -   Category filter
        -   :file:`/filter/master-of-science-2`
        -   :file:`/filter/master-of-science-2`
    *   -   Sorting
        -   :file:`/title/desc`
        -   :file:`/titel/absteigend`

A filter alone, a sorting alone and both together each have a route of their
own and resolve.

*   **The filter** is mapped by the :yaml:`CategoryFilterMapper` aspect of
    :guilabel:`category_types`, with the group :yaml:`programs`: the title of
    each category in the language of the page, followed by its uid. See
    `its documentation
    <https://docs.typo3.org/p/fgtclb/category-types/main/en-us/Developers/Index.html>`__.
*   **The sorting** is two segments, field and direction, the values of
    :php:`FGTCLB\AcademicPrograms\Enumeration\SortingOptions`:

    ..  list-table::
        :header-rows: 1

        *   -   Sorting
            -   English
            -   German
        *   -   Title
            -   :file:`title`
            -   :file:`titel`
        *   -   Last updated
            -   :file:`last-updated`
            -   :file:`zuletzt-aktualisiert`
        *   -   Sorting of the page tree
            -   :file:`sorting`
            -   :file:`sortierung`
        *   -   Ascending, descending
            -   :file:`asc`, :file:`desc`
            -   :file:`aufsteigend`, :file:`absteigend`

A sorting value of the other language is a 404, not a second address of the
same list. The filter segment is read by the uid, so it resolves under the
title of any language.
A path with the sorting field alone, such as :file:`/last-updated`, does not
resolve.

The enhancer declares no defaults, on purpose: a link that uses the default
sorting generates :file:`/title/asc`, not the plain page URL. The plain page
URL is where the content element's preset categories and sorting apply, and
the list redirects every form submission to a URL with the sorting in it so
that a visitor who cleared a preset category does not get it back. A default
would turn that redirect into the plain page URL again. The same holds for an
enhancer a site writes for this plugin itself. A link to the list that carries
no argument at all, the action URL of the plugin's own form for example,
enters no route and keeps its plugin arguments in the query string.

The form of the plugin submits by POST, and the plugin answers with a redirect
to the list URL, so the address bar shows the path after a submit. The program
finder posts to the list as well and ends on the same path.

Another language
================

The file maps English and German. A site with a further language adds
:yaml:`localeMap` items to the aspects in its own site configuration. The
import appends list items, so the site's own items come after the shipped
ones:

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    routeEnhancers:
      AcademicPrograms:
        limitToPages: [23]
        aspects:
          sorting_field:
            localeMap:
              - locale: 'fr.*'
                map:
                  titre: 'title'
                  mise-a-jour: 'lastUpdated'
                  tri: 'sorting'
          sorting_direction:
            localeMap:
              - locale: 'fr.*'
                map:
                  croissant: 'asc'
                  decroissant: 'desc'

A language without :yaml:`localeMap` items of its own uses the English keys
and values. A value missing from the map of a language keeps its query
argument in that language.

The page cache
==============

The sorting is a static route argument, so each of the six sortings is a page
cache entry of its own, per page and language. A path with the filter alone is
one more entry, and the page without arguments another, eight in all. The
filter is a dynamic argument and adds no entry: the demand of the list is
excluded from the cache hash (:ref:`important-1790226107`), so every filter of
a list shares one entry and its path carries no cache hash.

The detail plugin (:guilabel:`ProgramDetails`) takes no arguments, it renders
the program of the page it sits on, so there is nothing to map into a path for
it.
