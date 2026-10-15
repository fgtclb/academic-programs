..  _upgrade-from-2-3-to-2-4:

=========================
Upgrading from 2.3 to 2.4
=========================

Version 2.4 delivers the configuration of :guilabel:`academic_programs` per
component and hides its content elements until a site enables them. The program
page renders its content through a variable of its own page object now, instead
of the global :typoscript:`styles.content.getContent`, and the update brings no
database change and no upgrade wizard for this extension. Work through the
sections in order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-3-to-2-4-before-you-start:

Before you start
================

Version 2.4 runs on TYPO3 12.4 from 12.4.22 on and on TYPO3 13.4, with PHP 8.1
or later. That is the same as for 2.3, so the TYPO3 core does not have to
change for this upgrade.

:guilabel:`academic_programs` 2.4 needs these packages. Upgrade them first or
in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 1.x, new in 2.4, which
    :guilabel:`academic_base` 2.4 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` before
    it activates :guilabel:`academic_base` 2.4.
*   :composer:`fgtclb/academic-base` 2.4, the same release as this extension.
*   :composer:`fgtclb/category-types` 2.4, the same release as this extension.

..  _upgrade-from-2-3-to-2-4-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~2.4.0' \
        'fgtclb/category-types':'~2.4.0' \
        'fgtclb/academic-programs':'~2.4.0'

A project that requires branch ``2`` as ``2.x-dev`` keeps an inline alias such
as ``2.x-dev as 2.4.x-dev`` working, see :ref:`important-1790796667`.

On a classic installation, install :guilabel:`environment_state_manager` 1.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`category_types` and
:guilabel:`academic_programs` there, in this order. Run the commands of this
page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-2-4-configuration:

Adapt the configuration
=======================

The configuration of this extension changed shape in 2.4: one component per
content element, a separate component for the
:typoscript:`styles.content.getContent` override, the page TSconfig as a
selectable entry and the folder :file:`Configuration/TsConfig/` spelled
:file:`Configuration/TSconfig/`. The complete list of paths and names is in
:ref:`breaking-site-sets-and-static-templates-restructured`.

..  _upgrade-from-2-3-to-2-4-content-elements:

Make the content elements selectable
------------------------------------

The page TSconfig every installation includes hides the content elements
:guilabel:`Program List` and :guilabel:`Program Details` now. The page TSconfig
of a component brings its element back. Enable them on every site that uses
them, with one of the two mechanisms and never with both:

*   **TYPO3 13 with site sets:** the site configuration depends on
    `fgtclb/academic-programs`. The set keeps the name it had in 2.3 and
    includes every component now, so a site that depends on it already needs
    no change. A site may name the component sets instead.
*   **TYPO3 12, or TYPO3 13 without site sets:** in the page record of every
    site root, field :guilabel:`Include static Page TSconfig (from extensions)`
    on the tab :guilabel:`Resources`, add :guilabel:`Academic Programs: All
    components (academic_programs)`, or the entries of the components the site
    uses. The entry is new in 2.4, there is nothing to replace.

Existing content elements keep rendering in the frontend either way. In the
backend, an existing element on a page where its component is not enabled
keeps its type when it is saved. :guilabel:`academic_base` 2.4 shows the stored
type as the selected option, followed by :guilabel:`(not enabled on this
page)`. The warning of the changelog entry, that saving such an element writes
another :sql:`CType`, describes a state the 2.4 release of
:guilabel:`academic_base` no longer has.

..  _upgrade-from-2-3-to-2-4-static-template:

Check the static template
-------------------------

This step concerns sites that include the TypoScript through a
:sql:`sys_template` record, which is every site on TYPO3 12.

The entry of 2.3, :guilabel:`Academic Programs Page Setup`, keeps its stored
value `EXT:academic_programs/Configuration/TypoScript/` and is called
:guilabel:`Academic Programs: Shared plugin settings and page rendering
(academic_programs)` now. It keeps working, but it no longer carries the
:typoscript:`styles.content.getContent` override that 2.3 included with it.

The program page does not need the override any more. A template of the site
package that renders :typoscript:`styles.content.getContent` does, unless
another extension or the site package defines it. In that case replace the
entry with :guilabel:`Academic Programs: All components (academic_programs)`,
or add :guilabel:`Academic Programs: Content load override
(academic_programs)` next to it, in the field :guilabel:`Include TypoScript
sets` on the tab :guilabel:`Advanced Options` of the template record.

The static template entry of :guilabel:`academic_partners` 2.3 stored the same
value as the entry of this extension, so a template record that selected it
shows the entry of this extension now. The upgrade chapter of
:guilabel:`academic_partners` has a step to remove it by hand where the site
does not use this extension. Where it does, the entry stays.

..  _upgrade-from-2-3-to-2-4-program-page-content:

Move customisations of the program page content
-----------------------------------------------

The page template of the page type :guilabel:`Program` renders the
variable :typoscript:`page.10.variables.programContent` now, which the
extension defines on program pages only. A customisation of
:typoscript:`styles.content.getContent` that was meant for program pages, a
content slide or another column for example, no longer reaches them. Move it
into the same condition on :typoscript:`programContent`, see
:ref:`important-program-page-renders-its-own-content`.

..  _upgrade-from-2-3-to-2-4-imports:

Adjust imports of shipped files
-------------------------------

A site package that imports a file of this extension by path checks every
:typoscript:`@import`. An import of a missing file is silent, so a wrong path
shows up as missing configuration, not as an error.

*   :file:`Configuration/TsConfig/` is :file:`Configuration/TSconfig/` now.
    :file:`Configuration/TsConfig/page.tsconfig` is
    :file:`Configuration/TSconfig/Full/page.tsconfig`, and the backend layout
    moved from :file:`Configuration/TsConfig/Page/BackendLayouts/` to
    :file:`Configuration/TSconfig/BackendLayouts/`.
*   :file:`Configuration/TypoScript/setup.typoscript` no longer imports the
    :typoscript:`styles.content.getContent` override. Import
    :file:`Configuration/TypoScript/ContentLoad/setup.typoscript` where the
    site needs it. The old file
    :file:`Configuration/TypoScript/Content/ContentLoad.typoscript` is gone.
*   :file:`Configuration/Sets/AcademicPrograms/constants.typoscript` and
    :file:`setup.typoscript` are gone. Import the files of
    :file:`Configuration/TypoScript/` instead.

..  _upgrade-from-2-3-to-2-4-label-overrides:

Move label overrides
--------------------

The templates read label overrides from the path the TYPO3 documentation
names. An override under :typoscript:`plugin.tx_academic_programs._LOCAL_LANG`
no longer has an effect. Move it to
:typoscript:`plugin.tx_academicprograms._LOCAL_LANG`, or to
:typoscript:`plugin.tx_academicprograms_<plugin>._LOCAL_LANG` for one plugin.
This includes the labels of the filter form. See
:ref:`important-label-overrides-use-the-documented-path` and
:ref:`important-1790383007`.

..  _upgrade-from-2-3-to-2-4-route-enhancer:

Replace a copied route enhancer
-------------------------------

:file:`Configuration/Yaml/Routes.yaml` could not be imported up to 2.3, it was
not valid YAML. It can be imported now and maps the sorting of the program list
into the path:

..  code-block:: yaml
    :caption: config/sites/<site>/config.yaml

    imports:
      - resource: 'EXT:academic_programs/Configuration/Yaml/Routes.yaml'

A site that copied the enhancer of 2.3 into its own site configuration replaces
the copy by that import. A copy that stays needs the :yaml:`plugin`
:yaml:`ProgramList`, the :yaml:`routes` list, and the :yaml:`requirements` and
:yaml:`aspects` of the shipped file, otherwise it enhances nothing. See
:ref:`important-1787055000`.

..  _upgrade-from-2-3-to-2-4-templates:

Adapt templates and assets
==========================

Version 2.4 changes these templates and partials. A copy of one of them in a
site package keeps rendering the copy, so compare each copy with the shipped
file and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Pages/AcademicProgram.html`
        -   Renders :html:`{programContent -> f:format.raw()}` in place of
            :html:`<f:cObject typoscriptObjectPath="styles.content.getContent"/>`.
            A copy that keeps the old line depends on the content load
            override of the previous section.
        -   :ref:`important-program-page-renders-its-own-content`
    *   -   :file:`Partials/Program/DemandCategories.html`
        -   Offers the category types
            :typoscript:`plugin.tx_academicprograms.filter.categoryTypes`
            names, in that order, and puts the ones after
            :typoscript:`filter.visibleCount` into a :guilabel:`More filters`
            section with labels of its own.
        -   :ref:`feature-1790383004`,
            :ref:`important-1790383007`
    *   -   :file:`Pages/AcademicProgram.html`,
            :file:`Partials/Program/Categories.html`,
            :file:`Partials/Program/DemandCategories.html`,
            :file:`Partials/Program/DemandSorting.html`,
            :file:`Partials/Program/Item.html`,
            :file:`Partials/Program/ItemList.html`
        -   The translations pass the extension name :html:`AcademicPrograms`
            instead of :html:`academic_programs`. A copy that keeps the old
            name does not see the label overrides of the previous section.
        -   :ref:`important-label-overrides-use-the-documented-path`
    *   -   :file:`Backend/Partials/PageLayout/Doktype20.html`
        -   Removed, with its registrations
            :typoscript:`templates.typo3/cms-backend.1730990129` and
            :typoscript:`templates.typo3/cms-backend.academic-programs`. It was
            never rendered. The page module shows the categories of a program
            page through :guilabel:`category_types` now, which takes an
            override under :typoscript:`templates.fgtclb/category-types.<key>`.
        -   :ref:`important-1789660802`

