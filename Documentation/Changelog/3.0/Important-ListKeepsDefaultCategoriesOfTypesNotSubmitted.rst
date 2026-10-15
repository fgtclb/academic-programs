.. _important-list-keeps-default-categories-of-types-not-submitted:

===================================================================
Important: The program list keeps default categories of other types
===================================================================

Description
===========

A selection submitted to the program list replaced all of its
:guilabel:`Default categories`, also those of a category type the submitting
form did not offer. The :guilabel:`Course Finder` (:ref:`program-finder`)
offers its own category types, the degree and the topic unless its element or
the site names others, so a list preset to one location showed the programs of
every location once the finder opened it.

A submitted selection now replaces the default categories of the category types
its form offers, and the default categories of every other type stay. This
holds for the finder and for the list's own form, whose selects follow the
filter types of the element or the site. Choosing "All" in a select of the list
still clears the default category of that type. A link that carries a category
filter, as the address of a filtered list and the active filter tags do, is
still the whole selection.

A list that hides its category filter keeps all its default categories, see
:ref:`important-hidden-program-form-parts-keep-their-presets`. A finder that
opens such a list narrows it further: its selection is added to the default
categories.

Impact
======

*   A finder that opens a list preset to a category of a type the finder does
    not offer shows only the programs that carry that category and the
    selection of the visitor.
*   A finder that opens a list with a hidden category filter shows the programs
    that carry the default categories and the selection of the visitor. Where
    the two exclude each other, a finder degree and a different default degree
    for example, the list is empty.
*   A list whose filter types leave out the type of a default category keeps
    that category when the visitor filters.
*   Behaviour is identical on TYPO3 v13 and v14.

.. index:: Frontend, ext:academic_programs
