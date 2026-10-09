..  _feature-1791566505:

=======================================================================
Feature: The scripts of the program list and finder can be switched off
=======================================================================

Description
===========

The :guilabel:`Program List` registers the module
:js:`@fgtclb/academic-programs/frontend/program-list.js`, from its template and
from the three partials of its filter form, and the :guilabel:`Program Finder`
registers :js:`@fgtclb/academic-programs/frontend/program-finder.js`. An
installation that brings its own scripts had to override every one of those
files to leave them out.

Both modules are switchable now, together, per site:

..  list-table::
    :header-rows: 1

    *   -   Site setting and constant
        -   Default
    *   -   :yaml:`plugin.tx_academicprograms.assets.js`
        -   :yaml:`true`

It is declared by the site set `fgtclb/academic-programs`, with every other
setting of this extension:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin.tx_academicprograms.assets.js: false

An installation that configures its frontend through :sql:`sys_template`
records sets the TypoScript constant of the very same name, in
:guilabel:`Constants`:

..  code-block:: typoscript
    :caption: Constants of the root sys_template record

    plugin.tx_academicprograms.assets.js = 0

Every academic extension that loads a script has this switch, named
:typoscript:`assets.js` below its own plugin namespace and switched on by
default, after the switch of the study plan content element.

Impact
======

Nothing changes for an installation that configures nothing: both elements
load their scripts exactly as before.

Switched off, the page loads neither, and the markup is unchanged. Both
elements keep working: the list reloads the page when its form is submitted
with the button, and the finder submits to the list without narrowing its
options first. See :ref:`configuration-javascript`.

A project that overrides :file:`Templates/Program/List.html`,
:file:`Templates/Program/Finder.html` or one of the partials
:file:`Program/SortingAndFilters.html`, :file:`Program/DemandSorting.html` and
:file:`Program/DemandCategories.html` keeps whatever its copy loads. The switch
reaches it once it wraps its own :html:`<f:asset.module>` in
:html:`<f:if condition="{settings.assets.js}">`.

.. index:: Frontend, Fluid, TypoScript, ext:academic_programs
