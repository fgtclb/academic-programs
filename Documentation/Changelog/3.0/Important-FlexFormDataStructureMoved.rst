..  _important-programs-flexform-data-structure-moved:

============================================
Important: The FlexForm data structure moved
============================================

Description
===========

Version 2.x shipped the FlexForm data structure of the :guilabel:`Course List`
twice, as :file:`Configuration/FlexForms/Core12/ProgramListSettings.xml` and
:file:`Configuration/FlexForms/Core13/ProgramListSettings.xml`, and picked one
by the TYPO3 major version.

With TYPO3 v12 no longer supported, see
:ref:`breaking-removed-typo3-v12-support`, it is one file,
:file:`Configuration/FlexForms/ProgramListSettings.xml`. The directories
:file:`Configuration/FlexForms/Core12/` and
:file:`Configuration/FlexForms/Core13/` are gone. The fields the list had keep
their names, so the stored settings of existing content elements and page
TSconfig that addresses them keep working. The new fields are described in
:ref:`feature-1790361244` and :ref:`feature-1790599131`.

The new :guilabel:`Course Finder` has a file of its own,
:file:`Configuration/FlexForms/ProgramFinderSettings.xml`.

Impact
======

Code or configuration of a site that names one of the former files by path,
in TCA of its own or in a copy of the data structure, names a file that no
longer exists.

Migration
=========

Name :file:`EXT:academic_programs/Configuration/FlexForms/ProgramListSettings.xml`
instead.

..  index:: Backend, FlexForm, TCA, NotScanned, ext:academic_programs
