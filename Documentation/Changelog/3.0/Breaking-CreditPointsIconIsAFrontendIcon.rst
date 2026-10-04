..  _breaking-programs-credit-points-icon-is-a-frontend-icon:

==============================================================
Breaking: Icons renamed, credit points icon is a frontend icon
==============================================================

Description
===========

The icons of this extension came from several sources, a brand mark,
Illustrator exports and icon sets of different styles, and several of them did
not show what they stood for: the category type "Begin of program" was a
bicycle, and the drawings of "Standard period" and "Type of program" read as if
they had been swapped. One identifier, ``academic-programs``, registered from
the extension icon, served the page type, and the content elements named a file
path that was never a registered identifier.

Every icon of this extension is now a Font Awesome Free solid icon, drawn in
`currentColor` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
and each concept has a glyph that fits it (ACE-591). Icons that mean the same in
every academic extension, degree, department, standard period and location,
come from the shared icon set of :guilabel:`academic_base`. The licence and origin of every file of
this extension are listed in
:file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.

The identifiers follow the scheme
``tx-<extension key without underscores>-<group>-<name>``. The group decides
the registry: ``doktype`` and ``plugin`` icons are backend icons, registered in
:file:`Configuration/Icons.php`, ``info`` icons are frontend icons, registered
in :file:`Configuration/FrontendIcons.php` for the frontend icon registry of
:guilabel:`academic_base`. Renamed identifiers keep no alias.

..  list-table::
    :header-rows: 1

    *   -   2.x identifier
        -   3.0 identifier
        -   Registry
        -   Used for
    *   -   ``academic-programs``
        -   ``tx-academicprograms-doktype-program``
        -   backend, :file:`Configuration/Icons.php`
        -   the academic program page type (doktype 20): page type select and
            page tree
    *   -   ``EXT:academic_programs/Resources/Public/Icons/Extension.svg``
            (a file path, never registered)
        -   ``tx-academicprograms-plugin-programs``
        -   backend, :file:`Configuration/Icons.php`
        -   the content elements ``academicprograms_programlist``,
            ``academicprograms_programdetails`` and
            ``academicprograms_programfinder``: page module, record list and
            new content element wizard
    *   -   ``tx-academicprograms-info-credit-points`` (new in 3.0)
        -   unchanged
        -   frontend, :file:`Configuration/FrontendIcons.php`
        -   the credit points fact of a program
    *   -   ``category_types.programs.<type>``
        -   unchanged
        -   both, registered by :guilabel:`category_types`
        -   the twelve category types of the group ``programs``
    *   -   ``category_types_group.programs`` (new in 3.0)
        -   unchanged
        -   both, registered by :guilabel:`category_types`
        -   the category type group ``programs``

The page type and the content elements share the file
:file:`Resources/Public/Icons/plugin/programs.svg` (Font Awesome
``book-open-reader``) under two identifiers, so a project can replace either of
them on its own. :file:`Resources/Public/Icons/Extension.svg` stays the
extension icon and is registered for nothing.

The category type and group identifiers are unchanged, the files
:file:`Configuration/CategoryTypes.yaml` declares for them are replaced. Paths
are below :file:`Resources/Public/Icons/` of this extension unless they start
with ``EXT:``:

..  list-table::
    :header-rows: 1

    *   -   Category type
        -   2.x file
        -   3.0 file
    *   -   group ``programs``
        -   :file:`CategoryGroups/Programs.svg` (never shipped)
        -   :file:`category-group/programs.svg` (``book-open-reader``)
    *   -   ``admission_restriction``
        -   :file:`CategoryTypes/AdmissionRestriction.svg`
        -   :file:`category-type/admission-restriction.svg` (``lock``)
    *   -   ``application_period``
        -   :file:`CategoryTypes/ApplicationPeriod.svg`
        -   :file:`category-type/application-period.svg` (``hourglass-half``)
    *   -   ``begin_program``
        -   :file:`CategoryTypes/BeginProgram.svg`
        -   :file:`category-type/begin-program.svg` (``flag``)
    *   -   ``costs``
        -   :file:`CategoryTypes/Costs.svg`
        -   :file:`category-type/costs.svg` (``piggy-bank``)
    *   -   ``paying``
        -   :file:`CategoryTypes/Paying.svg`
        -   :file:`category-type/paying.svg` (``money-bill-1``)
    *   -   ``degree``
        -   :file:`CategoryTypes/Degree.svg`
        -   :file:`EXT:academic_base/Resources/Public/Icons/info/degree.svg`
            (``graduation-cap``)
    *   -   ``department``
        -   :file:`CategoryTypes/Department.svg`
        -   :file:`EXT:academic_base/Resources/Public/Icons/info/department.svg`
            (``building-columns``)
    *   -   ``standard_period``
        -   :file:`CategoryTypes/StandardPeriod.svg`
        -   :file:`EXT:academic_base/Resources/Public/Icons/info/time.svg`
            (``clock``)
    *   -   ``location``
        -   :file:`CategoryTypes/Location.svg`
        -   :file:`EXT:academic_base/Resources/Public/Icons/info/location.svg`
            (``location-dot``)
    *   -   ``program_type``
        -   :file:`CategoryTypes/ProgramType.svg`
        -   :file:`category-type/program-type.svg` (``layer-group``)
    *   -   ``teaching_language``
        -   :file:`CategoryTypes/TeachingLanguage.svg`
        -   :file:`category-type/teaching-language.svg` (``language``)
    *   -   ``topic``
        -   :file:`CategoryTypes/Topic.svg`
        -   :file:`category-type/topic.svg` (``lightbulb``)

