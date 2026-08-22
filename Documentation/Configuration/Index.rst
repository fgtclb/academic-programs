:navigation-title: Configuration

..  _configuration:

=============
Configuration
=============

This extension ships its frontend TypoScript and its backend page TSconfig in
two forms: as TYPO3 **site sets**, and as classic **static templates** plus
**page TSconfig files** that are selected on a page. Both forms read the very
same files, so they configure an installation identically.

Pick one of them per site and stay with it — see
:ref:`Do not combine both <one-mechanism-per-site>` for what happens otherwise.

..  _configuration-components:

What the sets contain
=====================

This extension ships two content elements, so it ships two component sets, one
set for the :typoscript:`styles.content` override described below, and one
aggregate set that depends on all of them.

Both content elements are driven by one Extbase plugin, so they share one
TypoScript block, :typoscript:`plugin.tx_academicprograms`. That block is
shipped once, in :file:`Configuration/TypoScript/`, and every component includes
it. Which component sets a site names therefore decides which content elements
the backend offers, not how much TypoScript is loaded.

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-programs-program-list`
        -   The :guilabel:`Program List` content element.
    *   -   `fgtclb/academic-programs-program-details`
        -   The :guilabel:`Program Details` content element.
    *   -   `fgtclb/academic-programs-content-load`
        -   The :typoscript:`styles.content.getContent` override only. No content
            element, and nothing this extension is otherwise made of — see
            :ref:`The content load override <content-load-override>`.
    *   -   `fgtclb/academic-programs`
        -   Everything above. This is the set to use unless you deliberately
            want a subset, and it is the name this extension published before
            the sets were cut per component — a site configuration that depends
            on it needs no change.

Every content element set depends on `fgtclb/academic-base-ctype-group`, the set
of :guilabel:`EXT:academic_base` that labels the content element group all
academic extensions sort their elements into.

..  _configuration-hidden-by-default:

The content elements are hidden by default
==========================================

:guilabel:`EXT:academic_programs` hides both of its content elements for the
whole installation and brings them back per component. Whichever of the two
mechanisms below you use, it is what makes an element selectable in the backend
again — without one of them the content element is not offered, and existing
records keep rendering.

..  warning::

    This changed in version 2.4. Before it, both elements were selectable on
    every page of every installation. Read
    :ref:`Breaking: Site sets and static templates have been restructured
    <breaking-site-sets-and-static-templates-restructured>` before upgrading:
    opening an existing record on a page that does not include the page TSconfig
    of its component can rewrite the type of that record.

What the sets do not control
============================

The page type :guilabel:`Academic program` (doktype 20) and its backend layout
:guilabel:`AcademicProgram` are **not** part of any set, and enabling or not
enabling a set never changes them.

That is deliberate, not an oversight. Both are values stored on :sql:`pages`
records: a page carries `doktype = 20` and `backend_layout = pagets__AcademicProgram`
long before any site configuration is read. Were they delivered by an opt-in
set, every page tree on a site that does not use that set would show
:guilabel:`[ MISSING LABEL ]` for the layout, the layout could not be picked for
a new page, and the page type would disappear from the page tree wizard.

They are therefore registered installation-wide — the page type in TCA
(:file:`Configuration/TCA/Overrides/pages.php`), the backend layout in the
always-included :file:`Configuration/page.tsconfig` — and stay available on every
site of the installation.

What a set does deliver for that page type is its **frontend rendering**: the
:typoscript:`page` object that picks the Fluid template of the page type is part
of the shared TypoScript block, so a site that includes no set of this extension
renders such a page with whatever its own site package defines.

..  _content-load-override:

The content load override
=========================

:file:`Configuration/TypoScript/ContentLoad/setup.typoscript` redefines
:typoscript:`styles.content.getContent` for the whole site so that it selects
:typoscript:`colPos = 0` only. This is an installation-wide rendering change, it
applies to every page of the site and not only to the pages of this extension,
and three academic extensions ship the same override.

It is therefore a set of its own, `fgtclb/academic-programs-content-load`. The
aggregate set depends on it, so a site on `fgtclb/academic-programs` keeps what
it had; a site that wants the content elements without the override names the
component sets it needs instead of the aggregate.

..  warning::

    The Fluid template of the page type renders
    :typoscript:`styles.content.getContent` through
    :html:`<f:cObject typoscriptObjectPath="styles.content.getContent"/>`, and
    that ViewHelper throws when the path is undefined. A site that opts out of
    this set and still uses the page type has to define
    :typoscript:`styles.content.getContent` itself.

..  _site-set:

Include the site set
====================

Add the set to the :file:`config.yaml` of the site that should offer the content
elements:

..  code-block:: diff
    :caption: config/sites/my-site/config.yaml (diff)

     base: 'https://example.com/'
     rootPageId: 1
    +dependencies:
    +  - fgtclb/academic-programs

See also `TYPO3 Explained, Using a site set as dependency in a site
<https://docs.typo3.org/permalink/t3coreapi:site-sets-usage>`__.

