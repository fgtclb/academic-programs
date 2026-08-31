.. _important-page-media-follows-the-reference-sorting:

===================================================
Important: Page media follows the reference sorting
===================================================

Description
===========

:php:`FileReferenceCollection::getCollectionByPageIdAndField()` read
:sql:`sys_file_reference` without an ordering, so a page with several images
in one field rendered them in whatever order the database yielded — the
:sql:`sorting_foreign` an editor arranges in the media field was ignored, and
on PostgreSQL the order could differ between two renders of the same page.
The query now orders by :sql:`sorting_foreign` with :sql:`uid` settling ties.

Impact
======

Galleries and download lists built from this collection now render the images
in the order the editor arranged them on the page — which is what every
supported database happened to deliver for references that were never
reordered, and what editors expressed but never got for references that were.

Affected Installations
======================

Every installation of this extension.

.. index:: Frontend, PHP-API, ext:academic_programs
