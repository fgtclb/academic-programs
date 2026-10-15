..  _upgrade-from-2-3-to-3-0:

=========================
Upgrading from 2.3 to 3.0
=========================

This page takes an installation of :guilabel:`academic_programs` 2.3 straight
to 3.0, without the step to 2.4. Version 2.4 brings the selectable content
elements, the static template, the page content and new paths of the shipped
files. Version 3.0 runs on TYPO3 13 and 14 only, renders the program page
inside the layout of the site, replaces the categories partial with the facts
of a program, carries speaking classes, renames the icons, makes the
controllers final and registers the content element :guilabel:`Course Finder`
itself. The page leaves out the 2.4 states that 3.0 replaces: the deprecated
file reference collection, see :ref:`deprecation-file-reference-collection`,
and the content load component, which the :ref:`2.4 changelog entry on the
restructured configuration
<breaking-site-sets-and-static-templates-restructured>` names. Work through the
sections in order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-3-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_programs` 3.0 needs these packages. Upgrade them first or
in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, new since 2.4, which
    :guilabel:`academic_base` 3.0 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` before
    it activates :guilabel:`academic_base` 3.0.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.
*   :composer:`fgtclb/category-types` 3.0, the same release as this extension.

These changes have to ship in the same deployment as the update, because a
site or a request fails until they are made:

*   A site without site sets includes the page TSconfig of the components, or
    the content elements are not offered, see
    :ref:`upgrade-from-2-3-to-3-0-content-elements`.
*   A subclass or an XCLASS of one of the two program controllers stops with a
    fatal error. A registration of the content type
    `academicprograms_programfinder` in the site package collides with the one
    of this extension. Code that names the removed classes
    :php:`FileReferenceCollection` or :php:`Backend\Tca\Labels` fails. See
    :ref:`upgrade-from-2-3-to-3-0-code`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-3-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.3, which supports
TYPO3 13.4. Version 3.0 no longer installs on TYPO3 12, and Composer and the
Extension Manager refuse it next to an older patch release of TYPO3 13.

Stay on TYPO3 13 until the upgrade is complete when the installation also has
:guilabel:`academic_jobs`, :guilabel:`academic_persons`,
:guilabel:`academic_persons_edit` or :guilabel:`academic_projects`. Their
content element wizards read the column :sql:`tt_content.list_type`, which
TYPO3 14 removes, so they only run on TYPO3 13. Move to TYPO3 14.3.7 or later
once they have run.

See :ref:`breaking-removed-typo3-v12-support` and
:ref:`important-minimum-typo3-versions`.

..  _upgrade-from-2-3-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/category-types':'~3.0.0' \
        'fgtclb/academic-programs':'~3.0.0'

On a classic installation, install :guilabel:`environment_state_manager` 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`category_types` and
:guilabel:`academic_programs` there, in this order. Run the commands of this
page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

The command creates and changes tables and columns and drops nothing. For this
extension it adds two columns in 3.0, which TYPO3 derives from the TCA:

..  list-table::
    :header-rows: 1

    *   -   Column
        -   Change
    *   -   :sql:`pages.application_link`,
            :sql:`pages.application_link_label`
        -   Added. The link to an application and its label, on the tab
            :guilabel:`Program` of a program page. A page shows no button until
            an editor sets a link. See :ref:`feature-1790703178`.

An installation that added a column :sql:`application_link` of its own removes
its TCA and its :file:`ext_tables.sql` line for it, the data stays and the
program page renders it. One that kept the link in a column of another name
copies it into the new column once, the changelog entry has the query.

The comparison also applies the changes of the index definitions TYPO3
13.4.35 and 14.3.7 bring.

..  _upgrade-from-2-3-to-3-0-configuration:

Adapt the configuration
=======================

The configuration of this extension is delivered per component since 2.4: one
component per content element, the page TSconfig as a selectable entry and the
folder :file:`Configuration/TsConfig/` spelled :file:`Configuration/TSconfig/`.
The complete list of paths and names is in the 2.4 changelog entry the
introduction of this page links. The content load component that entry names
was removed again in 3.0, see :ref:`upgrade-from-2-3-to-3-0-content`.

..  _upgrade-from-2-3-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports what no longer
reaches the academic extensions. For this extension it reports an XCLASS of a
controller and a stored path of 2.3 that no longer exists. Run it on the
staging copy right after the package update, and again once the steps of this
section are done:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key and a site it also compares the override folders of the
site with the shipped files and reports an override of a file the extension no
longer ships, :file:`Program/Categories.html` among them:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_programs --site=<site identifier>