..  _static-templates:

Include static templates
========================

For an installation that still configures its frontend through
:sql:`sys_template` records, the same files are registered as static templates
and as selectable page TSconfig files.

..  tip::

    On TYPO3 v13 and v14 we recommend the site set — and if you use it, do not
    press the backend button :guilabel:`Create a root TypoScript record` on that
    site. The :sql:`sys_template` record it creates carries the flag
    :guilabel:`Clear` for constants and setup, and that flag discards everything
    the site sets contributed. An installation that is already in that state
    gets its configuration back by selecting the static templates below in that
    very record.

..  _static-typoscript:

Include static TypoScript
-------------------------

Edit the :sql:`sys_template` record of the site root and add the entry to
:guilabel:`Include static (from extensions)`:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Programs: Program List (academic_programs)`
        -   The TypoScript of the :guilabel:`Program List` content element.
    *   -   :guilabel:`Academic Programs: Program Details (academic_programs)`
        -   The same for :guilabel:`Program Details`.
    *   -   :guilabel:`Academic Programs: Content load override (academic_programs)`
        -   The :typoscript:`styles.content.getContent` override on its own.
    *   -   :guilabel:`Academic Programs: All components (academic_programs)`
        -   Every component this extension ships, in one entry.
    *   -   :guilabel:`Academic Programs: Shared plugin settings and page
            rendering (academic_programs)`
        -   The shared :typoscript:`plugin.tx_academicprograms` block and the
            :typoscript:`page` object of the page type, on their own. This is
            the entry an installation stored before the configuration was cut
            per component, and it keeps working — but it does not make any
            content element selectable, which the page TSconfig below does, and
            it no longer carries the content load override.

..  _static-pagetsconfig:

Include static page TSconfig
----------------------------

Edit the page record of the site root, tab :guilabel:`Resources`, field
:guilabel:`Page TSconfig`, and add the entry:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Programs: Program List (academic_programs)`
        -   Makes the :guilabel:`Program List` content element selectable, and
            configures its entry in the new content element wizard.
    *   -   :guilabel:`Academic Programs: Program Details (academic_programs)`
        -   The same for :guilabel:`Program Details`.
    *   -   :guilabel:`Academic Programs: All components (academic_programs)`
        -   Every component this extension ships, in one entry.

The setting is inherited by every page below the one it is set on.

..  _one-mechanism-per-site:

Do not combine both
===================

A site that uses the site set **and** the static template reads the shipped
files twice. The site set is applied before the :sql:`sys_template` record, so
the second read happens after the site settings and after
:file:`config/sites/<site>/constants.typoscript` — and it resets every constant
the extension ships a default for back to that default. For this extension that
is the :typoscript:`plugin.tx_academicprograms` constants block, the three Fluid
root paths.

Nothing else is damaged: the :guilabel:`Constants` and :guilabel:`Setup` fields
of the :sql:`sys_template` record, the page TSconfig of a page and the page
TSconfig files selected on a page are all applied afterwards and still win. Use
one mechanism per site and the question does not arise.

..  toctree::
   :maxdepth: 5
   :titlesonly:

   RouteEnhancers/Index
