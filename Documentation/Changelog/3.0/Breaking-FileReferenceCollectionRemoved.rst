..  _breaking-file-reference-collection-removed:

==================================================
Breaking: The file reference collection is removed
==================================================

Description
===========

The class :php:`\FGTCLB\AcademicPrograms\Collection\FileReferenceCollection`
is removed. It read the file references of one field of a page, through its
static method :php:`getCollectionByPageIdAndField()`, and nothing this
extension ships calls it. Version 2.4 deprecated it, see
:ref:`deprecation-file-reference-collection`.

The core has the same lookup:
:php:`\TYPO3\CMS\Core\Resource\FileRepository::findByRelation()` returns the
file references of a field of a record in the order the editor arranged them,
the order the collection used since 2.4.

Impact
======

Code of a project that calls
:php:`FileReferenceCollection::getCollectionByPageIdAndField()` fails with an
error, because the class does not exist any more. The page media of a program
page render as before, on TYPO3 v13 and v14.

Affected Installations
======================

Installations whose own code names the class. Search the site package for it:

..  code-block:: bash

    grep -rn "FileReferenceCollection" packages/my_sitepackage/

Migration
=========

Read the references through the core repository, which takes the table, the
field and the uid of the record:

..  code-block:: php

    use TYPO3\CMS\Core\Resource\FileRepository;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $fileReferences = GeneralUtility::makeInstance(FileRepository::class)
        ->findByRelation('pages', 'media', $pageId);

Inject :php:`FileRepository` where the calling class is a service. It returns
an array of :php:`\TYPO3\CMS\Core\Resource\FileReference` instead of an object
that counts and iterates them, so :php:`count()` and :php:`foreach` work on it
unchanged. In TypoScript, the :typoscript:`files` data processor of the core
reads the same references.

The collection and the repository both look the references up by the uid of
the record that holds them, so a translated page needs the uid of its
translation, and neither filters by language. Which references come back
differs, on TYPO3 v13 and v14 alike:

*   The collection always left out deleted and hidden references, through the
    default restrictions of the query builder, and read the rows of every
    workspace alike, without an overlay.
*   In the frontend, the repository applies the frontend restrictions. It
    leaves out deleted and hidden references too, shows hidden ones while
    hidden records are previewed, and follows the workspace being previewed.
*   Everywhere else, in the backend and on the command line, it reads the
    references through the relation handler of the field, which returns
    hidden references as well and applies the workspace overlay. The field
    has to be defined in the TCA of the table.

..  index:: PHP-API, ext:academic_programs