Each finding, and what to do about it, is described in the `upgrade check
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/UpgradeCheck/Index.html>`__
of :guilabel:`academic_base`.

..  _upgrade-from-2-3-to-3-0-content-elements:

Make the content elements selectable
------------------------------------

The page TSconfig every installation includes hides the content elements
:guilabel:`Course List`, :guilabel:`Course Data` and the new
:guilabel:`Course Finder`. The page TSconfig of a component brings its element
back. Enable them on every site that uses them, with one of the two mechanisms
and never with both:

*   **With site sets:** the site configuration depends on
    `fgtclb/academic-programs`. The set keeps the name it had in 2.3 and
    includes every component, the finder set
    `fgtclb/academic-programs-program-finder` among them, so a site that
    depends on it already needs no change. A site may name the component sets
    instead.
*   **Without site sets:** in the page record of every site root, field
    :guilabel:`Include static Page TSconfig (from extensions)` on the tab
    :guilabel:`Resources`, add :guilabel:`Academic Programs: All components
    (academic_programs)`, or the entries of the components the site uses.

Until a component is included, an existing content element of it keeps
rendering and keeps its type: :guilabel:`academic_base` shows the stored type
in the field :guilabel:`Type` followed by
:guilabel:`(not enabled on this page)`, and saving keeps it. The warning of the
2.4 changelog entry that saving such a record rewrites its type describes the
state before a fix that :guilabel:`academic_base` 2.4 carries as well, see
`its changelog entry <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/2.4/Important-HiddenContentTypeStaysSelectable.html>`__.
A content element that a page TSconfig of your own already turned into another
type is not repaired. Correct it by hand from its record history.

The finder opens a program list with a selection applied, see
:ref:`feature-1790367619` and :ref:`program-finder`. A site that registered a
finder of its own under its content type removes that registration first, see
:ref:`upgrade-from-2-3-to-3-0-code`.

..  _upgrade-from-2-3-to-3-0-static-template:

Check the static template
-------------------------

This step concerns sites that include the TypoScript through a
:sql:`sys_template` record.

The entry of 2.3, :guilabel:`Academic Programs Page Setup`, keeps its stored
value `EXT:academic_programs/Configuration/TypoScript/` and is called
:guilabel:`Academic Programs: Shared plugin settings and page rendering
(academic_programs)` now. It keeps working, but it no longer defines
:typoscript:`styles.content.getContent`, see
:ref:`upgrade-from-2-3-to-3-0-content`.
:guilabel:`Academic Programs: All components (academic_programs)` carries the
same TypoScript, and is the entry to select for a new site.

The static template entry of :guilabel:`academic_partners` 2.3 stored the same
value as the entry of this extension, so a template record that selected it
shows the entry of this extension now. The upgrade chapter of
:guilabel:`academic_partners` has a step to remove it by hand where the site
does not use this extension. Where it does, the entry stays.

..  _upgrade-from-2-3-to-3-0-imports:

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
*   :file:`Configuration/TypoScript/Content/ContentLoad.typoscript` is gone,
    without a successor, see :ref:`upgrade-from-2-3-to-3-0-content`.
*   :file:`Configuration/Sets/AcademicPrograms/constants.typoscript` and
    :file:`setup.typoscript` are gone. Import the files of
    :file:`Configuration/TypoScript/` instead.

:bash:`academic:upgrade:check` reports an import of a missing file in a
TypoScript record as :bash:`typoscript-import` and in page TSconfig as
:bash:`tsconfig-import`.

..  _upgrade-from-2-3-to-3-0-content:

Replace the global content object
---------------------------------

Version 2.3 defined :typoscript:`styles.content.getContent` for every page of
the site, through :file:`Configuration/TypoScript/setup.typoscript`, and the
program page rendered it. The program page renders the content of its main
column from the variable :typoscript:`programContent` of its own page object
now, see :ref:`important-program-page-renders-its-own-content`, and no academic
extension defines :typoscript:`styles.content.getContent` any more, see
:ref:`breaking-programs-content-load-set-removed`.

*   Move a customisation of :typoscript:`styles.content.getContent` that was
    meant for program pages, a content slide or another column for example, to
    :typoscript:`page.10.variables.programContent`, inside the condition on the
    program page type, see :ref:`program-page-content`.
