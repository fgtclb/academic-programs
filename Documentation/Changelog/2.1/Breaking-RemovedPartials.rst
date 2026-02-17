.. include:: /Includes.rst.txt

.. _breaking-1748865631:

==========================
Breaking: Removed partials
==========================

Description
===========

Some partials got removed as the templating structure has changed.


Impact
======

Those partials include:

* `Resources/Private/Layouts/Default.html`
* `Resources/Private/Pages/AcademicPrograms.html`
* `Resources/Private/Partials/Categories.html`
* `Resources/Private/Partials/Program/FilterCategories.html`
* `Resources/Private/Partials/Program/FilterSorting.html`


Affected Installations
======================

TYPO3 instances with extensions overriding those partials of `EXT:academic_programs`.


Migration
=========

Adapt overrides accordingly to the new templating structure.

.. index:: Fluid, Frontend
