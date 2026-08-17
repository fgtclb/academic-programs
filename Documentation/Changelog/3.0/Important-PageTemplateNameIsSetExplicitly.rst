.. _important-1786968000:

=====================================================================================
Important: The page template name of the academic program page type is set explicitly
=====================================================================================

Description
===========

This extension registers a page type with a backend layout and ships the page
template for it in :file:`Resources/Private/Pages/`. Its TypoScript adds that
directory to :typoscript:`page.10.templateRootPaths`, but it did not set
:typoscript:`page.10.templateName` — the property that actually selects the
file.

A site package deriving the name from the backend layout therefore did not find
it. :composer:`bk2k/bootstrap-package` does exactly that:

..  code-block:: typoscript

    templateName.cObject = TEXT
    templateName.cObject {
      data = pagelayout
      case = uppercamelcase
      split {
        token = pagets__
        cObjNum = 1
        1.current = 1
      }
    }

:typoscript:`case = uppercamelcase` is
:php:`GeneralUtility::underscoredToUpperCamelCase()`, which lowercases the whole
string before it camel cases it on underscores. The registered backend layout
:typoscript:`pagets__AcademicProgram` therefore resolved to :file:`Academicprogram.html`, and
the frontend ended in an :php:`InvalidTemplateResourceException` — in a
production context a page whose body reads *Oops, an error occurred!*.

The extension now sets the name itself, inside the page type condition it
already uses:

..  code-block:: typoscript

    [page && traverse(page, "doktype") == 20]
      page.10 {
        templateName >
        templateName = AcademicProgram
      }
    [END]

The clear is not decoration. Bootstrap package assigns
:typoscript:`templateName.cObject`, and a cObject overwrites the plain value in
:php:`ContentObjectRenderer::stdWrapValue()`, so assigning without clearing
would change nothing.

Impact
======

A page of this type renders its template on a site package that derives the
name from the backend layout, where it previously did not render at all.

Nothing changes for a site package that sets the name itself, as long as it does
so after this extension's TypoScript, and nothing changes for a
:typoscript:`PAGEVIEW` page object — that content object ignores
:typoscript:`templateName` and resolves the file from :typoscript:`paths`, which
is why that integration worked before and is unaffected now.

Affected Installations
======================

All installations of this extension that use a :typoscript:`FLUIDTEMPLATE` page
object. An installation that shipped :file:`Academicprogram.html` in its own site
package to work around this loses that override: the template of this extension
is used instead. Clearing :typoscript:`page.10.templateName` after this
extension's TypoScript restores it.

.. index:: TypoScript, Frontend, ext:academic_programs
