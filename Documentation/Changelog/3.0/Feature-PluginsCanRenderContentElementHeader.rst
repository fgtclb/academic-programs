..  _feature-1790440963:

==========================================================
Feature: The plugins can render the content element header
==========================================================

Description
===========

The header and the subheader an editor enters on a :guilabel:`Program List`,
:guilabel:`Program Details` or :guilabel:`Program Finder` content element are
rendered by the content element layout of the site, like those of any other
content element. The layouts of :guilabel:`EXT:fluid_styled_content` and of the
bootstrap package do that. A site package whose layout leaves the header out,
because its element templates render it, showed no header for these content
elements, and projects copied the templates to add it.

A site whose layout renders no header now switches the header on:

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicprograms.renderContentElementHeader = 1

On a site that uses the site set, that is the site setting :guilabel:`Render the
content element header in the plugins` of `fgtclb/academic-programs`. It is off
by default.

Switched on, the templates render the header partial of
:guilabel:`EXT:fluid_styled_content` above their output, for every header layout
except :guilabel:`Hidden`. The plugin settings carry
:typoscript:`settings.defaultHeaderType`, mapped from the constant
:typoscript:`styles.content.defaultHeaderType`, so the header layout
:guilabel:`Default` renders a heading. The partial path of
:guilabel:`EXT:fluid_styled_content` is added below every other one, so a
:file:`Header/All.html` of the site package wins over it, and the extension does
not require :guilabel:`EXT:fluid_styled_content`. See
:ref:`configuration-content-element-header`.

On TYPO3 v14 the header partial renders the header through the `record` view
variable, so the plugin views now carry `record` next to `data`: the content
element as a record object.

Impact
======

Nothing renders differently until a site switches the header on. A site whose
layout renders the header leaves it off: switched on, the header appears twice.
A project that copied a template only to add the header can switch it on and
drop the copy.

.. index:: Frontend, Fluid, TypoScript, ext:academic_programs
