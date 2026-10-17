..  _upgrade-from-2-4-to-3-0:

=========================
Upgrading from 2.4 to 3.0
=========================

Version 3.0 of :guilabel:`academic_programs` runs on TYPO3 13 and 14 only. The
program page renders inside the layout of the site, the content load set of
2.4 is gone, and the facts of a program replace the categories partial. The
templates carry speaking classes in place of the classes of a theme, the icons
are renamed, the controllers are final, and the extension registers the
content element :guilabel:`Course Finder` itself. Work through the sections in
order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-4-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_programs` 3.0 needs these packages. Upgrade them first or
in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, which
    :guilabel:`academic_base` 3.0 requires in place of 1.x. Composer updates it
    on its own. A classic installation updates
    :guilabel:`environment_state_manager` before it updates
    :guilabel:`academic_base`.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.
*   :composer:`fgtclb/category-types` 3.0, the same release as this extension.

These changes have to ship in the same deployment as the update, because a
site or a request fails until they are made:

*   A site that depends on `fgtclb/academic-programs-content-load` answers
    every page with HTTP 500, see
    :ref:`upgrade-from-2-4-to-3-0-content-load`.
*   A subclass or an XCLASS of one of the two program controllers stops with a
    fatal error. A registration of the content type
    `academicprograms_programfinder` in the site package collides with the one
    of this extension. Code that names the removed classes
    :php:`FileReferenceCollection` or :php:`Backend\Tca\Labels` fails. See
    :ref:`upgrade-from-2-4-to-3-0-code`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-4-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.4, which supports
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

..  _upgrade-from-2-4-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/category-types':'~3.0.0' \
        'fgtclb/academic-programs':'~3.0.0'

On a classic installation, update :guilabel:`environment_state_manager` to 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`category_types` and
:guilabel:`academic_programs` there, in this order. Run the commands of this
page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-4-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

The command creates and changes tables and columns and drops nothing. For this
extension it adds two columns, which TYPO3 derives from the TCA:

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

..  _upgrade-from-2-4-to-3-0-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-4-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports what no longer
reaches the academic extensions. For this extension it reports a dependency on
the removed content load set and an XCLASS of a controller, but neither a
subclass that is registered another way nor a registration of the finder. Run
it on the staging copy right after the package update, and fix what it
reports before the deployment:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key and a site it also compares the override folders of the
site with the shipped files and reports an override of a file the extension no
longer ships, :file:`Program/Categories.html` among them:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_programs --site=<site identifier>

Each finding, and what to do about it, is described in the :ref:`upgrade check
<fgtclb/academic-base:upgrade-check>`
of :guilabel:`academic_base`.

..  _upgrade-from-2-4-to-3-0-content-load:

Remove the content load set
---------------------------

The program page renders its content from the variable
:typoscript:`programContent` of its own page object since 2.4. The set
`fgtclb/academic-programs-content-load`, which kept defining
:typoscript:`styles.content.getContent` for every page of the site, its static
template :guilabel:`Academic Programs: Content load override
(academic_programs)` and the file
:file:`Configuration/TypoScript/ContentLoad/setup.typoscript` are removed. No
academic extension defines :typoscript:`styles.content.getContent` any more.
See :ref:`breaking-programs-content-load-set-removed`.

#.  Remove `fgtclb/academic-programs-content-load` from the
    :yaml:`dependencies` of every site configuration and of every set of the
    site package, in the same deployment as the update. A site that depends on
    the aggregate `fgtclb/academic-programs` needs no change.
#.  Remove the static template :guilabel:`Academic Programs: Content load
    override (academic_programs)` from every :sql:`sys_template` record, and an
    :typoscript:`@import` of the removed file from the site package.
#.  A template of the site package that renders
    :typoscript:`styles.content.getContent` defines the object itself:

    ..  code-block:: typoscript

        styles.content.getContent < styles.content.get
        styles.content.getContent.select.where = {#colPos}=0

    An override of :file:`Pages/AcademicProgram.html` that still renders it
    renders :html:`{programContent -> f:format.raw()}` instead.

:bash:`academic:upgrade:check` reports a site that depends on the removed set as
:bash:`unavailable-set`, the removed static template as
:bash:`static-template` and an import of the removed file as
:bash:`typoscript-import`.

..  _upgrade-from-2-4-to-3-0-finder:

Offer the course finder
-----------------------

The new content element :guilabel:`Course Finder`, content type
`academicprograms_programfinder`, opens a program list with a selection
applied, see :ref:`feature-1790367619` and :ref:`program-finder`. A site that
depends on the aggregate set `fgtclb/academic-programs`, or selects the static
template and the page TSconfig :guilabel:`Academic Programs: All components
(academic_programs)`, offers it after the update. A site that names component
sets or entries adds `fgtclb/academic-programs-program-finder`, or the static
template and the page TSconfig :guilabel:`Academic Programs: Program Finder
(academic_programs)`, where its editors should have it.

A site that registered a finder of its own under that content type removes the
registration first, see :ref:`upgrade-from-2-4-to-3-0-code`.

..  _upgrade-from-2-4-to-3-0-route-enhancer:

Import the moved route enhancer
-------------------------------

The route enhancer of the :guilabel:`Course List` moved from
:file:`Configuration/Yaml/Routes.yaml` to
:file:`Configuration/Routes/List.yaml`, and maps the category filter into the
path as well. The former file imports the new one, so a site that imports it
keeps working. Import the new file instead:

..  code-block:: yaml
    :caption: config/sites/<site>/config.yaml

    imports:
      - resource: 'EXT:academic_programs/Configuration/Routes/List.yaml'

The sorting values follow the language of the site now, so the English values a
German page published before, :file:`/title/asc` for instance, answer with a
404. Redirect them where they are linked from elsewhere. See
:ref:`important-1791043405` and :ref:`feature-1791043404`.

The list answers a filter submission with a redirect to a URL that carries the
selection. A route enhancer the site wrote for the program list itself must not
declare :yaml:`defaults` for the sorting, see :ref:`important-1790226103` and
:ref:`feature-1790226102`.

..  _upgrade-from-2-4-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-4-to-3-0-stylesheets:

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

..  _upgrade-from-2-4-to-3-0-icons:

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

..  _upgrade-from-2-4-to-3-0-page:

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
*   A path the site set at the key `100` inside the condition on the program
    page type moves to `50`, or to a key above it to win over the extension.

..  _upgrade-from-2-4-to-3-0-facts:

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

..  _upgrade-from-2-4-to-3-0-overrides:

Compare template overrides
--------------------------

Version 3.0 changes these templates and partials. A copy of one of them in a
site package keeps rendering the copy, so compare each copy with the shipped
file and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Pages/AcademicProgram.html`
        -   Renders the layout of the site and the partials below
            :file:`Program/Page/`, among them the facts and the application
            link.
        -   :ref:`upgrade-from-2-4-to-3-0-page`,
            :ref:`feature-1790703178`
    *   -   :file:`Templates/Details/Show.html`,
            :file:`Partials/Program/Item.html`
        -   Render the facts through :file:`Program/Facts.html`. The item
            renders the image through :file:`Academic/Image.html` of
            :guilabel:`academic_base`, and can show the most specific category
            of a type.
        -   :ref:`upgrade-from-2-4-to-3-0-facts`,
            :ref:`upgrade-from-2-4-to-3-0-stylesheets`,
            :ref:`feature-1790681426`
    *   -   :file:`Templates/Program/List.html`,
            :file:`Partials/Program/SortingAndFilters.html`,
            :file:`Partials/Program/DemandCategories.html`,
            :file:`Partials/Program/DemandSorting.html`
        -   Carry the wrapper, the region, the submit button and the marks the
            module of the list reads to update the list in place, and load
            the module unless :typoscript:`settings.assets.js` switches it off.
            The selects have no :html:`onchange` handler any more, and the
            extension allows none in the Content Security Policy. A copy that
            keeps the handler needs its hash in the :file:`csp.yaml` of the
            site.
        -   :ref:`important-1790982307`,
            :ref:`important-ace-924-academic-programs`,
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
        -   Offers the filter types of the content element or of the site, and
            labels an own category type by its title.
        -   :ref:`feature-1790361244`,
            :ref:`feature-1791035982`
    *   -   :file:`Partials/Program/Header.html`
        -   Takes the class of the heading as :html:`class` in place of
            :html:`positionClass`.
        -   :ref:`upgrade-from-2-4-to-3-0-stylesheets`
    *   -   Every file above, and :file:`Partials/Program/ItemList.html`
        -   Speaking classes in place of the classes of a theme.
        -   :ref:`upgrade-from-2-4-to-3-0-stylesheets`
    *   -   :file:`Partials/Program/Categories.html`
        -   Removed.
        -   :ref:`upgrade-from-2-4-to-3-0-facts`

