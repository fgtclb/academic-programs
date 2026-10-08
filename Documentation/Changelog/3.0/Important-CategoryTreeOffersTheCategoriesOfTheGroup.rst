..  _important-category-tree-offers-the-categories-of-the-group:

===============================================================
Important: The category tree offers the categories of the group
===============================================================

Description
===========

The :guilabel:`Default categories` field of the program list plugin and the
:guilabel:`Preselected categories` field of the program finder offered every
category that had a type, including the categories of other extensions,
such as the regions of partners or the competence fields of projects. The
plugins only ever use the categories of the :php:`programs` group and silently
ignored any other selection.

The tree now offers the categories that carry a type of the :php:`programs`
group, and the categories above them, so a category of the group below a parent
of another type or of none is still reachable. Such a parent can be selected
and is ignored, as before. Types an integrator adds to the group are offered
as well.

When the site setting :yaml:`plugin.tx_academicprograms.categoryRootUids`
names a category root, the tree starts there and offers the categories of the
group below it.

Impact
======

Categories of other groups are no longer offered in the tree. The update
itself leaves a selection of such a category that is stored already in place,
and the frontend keeps ignoring it.

..  index:: Backend, FlexForm, ext:academic_programs
