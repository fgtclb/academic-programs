..  _important-1790592004:

=============================================================
Important: The programs group shows its title and has an icon
=============================================================

Description
===========

The extension declares a title and an icon for its category type group
:yaml:`programs` in :file:`Configuration/CategoryTypes.yaml`, but
:php:`EXT:category_types` never read that declaration, and the icon file it
names was never shipped. The type select of a category headed the programs
types with the key :yaml:`programs`.

Impact
======

The type select now heads them with the title :guilabel:`Academic Programs`.
The icon ships as :file:`Resources/Public/Icons/CategoryGroups/Programs.svg`, a
Font Awesome Free icon listed in
:file:`Resources/Public/Icons/LICENSE-font-awesome.txt`, and is registered as
:php:`category_types_group.programs`, in the icon registry of the backend and in
the frontend icon registry of :php:`EXT:academic_base`.

A site package can change the title or the icon by declaring the group
:yaml:`programs` again, see the developer documentation of
:php:`EXT:category_types`, section :guilabel:`Naming a group`.

.. index:: Backend, Frontend, ext:academic_programs
