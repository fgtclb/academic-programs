.. _feature-1791043404:

===========================================
Feature: The route enhancer maps the filter
===========================================

Description
===========

The route enhancer of the :guilabel:`Program List` maps the category filter
as well as the sorting, and translates the sorting values. It ships in
:file:`Configuration/Routes/List.yaml`, next to the route files of the other
academic extensions. A site that imports the file gets readable list URLs in
the language of the site:

..  code-block:: text

    /programs/filter/master-of-science-2/last-updated/desc
    /de/studiengaenge/filter/master-of-science-2/zuletzt-aktualisiert/absteigend

A filter alone, a sorting alone and both together each have a route of their
own. The filter segment uses the :yaml:`CategoryFilterMapper` aspect of
:guilabel:`category_types`.

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    imports:
      - resource: 'EXT:academic_programs/Configuration/Routes/List.yaml'

    routeEnhancers:
      AcademicPrograms:
        limitToPages: [23]

See :ref:`configuration-route-enhancers`, and :ref:`important-1791043405` for
a site that imports the former file.

Impact
======

*   Nothing changes for a site that imports neither file.
*   The filter adds no page cache entry: it is a dynamic argument, and the
    demand of the list is excluded from the cache hash.

.. index:: Frontend, NotScanned
