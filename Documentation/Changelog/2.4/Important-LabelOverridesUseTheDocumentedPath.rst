..  _important-label-overrides-use-the-documented-path:

============================================================
Important: Label overrides are read from the documented path
============================================================

Description
===========

The templates of this extension translate their labels with the extension name
:html:`AcademicPrograms` instead of the extension key
:html:`academic_programs`. TYPO3 v12 and v13 build the TypoScript path of
:typoscript:`_LOCAL_LANG` from that name as it is given, so they read label
overrides from :typoscript:`plugin.tx_academic_programs`. They now read them
from :typoscript:`plugin.tx_academicprograms` and
:typoscript:`plugin.tx_academicprograms_<plugin>`, the paths the TYPO3
documentation names and TYPO3 v14 reads anyway.

The options of the sorting select, which its view helper translates, follow the
same rule.

An override under :typoscript:`plugin.tx_academicprograms` could reach some
labels on TYPO3 v12 and v13 already, depending on what the page had rendered
before them. It now reaches every label.

Every label of the extension, and where it is shown, is listed in
:ref:`configuration-labels`.

Impact
======

On TYPO3 v12 and v13, a label override under
:typoscript:`plugin.tx_academic_programs._LOCAL_LANG` no longer has an effect.
Move it to :typoscript:`plugin.tx_academicprograms._LOCAL_LANG`, or to the path
of the one plugin it is meant for:

..  code-block:: typoscript

    plugin.tx_academicprograms._LOCAL_LANG.default.sorting.field.label = Sort by
    plugin.tx_academicprograms_programlist._LOCAL_LANG.default.sorting.field.label = Sort by

The labels of the filter form moved to this path with
:ref:`important-1790383007`.

..  index:: Frontend, Fluid, TypoScript, ext:academic_programs