The upgrade check with the extension key and a site reports an override of a
file the extension no longer ships, :file:`Program/Categories.html` among them,
see :ref:`upgrade-from-2-4-to-3-0-upgrade-check`.

..  _upgrade-from-2-4-to-3-0-code:

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
*   :php:`\FGTCLB\AcademicPrograms\Collection\FileReferenceCollection`,
    deprecated in 2.4, is removed. Read the file references through
    :php:`\TYPO3\CMS\Core\Resource\FileRepository::findByRelation()`, see
    :ref:`breaking-file-reference-collection-removed`.
*   :php:`\FGTCLB\AcademicPrograms\Backend\Tca\Labels` is removed. Remove a
    :php:`label_userFunc` that names it, it never changed a label, see
    :ref:`breaking-tca-labels-class-removed`.
*   :php:`\FGTCLB\AcademicPrograms\DataProcessing\ProgramDataProcessor` takes
    the facts builder, the data factory and the event dispatcher as
    constructor arguments. A subclass passes them on and is registered as a
    public service, or is replaced by a listener of
    :php:`ModifyProgramDataEvent`. See :ref:`feature-1790604743`.
*   The FlexForm data structure of the :guilabel:`Course List` is one file now,
    :file:`Configuration/FlexForms/ProgramListSettings.xml`. The files below
    :file:`Configuration/FlexForms/Core12/` and :file:`Core13/` are gone. Code
    of the site that names one of them by path names the new file. The field
    names are unchanged. See
    :ref:`important-programs-flexform-data-structure-moved`.

..  _upgrade-from-2-4-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension key,
    see :ref:`upgrade-from-2-4-to-3-0-upgrade-check`. Run it again after each
    configuration step, and in the deployment pipeline.
*   The content elements are offered as :guilabel:`Course List`,
    :guilabel:`Course Data` and :guilabel:`Course Finder` now, see
    :ref:`important-content-element-titles-and-descriptions`.
*   Every page of a site answers, none with HTTP 500.
*   A program page renders inside the layout of the site, with its content and
    its facts.
*   The program list, the details and the finder look as the site intends,
    with the speaking classes, and the list updates in place when a filter
    changes.
*   A list element with a hidden sorting select keeps the default sorting of
    the element when it is filtered now, see :ref:`important-1790370061`.
*   A list keeps its default categories of the category types a finder or its
    own filter does not offer, see
    :ref:`important-list-keeps-default-categories-of-types-not-submitted`.
