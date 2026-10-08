.. _feature-ace-250-academic-programs:

=================================================================
Feature: "Show hidden records" plugin option for the program list
=================================================================

Description
===========

A new boolean plugin option **Show hidden records**
(:typoscript:`settings.showHiddenRecords`, checkbox/toggle, default off)
was added to the following plugin:

* **Program List** (:php:`academicprograms_programlist`)

When the option is enabled, the frontend program listing includes hidden
(disabled) records, also for visitors without a preview of hidden records.
Only the `hidden` enable column (`disabled`) is ignored; the `deleted`,
`starttime`/`endtime` and `fe_group` restrictions stay in effect.

On a translated page the listing shows the translation of a hidden record,
and follows the fallback type of the site language like for any other
record. The start and end time of the default record keep deciding for its
translation. One limit remains on TYPO3 v12 with :yaml:`fallbackType: fallback`:
a record whose translation differs from its default record in visibility is
listed twice (default visible, translation hidden) or not at all (default
hidden, translation visible). With :yaml:`fallbackType: strict`, and on
TYPO3 v13, it is listed once, with its translation.

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
