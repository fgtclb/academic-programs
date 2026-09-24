..  _breaking-program-page-renders-inside-the-site-layout:

=====================================================
Breaking: Program pages render inside the site layout
=====================================================

Description
===========

The page template of the page type :guilabel:`Academic program`,
:file:`Resources/Private/Pages/AcademicProgram.html`, declared no Fluid layout.
On a site package that renders its header, navigation and footer through a page
layout, as :composer:`bk2k/bootstrap-package` does, a program page therefore
rendered without any of them. The template rendered every part inline, so a
project that wanted to change one of them had to replace the whole template.
And its paths were registered at the key `100` of the page object, which on a
:typoscript:`PAGEVIEW` site package replaced the package's own paths of that key
on program pages - its layouts and partials were gone there.

From 3.0 on:

*   The template renders the section :html:`Main` of the layout named by the
    new setting :typoscript:`plugin.tx_academicprograms.page.layout`, `Default`
    by default. A site package without a layout :file:`Default` gets a fallback
    layout of this extension, which renders the section alone.
*   The section renders four partials: :file:`Program/Page/Header.html`,
    :file:`Program/Page/Media.html`, :file:`Program/Page/Facts.html` and
    :file:`Program/Page/Content.html`.
*   The header renders the subtitle of the program, and a link back to the
    page named by the new setting :typoscript:`plugin.tx_academicprograms.page.listPid`.
*   :typoscript:`paths`, :typoscript:`templateRootPaths` and
    :typoscript:`partialRootPaths` of the page object use the key `50` instead of
    `100` on program pages. :typoscript:`layoutRootPaths.100`, which named a
    directory that does not exist, is removed.

Both settings are site settings of the aggregate set
`fgtclb/academic-programs` and constants for the static templates, see
:ref:`program-page-layout`.

Impact
======

*   A program page on a site package with a layout :file:`Default` renders
    inside it: with the header, navigation and footer of the site.
*   A site package whose layout :file:`Default` renders no section
    :html:`Main` renders the program page without its content.
*   A site package without a layout :file:`Default` renders the program page
    as before, without the frame of the site. A layout named by the setting
    that the site package does not have fails the page.
*   A program with a subtitle shows it below the title, in an element with the
    class `academic-programs-detail__subtitle`. The title is wrapped in an
    element with the class `academic-programs-detail__header`.
*   A path a project registered at a key between `50` and `100` now wins over
    the extension, where it lost before. A site package path at `100` is no
    longer replaced on program pages.
*   A project that set, read or cleared :typoscript:`page.10.paths.100`,
    :typoscript:`templateRootPaths.100`, :typoscript:`partialRootPaths.100` or
    :typoscript:`layoutRootPaths.100` inside the condition on the program page
    type reaches nothing of this extension there any more.

Affected Installations
======================

Every installation that renders program pages on a site package with page
layouts, and every installation that styles the markup of the program page or
changes its paths at the key `100`.

An override of the whole :file:`AcademicProgram.html` keeps rendering as
before.

Migration
=========

*   A site package whose layout for program pages has another name sets
    :typoscript:`plugin.tx_academicprograms.page.layout` to it; one whose
    layout renders another section than :html:`Main` overrides
    :file:`Pages/AcademicProgram.html`.
*   A project that overrides :file:`AcademicProgram.html` to change one part
    moves that change to the matching partial below
    :file:`Program/Page/` and removes the template override.
*   Move a path set at the key `100` inside the program page condition to
    `50`, or to a key above it to win over the extension.
*   Set :typoscript:`plugin.tx_academicprograms.page.listPid` to show the link
    back to the program list.
*   Adjust stylesheets that relied on the title being a direct child of the
    flex container.

..  index:: Frontend, Fluid, TypoScript, ext:academic_programs
