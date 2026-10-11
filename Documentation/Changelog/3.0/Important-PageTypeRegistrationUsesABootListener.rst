.. _important-ace-462-academic-programs:

===========================================================
Important: The page type is registered from a boot listener
===========================================================

Description
===========

Up to version 2.4 this extension registered its page type ``20`` with
:php:`PageDoktypeRegistry` in :file:`ext_tables.php`, which allowed every record
type on it. That file is gone.

TYPO3 v14 resolves the tables allowed on a page type from the TCA option
:php:`allowedRecordTypes` and deprecates :php:`PageDoktypeRegistry->add()`.
The page type declares :php:`'allowedRecordTypes' => ['*']` in
:file:`Configuration/TCA/Overrides/pages.php` now.

TYPO3 v13 has no such option and still asks the registry. There the page type is
registered by an event listener on
:php:`\TYPO3\CMS\Core\Core\Event\BootCompletedEvent`,
:php:`\FGTCLB\AcademicPrograms\EventListener\RegisterAcademicPageDoktype`. The listener
does nothing on TYPO3 v14.

The registration cannot move into the TCA override file on TYPO3 v13. The first
call to :php:`PageDoktypeRegistry->add()` collects every table declaring
:php:`security.ignorePageTypeRestriction`, ``tt_content``, ``sys_template`` and
``backend_layout``, from the :php:`TcaSchemaFactory` and keeps the result for
the rest of the request. A TCA override file runs before
:php:`TcaSchemaFactory::load()`, so none of the three would reach the allow list
of the ``default`` page type, and TYPO3 would refuse content elements on
ordinary pages while the TCA cache is cold:

..  code-block:: text

    Attempt to insert record on pages:1 where table "tt_content" is not allowed

:php:`BootCompletedEvent` is dispatched right after
:php:`TcaSchemaFactory::load()`, on every request, warm cache included.

Impact
======

The page type ``20`` allows every record type on TYPO3 v13 and v14, as it did
in version 2.4.

Affected Installations
======================

All installations of this extension. Nothing has to be done beyond the usual
cache flush after an update.

.. index:: TCA, Backend, ext:academic_programs
