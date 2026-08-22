..  _breaking-site-sets-and-static-templates-restructured:

===============================================================
Breaking: Site sets and static templates have been restructured
===============================================================

Description
===========

The TypoScript and the page TSconfig of this extension were shipped twice: the
static template read :file:`Configuration/TypoScript/`, and the site set
:yaml:`fgtclb/academic-programs` shipped its own :file:`constants.typoscript`
and :file:`setup.typoscript`, each of them a single :typoscript:`@import` of
that folder. The page TSconfig existed only as the wizard file
:file:`Configuration/TsConfig/page.tsconfig`, pulled in by a glob in
:file:`Configuration/page.tsconfig`, and was not selectable on a page at all.

Both mechanisms now read one physical copy of every file, and both of them
deliver the extension per component instead of as one block:

*   :file:`Configuration/TypoScript/` keeps the shared
    :typoscript:`plugin.tx_academicprograms` block and the :typoscript:`page`
    object of the page type. Both content elements are driven by the one
    plugin, so that block is shipped once and every component folder names it in
    a one-line :file:`include_static_file.txt`.
*   :file:`Configuration/TypoScript/ProgramList/` and
    :file:`Configuration/TypoScript/ProgramDetails/` are the two component
    folders — what the static template registers *and* what the matching set
    points its :yaml:`typoscript` key at.
*   :file:`Configuration/TSconfig/<Component>/page.tsconfig` holds the page
    TSconfig of a component and is what the page field :guilabel:`Page TSconfig`
    offers *and* what the set points its :yaml:`pagets` key at.
*   :file:`Configuration/TypoScript/Full/` and
    :file:`Configuration/TSconfig/Full/page.tsconfig` are the aggregates for
    installations that do not use site sets.

The directory :file:`Configuration/TsConfig/` is now spelled
:file:`Configuration/TSconfig/` — the spelling TYPO3 itself uses for the term,
and the one this extension family settled on. Every path below it changed with
it, and the migration table lists them.

The content elements are now **hidden by default**. The always-included
:file:`Configuration/page.tsconfig` removes both content element types from
the selectable ones, and the page TSconfig of a component adds its own back — so
an element is offered where it is wanted instead of on every page of every
installation. The TCA registration itself did not move, so the frontend renders
existing records exactly as before. Editing such a record in the backend is a
different matter — read the warning below before upgrading.

The :typoscript:`styles.content.getContent` override that
:file:`Configuration/TypoScript/setup.typoscript` used to import unconditionally
is a component of its own now,
:file:`Configuration/TypoScript/ContentLoad/setup.typoscript`. It redefines a
global TypoScript object path for every page of a site, so it must be possible
to take the content elements without it.

The page type :guilabel:`Academic program` (doktype 20) and its backend layout
:guilabel:`AcademicProgram` stay installation-wide. They are values stored on
:sql:`pages` records and are not, and must not be, part of any opt-in set. The
file that defines the layout moved from
:file:`Configuration/TsConfig/Page/BackendLayouts/` to
:file:`Configuration/TSconfig/BackendLayouts/`, where the sibling extensions of
this family keep theirs; the identifier :sql:`pagets__AcademicProgram` a page
record stores is unchanged.

Impact
======

A :sql:`sys_template` record that selected the static template of this extension
keeps its stored value and keeps working: the registered folder is unchanged,
only its label is. What it no longer delivers is the
:typoscript:`styles.content.getContent` override, which moved into a component
of its own.

A site package that imported one of the moved files by path fails to resolve it.
:typoscript:`@import` of a missing file is silent, so this shows up as missing
configuration rather than as an error message.

Both content elements are no longer offered in the backend until the page
TSconfig of their component is included, through the site set or through the
page field :guilabel:`Page TSconfig`.

..  warning::

    Do not open an existing record of one of these content elements in the
    backend form on a page that does not include that page TSconfig. An item
    removed through :typoscript:`TCEFORM.tt_content.CType.removeItems` is
    excluded from the :guilabel:`[ invalid value ]` fallback TYPO3 otherwise
    adds for a stored value it does not know, and the stored value is dropped
    from the form data as well. The field :guilabel:`Type` therefore comes up
    with nothing selected, and **saving the record writes whatever the browser
    preselected into** :sql:`CType` — the record silently becomes another
    content element. The frontend keeps rendering it correctly until that
    happens.

    Include the page TSconfig of the component on every page tree that holds
    such records, and do it before editing them.

..  warning::

    The Fluid template of the page type renders
    :typoscript:`styles.content.getContent` through
    :html:`<f:cObject typoscriptObjectPath="styles.content.getContent"/>`, and
    that ViewHelper throws when the path is undefined. A site that deliberately
    opts out of `fgtclb/academic-programs-content-load` and still uses the page
    type has to define :typoscript:`styles.content.getContent` itself.

The set :yaml:`fgtclb/academic-programs` keeps its name and keeps delivering
everything, so a site configuration that depends on it needs no change.

