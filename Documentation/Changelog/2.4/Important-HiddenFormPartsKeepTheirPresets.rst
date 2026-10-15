.. _important-hidden-program-form-parts-keep-their-presets:

===========================================================
Important: A hidden part of the form keeps the preset value
===========================================================

Description
===========

The program list applied the :guilabel:`Default categories` and the
:guilabel:`Default sorting` of the content element only to a request without
any selection of the visitor. Once a visitor used the visible part of the form,
the part the editor hid lost its preset as well:

*   With :guilabel:`Hide category filtering` switched on and the sorting shown,
    a visitor who changed the sorting got every program instead of the
    programs of the default categories.
*   With :guilabel:`Hide sorting` switched on and the category filter shown, a
    visitor who chose a category got the programs sorted by title, A to Z,
    instead of by the default sorting.

A part of the form the content element hides now keeps the value of the
content element. The sorting of a hidden sorting is always the default
sorting, also where a link or a hand-made URL carries another one. The default
categories of a hidden category filter always apply. Categories a submission,
a link or a hand-made URL carries for such a list are added to them, so they
can narrow the list further but never replace a default category. For a part
of the form the content element shows, the selection of the visitor applies as
before.

Impact
======

*   A list with a hidden category filter and default categories shows only the
    programs of those categories, also after the visitor changed the sorting.
*   A list with a hidden category filter and no default categories is still
    filtered by the categories of a link, as before.
*   A list with a hidden sorting keeps its default sorting when the visitor
    filters it.
*   An installation that hid the sorting as well to keep the default categories
    can show the sorting again.
*   Behaviour is identical on every supported TYPO3 version.

.. index:: Frontend, ext:academic_programs
