.. _important-incomplete-program-sorting-keeps-the-default:

==========================================================
Important: An incomplete default sorting keeps the default
==========================================================

Description
===========

The program list splits its :guilabel:`Default sorting` into a field and a
direction. A value that is not a field and a direction stopped the content
element with a PHP error on every request without a selection of the visitor:
an empty value, a field alone such as
:typoscript:`plugin.tx_academicprograms.settings.sorting = title` for an
element that stores no sorting of its own, or a value set by an import or a
hand-written FlexForm.

Such a value is now ignored, and the element sorts by title, A to Z, the
default of the extension. A value of more than two words, whose first two
words were applied before, is now ignored as well. Extra spaces no longer
change what applies: a field and a direction after a leading space, which fell
back to title, A to Z, before, and a field and a direction separated by more
than one space, which lost the direction before, are now both applied.

Impact
======

*   An element that failed with :php:`Undefined array key 1` or with a
    :php:`TypeError` of :php:`ProgramDemand::setSortingDirection()` renders its
    programs again, sorted by title. Set a :guilabel:`Default sorting` in the
    element or, for an element that stores no sorting of its own, a field and
    a direction such as `title desc` in TypoScript, to get another order.
*   A value of more than two words now sorts by title, A to Z, instead of by
    its first two words.
*   A value of the :guilabel:`Default sorting` select is applied as before.
*   Behaviour is identical on every supported TYPO3 version.

.. index:: Frontend, ext:academic_programs
