..  _important-sorting-options-read-the-plugin-override-on-typo3-v14:

================================================================
Important: Sorting options read the plugin override on TYPO3 v14
================================================================

Description
===========

The options of the sorting select are translated by its view helper. On TYPO3
v14 the core reads the :typoscript:`_LOCAL_LANG` override of a plugin,
:typoscript:`plugin.tx_academicprograms_<plugin>`, only from the plugin request
a translation is handed. The view helper now renders the core translate view
helper, which hands it on, so the override of the plugin reaches the options on
TYPO3 v14 as well. Before, only the override of the extension did.

The path of the overrides is the one of
:ref:`important-label-overrides-use-the-documented-path`.

Impact
======

An override of a sorting option under
:typoscript:`plugin.tx_academicprograms_programlist._LOCAL_LANG` now has an
effect on TYPO3 v14. Nothing has to be moved.

..  index:: Frontend, TypoScript, ext:academic_programs