*   A template of the site package that renders
    :typoscript:`styles.content.getContent` defines the object itself:

    ..  code-block:: typoscript

        styles.content.getContent < styles.content.get
        styles.content.getContent.select.where = {#colPos}=0

..  _upgrade-from-2-3-to-3-0-label-overrides:

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

..  _upgrade-from-2-3-to-3-0-route-enhancer:

Import the route enhancer
-------------------------

:file:`Configuration/Yaml/Routes.yaml` could not be imported up to 2.3, it was
not valid YAML, see :ref:`important-1787055000`. The enhancer of the
:guilabel:`Course List` lives in :file:`Configuration/Routes/List.yaml` now,
maps the category filter and the sorting into the path, and translates the
sorting values into the language of the site. The former file imports it.

..  code-block:: yaml
    :caption: config/sites/<site>/config.yaml

    imports:
      - resource: 'EXT:academic_programs/Configuration/Routes/List.yaml'

A site that copied the enhancer of 2.3 into its own site configuration replaces
the copy by that import. See :ref:`important-1791043405` and
:ref:`feature-1791043404`.

The list answers a filter submission with a redirect to a URL that carries the
selection. A route enhancer the site keeps for the program list must not
declare :yaml:`defaults` for the sorting, see :ref:`important-1790226103` and
:ref:`feature-1790226102`.

..  _upgrade-from-2-3-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-3-to-3-0-stylesheets:

Style the new markup
--------------------

The templates of this extension carry speaking classes now, such as `ace-item`,
`ace-list`, `ace-attribute`, `ace-label` and `ace-value`, in place of the card,
list group and utility classes of a theme. The extension ships no styles for
them. Move the selectors of the site stylesheet from the old classes to the new
ones, the changelog entry lists them side by side, see
:ref:`breaking-programs-speaking-frontend-classes`. :ref:`styling` shows the
markup of every view and links the stylesheet partial the development instances
of the academic extensions use, :ref:`styling-example`.

The images of the program list and the program page render as a
:html:`<picture>` through the image partial of :guilabel:`academic_base`. CSS
that selects the image as a direct child of its container adjusts to the
wrapper. A plugin view whose partial root paths the site replaced completely
needs the path of :guilabel:`academic_base` below its own:

..  code-block:: typoscript

    plugin.tx_academicprograms.view.partialRootPaths {
        -1 = EXT:academic_base/Resources/Private/Partials/
    }

See :ref:`breaking-programs-images-render-as-picture`.

..  _upgrade-from-2-3-to-3-0-icons:

Replace the renamed icons
-------------------------

The icons of this extension are new drawings, registered in a way that follows
the colour scheme of the backend and the colour of the text in the frontend.

*   The identifier :php:`academic-programs` is gone. Replace it with
    :php:`tx-academicprograms-doktype-program` where the page type is meant and
    with :php:`tx-academicprograms-plugin-programs` where a content element is
    meant, in TCA, page TSconfig, templates and CSS selectors.
*   The credit points icon is a frontend icon of
    :file:`Configuration/FrontendIcons.php`. A replacement of it is registered
    in the :file:`Configuration/FrontendIcons.php` of the site package.
*   A path to a file below :file:`Resources/Public/Icons/CategoryTypes/` points
    at the new file of the changelog entry.

See :ref:`breaking-programs-credit-points-icon-is-a-frontend-icon`. The
category type icons in the facts render as an inline :html:`<svg>` now, so CSS
that addressed an :html:`<img>` addresses the :html:`<svg>`, see
:ref:`breaking-programs-record-and-category-icons-follow-the-colour-scheme`.

..  _upgrade-from-2-3-to-3-0-page:

Render program pages in the site layout
---------------------------------------

The page template of the program page type renders the section :html:`Main` of
the layout :file:`Default` of the site package, through the partials below
:file:`Program/Page/`. A program page shows the header, the navigation and the
footer of the site now. See
:ref:`breaking-program-page-renders-inside-the-site-layout`.

*   A site package whose layout for program pages has another name sets
    :typoscript:`plugin.tx_academicprograms.page.layout`, see
    :ref:`program-page-layout`.
*   :typoscript:`plugin.tx_academicprograms.page.listPid` names the page the
    link back to the program list leads to. Without it the page shows no link.
*   An override of :file:`Pages/AcademicProgram.html` made to change one part
    moves that change to the matching partial below :file:`Program/Page/`, see
    :ref:`program-page-partials`, and the override goes. An override that stays
    renders no application link, which the partial
    :file:`Program/Page/CallToAction.html` renders.
*   A site package that shipped :file:`Academicprogram.html` as the page
    template of the page type renames it to :file:`AcademicProgram.html`. The
    extension sets :typoscript:`page.10.templateName` itself. See
    :ref:`important-1786968000`.

..  _upgrade-from-2-3-to-3-0-facts:

Render the facts
----------------

The partial :file:`Program/Categories.html` is removed. The program page, the
:guilabel:`Course Data` content element and the card of the :guilabel:`Course
List` render their facts through :file:`Program/Facts.html` and
:file:`Program/Facts/Item.html`, from the field lists
:typoscript:`plugin.tx_academicprograms.facts.fields` and
:typoscript:`card.fields`. See
:ref:`breaking-program-categories-partial-removed` and :ref:`program-facts`.

*   Move a change of one fact to an override of :file:`Program/Facts/Item.html`,
    and a change of the whole list to an override of :file:`Program/Facts.html`.
*   Set the field lists where the override changed which facts are shown or
    their order.
*   An override of a template that renders
    :html:`<f:render partial="Program/Categories" ... />` fails. Render
    :file:`Program/Facts` there, and remove the override of
    :file:`Program/Categories.html`.

..  _upgrade-from-2-3-to-3-0-overrides:

Compare template overrides
--------------------------

Versions 2.4 and 3.0 change these templates and partials. A copy of one of them
in a site package keeps rendering the copy, so compare each copy with the
shipped file of 3.0 and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Pages/AcademicProgram.html`
        -   Renders the layout of the site and the partials below
            :file:`Program/Page/`, among them the facts and the application
            link, and the content from :html:`{programContent}` in place of
            :typoscript:`styles.content.getContent`.
        -   :ref:`upgrade-from-2-3-to-3-0-page`,
            :ref:`upgrade-from-2-3-to-3-0-content`,
            :ref:`feature-1790703178`
    *   -   :file:`Templates/Details/Show.html`,
            :file:`Partials/Program/Item.html`
        -   Render the facts through :file:`Program/Facts.html`. The item
            renders the image through :file:`Academic/Image.html` of
            :guilabel:`academic_base`, and can show the most specific category
            of a type.
        -   :ref:`upgrade-from-2-3-to-3-0-facts`,
            :ref:`upgrade-from-2-3-to-3-0-stylesheets`,
            :ref:`feature-1790681426`
    *   -   :file:`Templates/Program/List.html`,
            :file:`Partials/Program/SortingAndFilters.html`,
            :file:`Partials/Program/DemandCategories.html`,
            :file:`Partials/Program/DemandSorting.html`
        -   Carry the wrapper, the region, the submit button and the marks the
            module of the list reads to update the list in place, and load
            the module unless :typoscript:`settings.assets.js` switches it off.
            The selects have no :html:`onchange` handler any more.
        -   :ref:`important-1790982307`,
            :ref:`feature-1791566505`
    *   -   :file:`Templates/Program/List.html`,
            :file:`Templates/Details/Show.html`
        -   Render the header of the content element while
            :typoscript:`renderContentElementHeader` is switched on.
        -   :ref:`feature-1790440963`
    *   -   :file:`Partials/Program/SortingAndFilters.html`
        -   Renders the new partials :file:`Program/ActiveFilters.html` and
            :file:`Program/ResultCount.html`.
        -   :ref:`feature-1790946476`
    *   -   :file:`Partials/Program/DemandCategories.html`
        -   Offers the category types the content element or
            :typoscript:`plugin.tx_academicprograms.filter.categoryTypes`
            names, in that order, puts the ones after
            :typoscript:`filter.visibleCount` into a :guilabel:`More filters`
            section, and labels an own category type by its title.
        -   :ref:`feature-1790383004`,
            :ref:`feature-1790361244`,
            :ref:`feature-1791035982`
    *   -   :file:`Partials/Program/Header.html`
        -   Takes the class of the heading as :html:`class` in place of
            :html:`positionClass`.
        -   :ref:`upgrade-from-2-3-to-3-0-stylesheets`
    *   -   Every file above, and :file:`Partials/Program/ItemList.html`
        -   Speaking classes in place of the classes of a theme. The
            translations pass the extension name :html:`AcademicPrograms`
            instead of :html:`academic_programs`, otherwise they miss the
            label overrides of the configuration step.
        -   :ref:`upgrade-from-2-3-to-3-0-stylesheets`,
            :ref:`upgrade-from-2-3-to-3-0-label-overrides`
    *   -   :file:`Partials/Program/Categories.html`
        -   Removed.
        -   :ref:`upgrade-from-2-3-to-3-0-facts`
    *   -   :file:`Backend/Partials/PageLayout/Doktype20.html`
        -   Removed, with its registrations
            :typoscript:`templates.typo3/cms-backend.1730990129` and
            :typoscript:`templates.typo3/cms-backend.academic-programs`. It was
            never rendered. The page module shows the categories of a program
            page through :guilabel:`category_types`, which takes an override
            under :typoscript:`templates.fgtclb/category-types.<key>`.
        -   :ref:`important-1789660802`

The upgrade check with the extension key and a site reports an override of a
file the extension no longer ships, :file:`Program/Categories.html` among them,
see :ref:`upgrade-from-2-3-to-3-0-upgrade-check`.

..  _upgrade-from-2-3-to-3-0-code:

Adapt your code
===============

*   :php:`ProgramController` and :php:`DetailsController` are final. A
    subclass, and an XCLASS of one of them, stop with a fatal error. Remove
    them, and the :php:`configurePlugin()` call or the XCLASS registration that
    points at them, in the same deployment as the update. Move what they did
    to a listener of the program events or of the plugin view event, see
    :ref:`breaking-1791043408` and :ref:`developers`.
*   This extension registers the content type `academicprograms_programfinder`
    with the plugin `ProgramFinder` now. A site package or project extension
    that registered a finder under these names removes its type item, its
    FlexForm, its :php:`configurePlugin()` call, its controller code and its
    page TSconfig, in the same deployment as the update. Stored finder records
    keep their type and render with the finder of this extension. See
    :ref:`breaking-program-finder-registered-upstream`.
*   :php:`\FGTCLB\AcademicPrograms\Collection\FileReferenceCollection` is
    removed. Read the file references through
    :php:`\TYPO3\CMS\Core\Resource\FileRepository::findByRelation()`. Which
    references come back differs in hidden and workspace records, the entry
    lists how. See :ref:`breaking-file-reference-collection-removed`.
*   :php:`\FGTCLB\AcademicPrograms\Backend\Tca\Labels` is removed. Remove a
    :php:`label_userFunc` that names it, it never changed a label, see
    :ref:`breaking-tca-labels-class-removed`.
*   :php:`\FGTCLB\AcademicPrograms\DataProcessing\ProgramDataProcessor` takes
    the facts builder, the data factory and the event dispatcher as
    constructor arguments. A subclass passes them on and is registered as a
    public service, or is replaced by a listener of
    :php:`ModifyProgramDataEvent`. See :ref:`feature-1790604743`.
*   Code that extends or replaces :php:`ProgramDemand`, the
    :php:`DemandFactory` or the :php:`ProgramRepository` takes the flag
    :php:`showHiddenRecords` into account, new since 2.4. See
    :ref:`important-ace-250-academic-programs`.
*   The FlexForm data structure of the :guilabel:`Course List` is one file now,
    :file:`Configuration/FlexForms/ProgramListSettings.xml`. The files below
    :file:`Configuration/FlexForms/Core12/` and :file:`Core13/` are gone. Code
    of the site that names one of them by path names the new file. The field
    names are unchanged. See
    :ref:`important-programs-flexform-data-structure-moved`.

..  _upgrade-from-2-3-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension key,
    see :ref:`upgrade-from-2-3-to-3-0-upgrade-check`. Run it again after each
    configuration step, and in the deployment pipeline.
*   The new content element wizard offers :guilabel:`Course List`,
    :guilabel:`Course Data` and :guilabel:`Course Finder` on the pages of every
    site that uses them, see
    :ref:`important-content-element-titles-and-descriptions`, and an existing
    element opens with its type.
*   A program page renders inside the layout of the site, with the content of
    its main column and its facts, and its page module shows the categories of
    the page.
*   Pages of the site package that render
    :typoscript:`styles.content.getContent` still render their content.
*   The program list, the details and the finder look as the site intends,
    with the speaking classes, and the list updates in place when a filter
    changes.
*   A list element with a hidden sorting select keeps the default sorting of
    the element when it is filtered now, see :ref:`important-1790370061`.
*   On PostgreSQL, programs that are equal in the chosen ordering may change
    their relative order once, they are ordered by uid now. See
    :ref:`important-list-ordering-ties-are-broken-by-uid`.
