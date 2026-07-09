.. _feature-ace-250-academic-programs:

==================================================================
Feature: "Show hidden records" plugin option for the program list
==================================================================

Description
===========

A new boolean plugin option **Show hidden records**
(:typoscript:`settings.showHiddenRecords`, checkbox/toggle, default off)
was added to the following plugin:

* **Program List** (:php:`academicprograms_programlist`)

When the option is enabled, the frontend program listing includes hidden
(disabled) records, independent of the Context API visibility settings.
Only the `hidden` enable column (`disabled`) is ignored; the `deleted`,
`starttime`/`endtime` and `fe_group` restrictions stay in effect.

The option is core-version-aware and available in both the TYPO3 v12 and
v13 flexform data structures of the plugin.

Impact
======

Editors can now opt in per plugin instance to display hidden programs in
the frontend, for example to preview intentionally hidden records without
changing the global preview settings. The option is off by default, so
existing plugin instances keep their current behaviour.

Affected Installations
======================

All installations using the `EXT:academic_programs` extension starting
with version 2.4. No action is required for existing installations.

.. index:: Backend, Frontend, ext:academic_programs
