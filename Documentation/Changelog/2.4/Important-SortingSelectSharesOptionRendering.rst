.. _important-1785882200:

=========================================================
Important: The sorting select shares the option rendering
=========================================================

Description
===========

:php:`ViewHelpers\Form\SortingSelectViewHelper::renderOptionTags()` was removed.
It was identical to the method it inherits from
:php:`FGTCLB\CategoryTypes\ViewHelpers\Form\AbstractSelectViewHelper`, down to
the last character. The class only ever needed its own :php:`getOptions()`.

That base class writes every option through a single method now, which escapes
the option value. It used to concatenate the value unchanged while escaping the
label.

Impact
======

The rendered markup is unchanged. The options of this select carry the values of
:php:`Enumeration\SortingOptions`, such as `title asc` and `lastUpdated desc`,
and none of them needs escaping.

An own subclass overriding :php:`renderOptionTags()` keeps working, and one
calling :php:`parent::renderOptionTags()` reaches the inherited implementation.

References
==========

*   `AbstractSelectViewHelper
    <https://github.com/fgtclb/typo3-category-types>`__ in EXT:category_types,
    the class writing the option markup, and where the change is documented.

.. index:: Fluid, Frontend, PHP-API, NotScanned, ext:academic_programs
