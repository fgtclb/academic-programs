..  _important-program-default-categories-description:

=================================================================
Important: The description of Default categories names their rule
=================================================================

Description
===========

The description below :guilabel:`Default categories` of the program list said
that selected categories of the same category type are alternatives (OR), and
that the subcategories of a selected category are added automatically. The
list never did either.

The description now says what the list does, in English and in German: a
program is shown only when it carries every selected category, also where two
of them are of the same category type. The subcategories of a selected
category are not added automatically.

Impact
======

Only the text changed, the list shows the same programs as before. The label
key :xml:`flexform.categories.description` is unchanged, so an override of it
in a language file keeps its own text.

Affected Installations
======================

Every installation with a program list that has default categories. An
element with two default categories of one category type shows only the
programs that carry both. Where either of them was meant, give the programs of
both categories one category in common and select that one instead.

..  index:: Backend, FlexForm, ext:academic_programs
