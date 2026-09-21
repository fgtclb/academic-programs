..  _breaking-programs-images-render-as-picture:

===============================================
Breaking: Images render as a responsive picture
===============================================

Description
===========

The program list item and the program page template render their image
through the responsive image partial of `EXT:academic_base`,
:file:`Partials/Academic/Image.html`, from 3.0 on. Every raster image is
therefore a :html:`<picture>` with WebP sources and a fallback :html:`<img>`
that carries the classes it carried before, and an SVG file is rendered as one
:html:`<img>` of the original file without processing.

The list item asks for the preset `card`, the page template for `detail`.

The views register :file:`EXT:academic_base/Resources/Private/Partials/`:
the plugin view with the partial root path key `-1`, below the keys `0` and
`1` it uses already, and the page object of the program page type with the
key `-1758484802`, below every theme and project path. The page object
registers it twice, because a :typoscript:`PAGEVIEW` page object reads no
`partialRootPaths`: it derives those from `paths` by appending
:file:`Partials/`, so `paths` carries
:file:`EXT:academic_base/Resources/Private/` under the same key.

Impact
======

*   CSS that selects the image as a direct child of the card, or as the direct
    child of the detail container, no longer matches: it is wrapped in a
    :html:`<picture>`.
*   The fallback image is requested with `loading="lazy"`, which it was not
    before, and its `width` and `height` are those of the preset rather than
    those of the original file.
*   A project that replaces the partial root paths of the plugin completely,
    or a page object that renders `Program/Item` or the page template in a view
    of its own, fails with an exception on the partial `Academic/Image` that
    the view cannot resolve.

Affected Installations
======================

Every installation that renders a program list, or a page of the program page
type with an image.

Migration
=========

#.  Adjust CSS that addresses the program image.
#.  A plugin view whose partial root paths were replaced needs the path of
    `EXT:academic_base` below the others:

    ..  code-block:: typoscript

        plugin.tx_academicprograms.view.partialRootPaths {
            -1 = EXT:academic_base/Resources/Private/Partials/
        }

#.  If a page object of the project renders the page template or one of the
    item partials itself, list the path there with a key of your own - under
    `partialRootPaths` for a :typoscript:`FLUIDTEMPLATE` page object, under
    `paths` for a :typoscript:`PAGEVIEW` one:

    ..  code-block:: typoscript

        page.10 {
            partialRootPaths.-1700000001 = EXT:academic_base/Resources/Private/Partials/
            paths.-1700000001 = EXT:academic_base/Resources/Private/
        }

#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, TypoScript, ext:academic_programs
