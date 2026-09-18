.. _important-1789660802:

====================================================
Important: The page module shows the page categories
====================================================

Description
===========

A program page (page type 20) carries its categories in the page properties,
where an editor has to open a form to see them. The page module shows them now
as well, in a table above the content grid: one row per category type of the
:yaml:`programs` group, twelve of them - degree, location, teaching language
and nine more - with the categories assigned to that page.

A type the page carries no category of is listed with a `Not set` note, and a
category that is assigned and switched off is listed with the hidden overlay.

The extension shipped that table before and stopped showing it in March 2023.

It began as a full override of the page module template of :php:`EXT:backend`,
at :file:`Resources/Private/Backend/Templates/PageLayout/PageLayout.html` and
registered through :typoscript:`module.tx_backend.view.templateRootPaths.10`.
That file rendered the categories and then delegated to core's own
:file:`PageLayout/Grid` partial, and it worked.

One day later it was renamed to
:file:`Resources/Private/Backend/Partials/PageLayout/Doktype20.html` and the
registration was changed from :typoscript:`templateRootPaths` to
:typoscript:`partialRootPaths` - and **no template of** :php:`EXT:backend`
**renders a** :file:`PageLayout/Doktype*` **partial**. From that rename on,
nothing rendered the file.

:file:`PageLayout/Doktype*` is not a core convention and never was. The Fluid
page module arrived in TYPO3 v10.3, and the changelog that introduced it lists
every template and partial it ships; the only per-record convention it defines
is :file:`PageLayout/Record/<CType>/{Header,Footer,Preview}`, keyed by the
content type of a record and not by the type of a page. There has been no
version of TYPO3 in which that file name resolved.

TYPO3 v12 later removed the :typoscript:`module.tx_backend.view` mechanism
altogether (v12.0, Breaking: #96812), and the replacement registration in
:file:`Configuration/page.tsconfig`

..  code-block:: typoscript

    templates.typo3/cms-backend.academic-programs = fgtclb/academic-programs:Resources/Private/Backend

kept pointing at the same unrendered partial.

A second defect hid behind the first: the partial translated
:php:`sys_category.academic_programs.{type}`, a key that exists in no XLF
file of this extension, so every type label would have been empty even if
something had rendered it.

The partial and the registration are removed. The summary is rendered by
:php:`EXT:category_types` now, which this extension already depends on, and
asked for by an event listener on
:php:`\TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent`.

Impact
======

*   The page module of a program page shows the categories of that page.
    No other page type is affected: the page module of a standard page
    renders exactly what it rendered before.

*   The type labels are the titles the category types are registered with in
    :file:`Configuration/CategoryTypes.yaml`, so a type an installation adds to
    the :yaml:`programs` group is labelled too.

*   The override key changed. An installation that registered

    ..  code-block:: typoscript

        templates.typo3/cms-backend.academic-programs = my-vendor/my-site:Resources/Private/Backend

    to replace :file:`PageLayout/Doktype20.html` was overriding a file that
    had not rendered since March 2023, so it saw no summary either.
    It registers

    ..  code-block:: typoscript

        templates.fgtclb/category-types.my-site = my-vendor/my-site:Resources/Private/Backend

    instead now, and puts its markup in
    :file:`Resources/Private/Backend/Templates/PageCategorySummary.html`.

Affected Installations
======================

Every installation using this extension sees the summary after the update -
that is the point of the change. Only an installation that overrode the removed
partial has anything to do, and what it had was not in effect.

References
==========

*   TYPO3 v10.3 changelog `Feature: #90348 - Fluid-based replacement for
    PageLayoutView
    <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/10.3/Feature-90348-NewFluid-basedReplacementForPageLayoutView.html>`__ - the
    templates and partials the page module ships, and the one per-record naming
    convention it defines.

*   TYPO3 v12.0 changelog `Breaking: #96812 - No Frontend TypoScript based
    template overrides in the backend
    <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-96812-NoFrontendTypoScriptBasedTemplateOverridesInTheBackend.html>`__ - why the
    :typoscript:`module.tx_backend.view` registration stopped existing and what
    replaced it.

*   The chapter :guilabel:`For Developers` of :php:`EXT:category_types`,
    section :guilabel:`The page module category summary`, describes the
    template, its variables and the override key.

.. index:: Backend, TSConfig, Fluid, NotScanned