Site sets arrived in TYPO3 v13.1 and do nothing at all on TYPO3 v12: the core has
no set API there and the folder :file:`Configuration/Sets/` is never read.
Everything above about the static templates and the page field
:guilabel:`Page TSconfig` therefore applies to both TYPO3 versions this extension
supports, and everything about sets applies to TYPO3 v13 only. An installation on
v12 has to add the page TSconfig entry described under :guilabel:`Migration`, and
has no alternative to it.

The duplicated backend template registration is gone
====================================================

This extension registered its :guilabel:`EXT:backend` template override twice,
under two different keys and pointing at the same directory:
:typoscript:`templates.typo3/cms-backend.1730990129` in
:file:`Configuration/page.tsconfig` and
:typoscript:`templates.typo3/cms-backend.academic-programs` in
:file:`Configuration/TsConfig/page.tsconfig`, which the first file globbed in.
:php:`BackendViewFactory` does not deduplicate, so
:file:`fgtclb/academic-programs:Resources/Private/Backend` was appended twice to
every backend template, layout and partial root path on every page render.

Only :typoscript:`templates.typo3/cms-backend.academic-programs` is left, in
:file:`Configuration/page.tsconfig`, which is what the sibling extensions of this
family use. Nothing an integrator configures changes with it — but an own site
package that overrode the numeric key
:typoscript:`templates.typo3/cms-backend.1730990129` has to be adjusted.

Affected Installations
======================

Installations that import one of the shipped files from an own site package,
that use one of the content elements of this extension without including its
page TSconfig, or that relied on the :typoscript:`styles.content.getContent`
override arriving with the plugin configuration.

Migration
=========

Adjust every :typoscript:`@import` in an own site package:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_programs/Configuration/TypoScript/constants.typoscript`
        -   Unchanged.
    *   -   `EXT:academic_programs/Configuration/TypoScript/setup.typoscript`
        -   Unchanged, but it no longer imports the
            :typoscript:`styles.content` override below.
    *   -   `EXT:academic_programs/Configuration/TypoScript/Content/ContentLoad.typoscript`
        -   `EXT:academic_programs/Configuration/TypoScript/ContentLoad/setup.typoscript`
    *   -   `EXT:academic_programs/Configuration/TypoScript/Content/`
        -   `EXT:academic_programs/Configuration/TypoScript/ContentLoad/`
    *   -   `EXT:academic_programs/Configuration/TsConfig/page.tsconfig`
        -   `EXT:academic_programs/Configuration/TSconfig/Full/page.tsconfig`
    *   -   `EXT:academic_programs/Configuration/TsConfig/Page/BackendLayouts/AcademicProgram.tsconfig`
        -   `EXT:academic_programs/Configuration/TSconfig/BackendLayouts/AcademicProgram.tsconfig`
    *   -   `EXT:academic_programs/Configuration/TsConfig/Page/BackendLayouts/*.tsconfig`
        -   `EXT:academic_programs/Configuration/TSconfig/BackendLayouts/*.tsconfig`
    *   -   `EXT:academic_programs/Configuration/Sets/AcademicPrograms/constants.typoscript`
        -   Removed. It was a one-line :typoscript:`@import` of the file above
            it in this table.
    *   -   `EXT:academic_programs/Configuration/Sets/AcademicPrograms/setup.typoscript`
        -   Removed, for the same reason.

The entry in the :sql:`sys_template` record keeps its value and changes its
label:

..  list-table::
    :header-rows: 1

    *   -   Old entry
        -   New entry
    *   -   :guilabel:`Academic Programs Page Setup (academic_programs)`,
            stored as `EXT:academic_programs/Configuration/TypoScript/`
        -   :guilabel:`Academic Programs: Shared plugin settings and page
            rendering (academic_programs)`, same stored value — or
            :guilabel:`Academic Programs: All components (academic_programs)`,
            stored as `EXT:academic_programs/Configuration/TypoScript/Full`,
            which also carries the content load override.

Add the page TSconfig entry, which did not exist before, in the page record of
the site root, tab :guilabel:`Resources`, field :guilabel:`Page TSconfig`:
:guilabel:`Academic Programs: All components (academic_programs)`, stored as
`EXT:academic_programs/Configuration/TSconfig/Full/page.tsconfig`. Without it
the content elements are not selectable any more, and existing records of them
lose their :sql:`CType` when they are saved from the backend form.

Sites that use the site set instead need no migration — but they must not use
both mechanisms at once, see the :guilabel:`Configuration` chapter.

A site configuration may name the new component sets instead of the aggregate:

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-programs`
        -   Unchanged in name, now delivers through the component sets below.
    *   -   `fgtclb/academic-programs-program-list`
        -   The :guilabel:`Program List` content element only.
    *   -   `fgtclb/academic-programs-program-details`
        -   The :guilabel:`Program Details` content element only.
    *   -   `fgtclb/academic-programs-content-load`
        -   The :typoscript:`styles.content.getContent` override only. The
            aggregate depends on it; name the component sets above without it to
            opt out.

..  index:: TypoScript, TSConfig, Backend, ext:academic_programs
