.. _important-ace-462-academic-programs:

===========================================================
Important: The page type is registered from a boot listener
===========================================================

Description
===========

TYPO3 v13 has no :php:`allowedRecordTypes` TCA option and resolves the tables
allowed on a page type through :php:`PageDoktypeRegistry`. This extension
registered its page type ``20`` there, from
:file:`Configuration/TCA/Overrides/pages.php`.

That is too early. The first call to :php:`PageDoktypeRegistry->add()` collects
every table declaring :php:`security.ignorePageTypeRestriction` - ``tt_content``,
``sys_template`` and ``backend_layout`` - from the :php:`TcaSchemaFactory` and
latches the result for the rest of the request. A TCA override file runs while
the TCA is still being assembled, before :php:`TcaSchemaFactory::load()`, so that
factory is still empty and none of the three ever reaches the allow list of the
``default`` page type.

TYPO3 then refuses content elements on ordinary pages:

..  code-block:: text

    Attempt to insert record on pages:1 where table "tt_content" is not allowed

This happens while the TCA cache is cold, which is every import run after a cache
flush and the first backend request after one. Once the TCA cache is warm the
override files are not executed at all - and then the page type of this extension
is not registered either, and silently falls back to the allow list of the
``default`` page type instead of allowing every record type.

The registration moved to an event listener on
:php:`\TYPO3\CMS\Core\Core\Event\BootCompletedEvent`,
:php:`\FGTCLB\AcademicPrograms\EventListener\RegisterAcademicPageDoktype`. That event
is dispatched one line after :php:`TcaSchemaFactory::load()` and on every request,
warm cache included, which is what both halves of the defect need. The listener
does nothing on TYPO3 v14, where the TCA option carries the configuration and
:php:`PageDoktypeRegistry->add()` is deprecated.

Impact
======

Content elements can be created on standard pages again while the TCA cache is
cold, and the page type ``20`` of this extension allows every record type
while it is warm.

Affected Installations
======================

All installations of this extension on TYPO3 v13. TYPO3 v14 is not affected, it
resolves the allowed tables from TCA. Nothing has to be done beyond the usual
cache flush after an update.

.. index:: TCA, Backend, ext:academic_programs
