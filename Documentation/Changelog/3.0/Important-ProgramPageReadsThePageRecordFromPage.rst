.. _important-program-page-reads-the-page-record-from-page:

=======================================================
Important: Program page reads the page record from page
=======================================================

Description
===========

The data processor :typoscript:`program-data` builds the program of a program
page from the page record. It took that record from the variable
:typoscript:`data` first, the one a :typoscript:`FLUIDTEMPLATE` page object
assigns, and from the page information object :html:`{page}` of a
:typoscript:`PAGEVIEW` page object only when :typoscript:`data` was empty.

:typoscript:`PAGEVIEW` reserves :html:`{page}` but not :typoscript:`data`, so a
:typoscript:`PAGEVIEW` site package may assign a :typoscript:`data` of its
own. A text there failed every program page with a type error, and the
records of a query were read as if they were the page record.

The processor now takes the record from :html:`{page}` when it is a page
information object, and from :typoscript:`data` otherwise. A value that is not
a non-empty array adds no program. The partner and project page processors
read the record in the same order.

Impact
======

*   A :typoscript:`PAGEVIEW` site package that assigns a variable
    :typoscript:`data` of its own, a text or the records of a query for
    example, no longer breaks program pages. The page shows the program of the
    page it is on.
*   A :typoscript:`PAGEVIEW` site package that assigned another page record
    as :typoscript:`data` on purpose, to show another program than the one of
    the page, now gets the program of the page. A listener to
    :php:`ModifyProgramDataEvent` changes the program instead.
*   A :typoscript:`FLUIDTEMPLATE` page object is not affected.

The same applies to TYPO3 v13 and v14.

Affected Installations
======================

Installations with a :typoscript:`PAGEVIEW` page object that assigns a
variable :typoscript:`data` on program pages.

.. index:: TypoScript, Frontend, ext:academic_programs
