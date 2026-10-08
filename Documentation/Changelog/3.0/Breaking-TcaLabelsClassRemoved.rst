..  _breaking-tca-labels-class-removed:

=====================================================
Breaking: The unused TCA label class has been removed
=====================================================

Description
===========

The class :php:`\FGTCLB\AcademicPrograms\Backend\Tca\Labels` is removed. It was
meant to render the rootline of a category as the record label in the backend,
but its method :php:`category()` had its body commented out since it was
added, it set no label, and no TCA of this repository named it as a
:php:`label_userFunc`.

Impact
======

A :php:`label_userFunc` of a project that names
:php:`\FGTCLB\AcademicPrograms\Backend\Tca\Labels->category` fails with an
exception as soon as the backend renders a label of that table, since the class
does not exist any more. A project class that
extends it fails as soon as it is loaded.

Affected Installations
======================

Installations that name the class in their own TCA or extend it. Search the
site package for it:

..  code-block:: bash

    grep -rn "AcademicPrograms.Backend.Tca.Labels" packages/my_sitepackage/

Migration
=========

Remove the :php:`label_userFunc`. The method never changed a label, so the
backend shows the same label without it. A project that wants the rootline of
a category as its label implements a label function of its own, for example
on top of
:php:`\FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository::getCategoryRootline()`.

..  index:: Backend, TCA, PHP-API, ext:academic_programs
