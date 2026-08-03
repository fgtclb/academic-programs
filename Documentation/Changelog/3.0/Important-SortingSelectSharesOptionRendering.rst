.. _important-1785882200:

=========================================================
Important: The sorting select shares the option rendering
=========================================================

Description
===========

`ViewHelpers\\Form\\SortingSelectViewHelper::renderOptionTags()` was removed. It
was identical to the method it inherits from
`FGTCLB\\CategoryTypes\\ViewHelpers\\Form\\AbstractSelectViewHelper`, down to the
last character - the class only ever needed its own `getOptions()`.

That base class writes every option through a single method now, which escapes
the option value. It used to concatenate the value unchanged while escaping the
label.

Impact
======

The rendered markup is unchanged. The options of this select carry the values of
`Enumeration\\SortingOptions` - `title`, `asc`, `desc` and their siblings - and
none of them needs escaping.

An own subclass overriding `renderOptionTags()` keeps working, and one calling
`parent::renderOptionTags()` reaches the inherited implementation.

References
==========

*   `AbstractSelectViewHelper
    <https://github.com/fgtclb/typo3-category-types>`__ in
    `EXT:category_types` - the class writing the option markup, and where the
    change is documented.

.. index:: Fluid, Frontend, PHP-API, NotScanned
