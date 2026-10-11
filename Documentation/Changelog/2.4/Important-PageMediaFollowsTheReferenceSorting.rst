..  _important-page-media-follows-the-reference-sorting:

===================================================
Important: Page media follows the reference sorting
===================================================

Description
===========

:php:`FGTCLB\AcademicPrograms\Collection\FileReferenceCollection::getCollectionByPageIdAndField()`
read :sql:`sys_file_reference` without an ordering, so a page with several
files in one field returned them in whatever order the database yielded. The
:sql:`sorting_foreign` an editor arranges in the field was ignored, and on
PostgreSQL the order could differ between two calls for the same page. The
query now orders by :sql:`sorting_foreign`, with :sql:`uid` settling ties.

Impact
======

Nothing this extension renders uses the collection. Code of a project that
calls the method gets the files in the order the editor arranged them on the
page. That is what SQLite, MySQL and MariaDB delivered in practice for
references that were never reordered, and what editors expressed but never got
for references that were.

Affected Installations
======================

Installations whose own code calls
:php:`FileReferenceCollection::getCollectionByPageIdAndField()`. Nothing has to
be done on update.

..  index:: PHP-API, ext:academic_programs
