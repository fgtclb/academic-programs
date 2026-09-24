.. _important-1790226103:

=========================================================
Important: The route enhancer declares no default sorting
=========================================================

Description
===========

The route enhancer in :file:`Configuration/Yaml/Routes.yaml` maps the sorting
of the program list into the path and declares no `defaults` for its two path
variables. A variable that equals its default is left out of a generated path,
so with `defaults` of :yaml:`title` and :yaml:`asc` a link with the default
sorting would be generated as the page URL without arguments.

That page URL is where the content element's preset categories and sorting
apply. The program list redirects every filter submission to a URL carrying
the sorting (:ref:`feature-1790226102`); with `defaults`, a visitor who cleared
a preset category and kept the default sorting would be sent to that page URL
and get the preset category back.

What follows from the enhancer declaring none:

*   The default sorting is generated as :file:`/title/asc` like every other
    sorting, and the page URL without arguments stays the preset list.
*   A path with the sorting field alone, such as :file:`/last-updated`, does
    not resolve; both segments are required.
*   A link to the list that carries no sorting at all - the action URL of the
    plugin's own form, a hand written reset link - does not enter the route
    either. It keeps its plugin arguments in the query string, with a `cHash`,
    and shows the list as the content element presets it.

The file first became usable in 2.4 (:ref:`important-1787055000`), and 2.4
ships it without `defaults` as well, so the paths it generates keep resolving
after the update to 3.0.

Affected Installations
======================

Sites that declare their own route enhancer for the program list with
`defaults` for the sorting: they have the problem described above and should
remove the `defaults`.

.. index:: Frontend, YAML, NotScanned
