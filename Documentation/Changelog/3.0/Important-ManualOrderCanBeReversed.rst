.. _important-manual-order-can-be-reversed:

=======================================
Important: Manual order can be reversed
=======================================

Description
===========

The program list lets a visitor pick a sort field and a sort direction
independently, and it offers both directions for every field. The manual order
of the program pages — :guilabel:`Page sorting` — existed in its ascending
variant only, so picking it together with :guilabel:`Descending` rendered the
ascending list again and the direction select snapped back to
:guilabel:`Ascending` without a message.

:php:`FGTCLB\AcademicPrograms\Enumeration\SortingOptions` now defines
:php:`SORT_BY_SORTING_DESC`, so every combination the two selects offer is an
ordering the list really renders. :guilabel:`Page sorting, reversed` is
available as a default ordering of the list content element as well.

Impact
======

Nothing changes for a content element that is already stored: every ordering
that existed keeps its value and its behaviour. One further ordering becomes
reachable — in the plugin configuration, through the sorting form, and through
the :file:`/sorting/desc` path of the shipped route enhancer, which resolved
before but was then discarded.

:guilabel:`academic_partners` and :guilabel:`academic_projects` already offered
the reversed manual order, so the three lists now agree.

Affected Installations
======================

Every installation of this extension that renders the program list with its
sorting form, or that wants the reversed manual order as a configured default.

.. index:: Backend, Frontend, PHP-API, ext:academic_programs
