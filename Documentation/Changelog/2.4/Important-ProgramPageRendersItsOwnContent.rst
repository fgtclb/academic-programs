..  _important-program-page-renders-its-own-content:

===================================================
Important: The program page renders its own content
===================================================

Description
===========

The page template of the page type :guilabel:`Academic program` rendered the
content elements of the page through the global TypoScript object
:typoscript:`styles.content.getContent`. Since the configuration was cut per
component, this extension defines it only in the component
`fgtclb/academic-programs-content-load` - and in its static template, which
:guilabel:`Academic Programs: All components` includes. The content load
components of :php:`EXT:academic_partners` and :php:`EXT:academic_projects`
define the same path. A site on the component sets or the component static
templates of this extension without any of them got an exception on every
program page:

..  code-block:: text

    No Content Object definition found at TypoScript object path "styles.content.getContent"

The page object of the program page type now carries the content itself, as the
variable :typoscript:`programContent`:

..  code-block:: typoscript

    [page && traverse(page, "doktype") == 20]
      page.10.variables.programContent = CONTENT
      page.10.variables.programContent {
        table = tt_content
        select {
          orderBy = sorting, uid
          where = {#colPos}=0
        }
      }
    [END]

:file:`Resources/Private/Pages/AcademicProgram.html` renders it as
:html:`{programContent -> f:format.raw()}`, on a :typoscript:`FLUIDTEMPLATE`
page object and, on TYPO3 v13, on a :typoscript:`PAGEVIEW` one alike. Every
set and every static template of this extension delivers it, except the content
load override on its own.

The content load component is unchanged and still part of the aggregate set
`fgtclb/academic-programs` and of the static template
:guilabel:`Academic Programs: All components`.

Impact
======

*   A program page renders on a site without the content load component.
*   It renders the same elements as before - the main column, in manual order,
    in the language of the page - with elements that share a `sorting` value in
    uid order.
*   A customisation of :typoscript:`styles.content.getContent` - for example
    one that slides the content from the parent pages - no longer reaches
    program pages. The same holds for a customisation of
    :typoscript:`styles.content.get` that was parsed before the override: the
    override copied it into :typoscript:`styles.content.getContent`.
*   A template override of :file:`AcademicProgram.html` that renders
    :typoscript:`styles.content.getContent` keeps working as long as the site
    includes one of the content load components that define it.

Migration
=========

Move a customisation of :typoscript:`styles.content.getContent` for program
pages to :typoscript:`page.10.variables.programContent`, inside the same
condition - see :ref:`program-page-content`. A template override of
:file:`AcademicProgram.html` may switch to
:html:`{programContent -> f:format.raw()}`; version 3.0 removes the content
load component, and with it :typoscript:`styles.content.getContent`.

..  index:: Frontend, TypoScript, ext:academic_programs