A site package that shipped :file:`Academicprogram.html` as the page template
of the page type renames it to :file:`AcademicProgram.html`. The extension sets
:typoscript:`page.10.templateName` itself now. See
:ref:`important-1786968000`.

..  _upgrade-from-2-3-to-2-4-code:

Adapt your code
===============

*   :php:`\FGTCLB\AcademicPrograms\Collection\FileReferenceCollection` is
    deprecated, and calling it raises a deprecation notice. Read the file
    references through
    :php:`\TYPO3\CMS\Core\Resource\FileRepository::findByRelation()`. Which
    references come back differs in hidden and workspace records, the entry
    lists how. See :ref:`deprecation-file-reference-collection`.
*   Code that extends or replaces :php:`ProgramDemand`, the
    :php:`DemandFactory` or the :php:`ProgramRepository` takes the new flag
    :php:`showHiddenRecords` into account. No signature changed. See
    :ref:`important-ace-250-academic-programs`.

..  _upgrade-from-2-3-to-2-4-check:

Check the result
================

*   The new content element wizard offers :guilabel:`Program List` and
    :guilabel:`Program Details` on the pages of every site that uses them, and
    an existing element opens with its type.
*   A program page renders its template and the content of its main column,
    and its page module shows the categories of the page.
*   Pages of the site package that render
    :typoscript:`styles.content.getContent` still render their content.
*   A list that hides a part of its form keeps the preset of that part when a
    visitor uses the rest of the form, see
    :ref:`important-hidden-program-form-parts-keep-their-presets`. A sorting
    that was hidden only to keep the default categories can be shown again.
*   On PostgreSQL, programs that are equal in the chosen ordering may change
    their relative order once, they are ordered by uid now. See
    :ref:`important-list-ordering-ties-are-broken-by-uid`.
