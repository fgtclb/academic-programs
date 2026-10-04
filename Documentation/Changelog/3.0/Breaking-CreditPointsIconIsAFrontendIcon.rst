..  _breaking-programs-credit-points-icon-is-a-frontend-icon:

===================================================
Breaking: The credit points icon is a frontend icon
===================================================

Description
===========

The credit points fact of a program shows the icon
``tx-academicprograms-info-credit-points``. Only the frontend shows it, on the
program page, in the program details content element and on the program card.

It was registered in :file:`Configuration/Icons.php`, for the icon registry of
the TYPO3 backend, and :file:`Partials/Program/Facts/Item.html` rendered it with
``core:icon``.

It is now registered in :file:`Configuration/FrontendIcons.php`, for the
frontend icon registry of :guilabel:`academic_base`, and is no longer
registered in :file:`Configuration/Icons.php`. The partial renders the icons of
all facts with the ``ab:icon`` ViewHelper of :guilabel:`academic_base`, with the
arguments it had. Identifier, file and icon provider are unchanged.

The icons of the category type facts, ``category_types.programs.<type>``, come
from the frontend icon registry now as well. :guilabel:`category_types`
registers them in both registries, so they need no move. A category type that
declares a ``frontendIcon`` in :file:`Configuration/CategoryTypes.yaml` shows
that file in the facts, and the icon of the page type, ``academic-programs``,
stays in :file:`Configuration/Icons.php`.

Impact
======

Two things change for a site, and neither shows an error:

*   A site package that replaced the credit points icon in its own
    :file:`Configuration/Icons.php` sees the shipped drawing again in the
    facts. The frontend icon registry does not read that file.
*   An override of :file:`Partials/Program/Facts/Item.html` that still renders
    the icons with ``core:icon`` shows TYPO3's not-found icon in place of the
    credit points icon, because the icon registry of the backend no longer
    knows the identifier. The category type icons keep rendering there, from
    the backend registry, and miss a replacement the site registers for the
    frontend only.

PHP or backend code that asks the :php:`IconFactory` of TYPO3 for
``tx-academicprograms-info-credit-points`` gets the not-found icon as well.

The rendered markup of the icons is the same as before, the inlined drawing in
its wrapper :html:`<span class="t3js-icon icon" data-identifier="…">`, so a
site stylesheet needs no change. One case changes on purpose: a category type
declared without an icon file made the partial fail with exception 1440754980
of the bitmap icon provider of TYPO3, which the backend icon registry picks for
an empty source. It now shows TYPO3's not-found placeholder.

Affected Installations
======================

Every installation whose site package replaces the credit points icon,
overrides :file:`Partials/Program/Facts/Item.html`, or renders
``tx-academicprograms-info-credit-points`` in a template or PHP code of its own.

Migration
=========

#.  Move a replacement of the credit points icon from the
    :file:`Configuration/Icons.php` of the site package to its
    :file:`Configuration/FrontendIcons.php`. The file has the format of
    :file:`Icons.php`. The site package has to depend on
    :guilabel:`academic_programs`, so its entry is read after the shipped one:

    ..  code-block:: php
        :caption: EXT:mysitepackage/Configuration/FrontendIcons.php

        <?php

        use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

        return [
            'tx-academicprograms-info-credit-points' => [
                'provider' => CurrentColorSvgIconProvider::class,
                'source' => 'EXT:mysitepackage/Resources/Public/Icons/CreditPoints.svg',
            ],
        ];

#.  In an override of :file:`Partials/Program/Facts/Item.html`, replace
    ``<core:icon`` with ``<ab:icon``, keep every argument, and declare the
    namespace in the :html:`<html>` tag of the partial instead of the core one:
    ``xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"``.
#.  Flush the TYPO3 caches, so the icon registries and the Fluid template cache
    are rebuilt.

A site package that registers an identifier of its own for its own override,
and renders it with ``core:icon``, is not affected.

..  index:: Fluid, Frontend, ext:academic_programs
