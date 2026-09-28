..  _feature-1790599131:

=====================================================
Feature: The program filter can include subcategories
=====================================================

Description
===========

A category selected in the filter of the :guilabel:`Program List`, or set in the
:guilabel:`Default categories` of the element, found only the programs that
carry that very category. A visitor who filtered by "Bachelor" did not find a
program that carries only "Bachelor of Science", a subcategory of "Bachelor",
and the filter offered "Bachelor" as a disabled option unless some program
carried it itself. Editors therefore assigned both levels, and projects hid the
parent in the program output again.

The :guilabel:`Program List` content element has a new field
:guilabel:`Include subcategories`, tab :guilabel:`Configuration`, off by
default. With it on:

*   A selected category finds every program that carries the category itself
    or a visible category anywhere below it, for a category the visitor
    selects and for the :guilabel:`Default categories` of the element alike.
*   Several selections still all have to match: "Bachelor" and "Berlin" find
    the Bachelor programs of any kind in Berlin.
*   The filter offers a category as soon as a listed program carries one of
    its subcategories, and keeps it when the site hides options without
    results.

The :guilabel:`Program Finder` has the same field, for the options it offers
and the categories it preselects.
The list on its target page decides which programs are found, so the field is
switched on in both elements.

See :ref:`program-list-subcategories`.

Impact
======

Existing content elements have the field off and list exactly the programs
they listed before. With the field on, a program needs only its most specific
category, and the parent category can be removed from the programs.

A hidden category, a category of a type outside the group `programs`, and the
categories below either of them take no part. The subcategories are read with
one query per level of the category tree when a list with the field on is
filtered.

..  index:: Backend, Frontend, FlexForm, ext:academic_programs
