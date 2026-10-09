.. _important-content-element-titles-and-descriptions:

============================================================
Important: Content elements have new titles and descriptions
============================================================

Description
===========

The content elements of this extension have new titles and new descriptions, in
English and in German. They follow one wording that all academic extensions
share now. An editor sees the title in the new content element wizard and in
the type field of a content element, and the description below the title in the
wizard.

..  list-table::
    :header-rows: 1

    *   -   Content type
        -   Title until now
        -   Title now
        -   Description now
    *   -   :typoscript:`academicprograms_programdetails`
        -   :guilabel:`Program Details` (German :guilabel:`Programmdetails`)
        -   :guilabel:`Course Data` (German :guilabel:`Studiengang Daten`)
        -   Displays the key details and categories of a degree programme
    *   -   :typoscript:`academicprograms_programfinder`
        -   :guilabel:`Program Finder` (German :guilabel:`Programmfinder`)
        -   :guilabel:`Course Finder` (German :guilabel:`Studiengang Finder`)
        -   Degree programme finder with filters for qualification and area of interest
    *   -   :typoscript:`academicprograms_programlist`
        -   :guilabel:`Program List` (German :guilabel:`Programmliste`)
        -   :guilabel:`Course List` (German :guilabel:`Studiengang Liste`)
        -   Degree programme overview page with sorting and filtering by category

Impact
======

Editors see the new titles and descriptions. The content types, the label keys
and their files did not change. A site that replaces a title or a description,
with page TSconfig of the wizard, with
:typoscript:`TCEFORM.tt_content.CType.altLabels` or with a language file
override, keeps its own text.

Affected Installations
======================

Every installation that offers a content element of this extension to its
editors. Nothing has to be migrated.

.. index:: Backend, TSConfig, ext:academic_programs
