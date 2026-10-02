.. _important-1791043405:

=============================================================
Important: The route enhancer moved and translates its values
=============================================================

Description
===========

The route enhancer of the :guilabel:`Program List` moved from
:file:`Configuration/Yaml/Routes.yaml` to
:file:`Configuration/Routes/List.yaml` and maps the category filter as well
(:ref:`feature-1791043404`). The former file stays and imports the new one, and
the enhancer kept its key :yaml:`AcademicPrograms`.

Impact
======

A site that imports :file:`Configuration/Yaml/Routes.yaml` keeps working and
sees these differences:

*   A filtered list has a path, :file:`/programs/filter/master-of-science-2/title/asc`,
    instead of a query argument after the sorting path. The query argument URLs
    still work.
*   The sorting values follow the language of the site. A German page links
    :file:`/titel/aufsteigend`, and the English values that were published for
    it before, :file:`/title/asc`, answer with a 404. English pages keep their
    URLs.
*   Its :yaml:`limitToPages` for :yaml:`AcademicPrograms` still applies.

Migration
=========

Import :file:`EXT:academic_programs/Configuration/Routes/List.yaml` instead of
the former file. A site whose German sorting paths are linked from elsewhere
redirects them, for example with a redirect record per sorting.

.. index:: Frontend, NotScanned