The folder :file:`Resources/Public/Icons/CategoryTypes/` is removed, with
:file:`JobProfile.svg`, :file:`PerformanceScope.svg` and
:file:`Prerequisites.svg`, which nothing referenced, and so is
:file:`Resources/Public/Icons/LICENSE-bootstrap-icons.txt`, which covered two of
the removed drawings.

The credit points fact of a program shows the icon
``tx-academicprograms-info-credit-points``. Only the frontend shows it, on the
program page, in the program details content element and on the program card.
It was registered in :file:`Configuration/Icons.php`, for the icon registry of
the TYPO3 backend, and :file:`Partials/Program/Facts/Item.html` rendered it with
``core:icon``. It is now registered in :file:`Configuration/FrontendIcons.php`,
for the frontend icon registry of :guilabel:`academic_base`, and is no longer
registered in :file:`Configuration/Icons.php`. The partial renders the icons of
all facts with the ``ab:icon`` ViewHelper of :guilabel:`academic_base`, with the
arguments it had. Identifier, file and icon provider of the credit points icon
are unchanged (ACE-814).

The icons of the category type facts, ``category_types.programs.<type>``, come
from the frontend icon registry now as well. :guilabel:`category_types`
registers them in both registries, so they need no move. A category type that
declares a ``frontendIcon`` in :file:`Configuration/CategoryTypes.yaml` shows
that file in the facts.

Impact
======

The identifier ``academic-programs`` is no longer registered. Wherever a
project names it, a template calling ``core:icon``, TCA, or the page TSconfig of
a wizard entry of its own, TYPO3 renders its not-found placeholder. A project
that replaced the page type icon by registering ``academic-programs`` in its own
:file:`Configuration/Icons.php` registers an identifier nothing asks for any
more, so the shipped drawing is shown. Site or backend CSS that selects the icon
by its identifier class, ``.icon-academic-programs``, no longer matches.

A project that referenced one of the removed files by path, for example to
reuse a category type drawing in its own :file:`CategoryTypes.yaml`, gets a
missing file.

Every category type icon has a new drawing, in the backend and in the facts of a
program, under its unchanged identifier. How the provider and the markup of
these icons changed is described in
:ref:`breaking-programs-record-and-category-icons-follow-the-colour-scheme`.

Two things change for the credit points icon, and neither shows an error:

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

The rendered markup of the fact icons is the same as before, the inlined
drawing in its wrapper :html:`<span class="t3js-icon icon" data-identifier="…">`,
so a site stylesheet needs no change. One case changes on purpose: a category
type declared without an icon file made the partial fail with exception
1440754980 of the bitmap icon provider of TYPO3, which the backend icon registry
picks for an empty source. It now shows TYPO3's not-found placeholder.

Affected Installations
======================

Installations that name ``academic-programs`` anywhere, select
``.icon-academic-programs`` in CSS, override that identifier, or reference a
file below :file:`Resources/Public/Icons/CategoryTypes/` of this extension.

Installations whose site package replaces the credit points icon, overrides
:file:`Partials/Program/Facts/Item.html`, or renders
``tx-academicprograms-info-credit-points`` in a template or PHP code of its own.

Migration
=========

#.  Replace ``academic-programs`` with ``tx-academicprograms-doktype-program``
    where the page type is meant, and with
    ``tx-academicprograms-plugin-programs`` where a content element is meant,
    in TCA, page TSconfig, templates and CSS selectors on the identifier class.
#.  Register a project specific drawing of a backend icon under its new
    identifier in the :file:`Configuration/Icons.php` of the site package:

    ..  code-block:: php
        :caption: EXT:mysitepackage/Configuration/Icons.php

        <?php

        use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

        return [
            'tx-academicprograms-plugin-programs' => [
                'provider' => CurrentColorSvgIconProvider::class,
                'source' => 'EXT:mysitepackage/Resources/Public/Icons/Programs.svg',
            ],
        ];

    A drawing registered with that provider has to be drawn in `currentColor`
    without colours of its own. Use the core
    :php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider` for a drawing
    with fixed colours.
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
#.  Replace a path to a removed category type file with the new file from the
    table above.
#.  Flush the TYPO3 caches, so the icon registries and the Fluid template cache
    are rebuilt.

A site package that registers an identifier of its own for its own override,
and renders it with ``core:icon``, is not affected.

..  index:: Backend, Fluid, Frontend, TCA, TSConfig, ext:academic_programs
