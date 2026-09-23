..  _breaking-programs-content-load-set-removed:

==================================================================
Breaking: Program pages render their content without a global path
==================================================================

Description
===========

The page template of the page type :guilabel:`Academic program` rendered the
content elements of the page through the global TypoScript object
:typoscript:`styles.content.getContent`. Only the opt-in set
`fgtclb/academic-programs-content-load`, its static template and the static
template :guilabel:`Academic Programs: All components`, which included it,
defined it, for every page of the site. A site on the component sets or the
component static templates without them got an exception on every program
page:

..  code-block:: text

    No Content Object definition found at TypoScript object path "styles.content.getContent"

From 3.0 on, the page object of the program page type carries the content
itself, as the variable :typoscript:`programContent`:

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
and on a :typoscript:`PAGEVIEW` page object alike. Every set of this extension
delivers it.

The site wide override is not needed any more, and it is removed:

*   the set `fgtclb/academic-programs-content-load`;
*   the dependency on it in the aggregate set `fgtclb/academic-programs`;
*   the static template
    :guilabel:`Academic Programs: Content load override (academic_programs)`,
    stored as `EXT:academic_programs/Configuration/TypoScript/ContentLoad`,
    and the entry for it in
    :guilabel:`Academic Programs: All components (academic_programs)`;
*   the file
    :file:`EXT:academic_programs/Configuration/TypoScript/ContentLoad/setup.typoscript`.

Impact
======

*   A site configuration that names `fgtclb/academic-programs-content-load` as
    a dependency, directly or through a set of the site package, fails
    completely: TYPO3 answers every page of the site with HTTP 500 and the
    message *"Site <identifier> depends on unavailable sets:
    fgtclb/academic-programs-content-load"*. A site that depends on the
    aggregate `fgtclb/academic-programs` is not affected.
*   :typoscript:`styles.content.getContent` is no longer defined by this
    extension. On a site that includes neither the content-load set of
    :php:`EXT:academic_partners` nor that of :php:`EXT:academic_projects` -
    directly, through their aggregate sets or through their static
    templates - a template of the site package that renders it through
    :html:`f:cObject` throws the exception above.
*   A template override of :file:`AcademicProgram.html` that still renders
    :typoscript:`styles.content.getContent` throws the same exception on such
    a site.
*   A customisation of :typoscript:`styles.content.getContent`, for example
    one that slides the content from the parent pages, no longer reaches
    program pages. The same holds for a customisation of
    :typoscript:`styles.content.get` that was parsed before the override:
    the override copied it into :typoscript:`styles.content.getContent`.
*   A :sql:`sys_template` record that selects the removed static template, or
    imports the removed file, gets nothing for it, without a message.

Affected Installations
======================

Installations that name the content load set or select its static template,
that render :typoscript:`styles.content.getContent` in a template of their own,
or that customised that object for program pages.

The console command :bash:`academic:upgrade:check` of
:php:`EXT:academic_base` reports each of the stored references: a site that
depends on the removed set as :bash:`unavailable-set`, the removed static
template as :bash:`static-template` and an :typoscript:`@import` of the removed
file in a TypoScript record as :bash:`typoscript-import`.

Migration
=========

*   Remove `fgtclb/academic-programs-content-load` from the `dependencies` of
    the site configuration and of every set of the site package. The aggregate
    `fgtclb/academic-programs`, or the component sets, deliver everything a
    program page needs.
*   Remove the static template from the :sql:`sys_template` record, and an
    :typoscript:`@import` of the removed file from a site package.
*   A template override of :file:`AcademicProgram.html` renders
    :html:`{programContent -> f:format.raw()}` instead of the
    :html:`f:cObject` call.
*   Move a customisation of :typoscript:`styles.content.getContent` for
    program pages to :typoscript:`page.10.variables.programContent`, inside the
    same condition - see :ref:`program-page-content`.
*   A site package that renders :typoscript:`styles.content.getContent` in a
    template of its own defines the object itself, for example from
    :typoscript:`styles.content.get`, which TYPO3 defines on every site:

    ..  code-block:: typoscript

        styles.content.getContent < styles.content.get
        styles.content.getContent.select.where = {#colPos}=0

..  index:: Frontend, TypoScript, ext:academic_programs
