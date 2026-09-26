..  _important-1790383007:

=======================================================================
Important: Label overrides of the filters are read from the plugin path
=======================================================================

Description
===========

The filter form of the :guilabel:`Program List` (and from 3.0 on of the
:guilabel:`Program Finder`) translates its labels with the extension name
:html:`AcademicPrograms` instead of :html:`academic_programs`. TYPO3 v12 and v13
build the TypoScript path of :typoscript:`_LOCAL_LANG` from that name as it is
given, so they read label overrides of the filters from
:typoscript:`plugin.tx_academic_programs`. They now read them from
:typoscript:`plugin.tx_academicprograms` and
:typoscript:`plugin.tx_academicprograms_<plugin>`, the paths the TYPO3
documentation names and TYPO3 v14 reads anyway.

It concerns the labels the filter form renders:
:xml:`sys_category.programs.<type>`, :xml:`sys_category.programs.allOptions`,
:xml:`sys_category.programs.allOptions.<type>` and :xml:`filter.moreFilters`,
and, from 3.0 on, in the finder :xml:`finder.submit`.

Impact
======

On TYPO3 v12 and v13, an override of one of these labels under
:typoscript:`plugin.tx_academic_programs._LOCAL_LANG` no longer reaches the
filter form. Set it under :typoscript:`plugin.tx_academicprograms._LOCAL_LANG`
as well:

..  code-block:: typoscript

    plugin.tx_academicprograms._LOCAL_LANG.default.sys_category.programs.allOptions = All
    plugin.tx_academicprograms._LOCAL_LANG.de.sys_category.programs.allOptions = Alle

Copy an override of a type label, :xml:`sys_category.programs.<type>`, rather
than move it: the program cards of the list, the program details content element
and the program page render the same label and still read it from the old path
on TYPO3 v12 and v13.

Other templates of the extension are not affected by this change.

..  index:: Frontend, Fluid, TypoScript, ext:academic_programs
