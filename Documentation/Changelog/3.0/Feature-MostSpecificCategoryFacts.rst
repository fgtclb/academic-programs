..  _feature-1790681426:

==========================================================
Feature: Program facts can show the most specific category
==========================================================

Description
===========

Editors often assign a parent degree such as "Bachelor" together with its
subcategory "Bachelor of Science", so that a filter on the parent finds the
program. The facts of the program then show both.

The new setting :typoscript:`plugin.tx_academicprograms.facts.mostSpecificOnly`
leaves a category out of the facts when the program carries a descendant of it
of the same category type as well. With it switched on, the program above shows
"Bachelor of Science" only:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicprograms:
        facts:
          mostSpecificOnly: true

It is a site setting of the aggregate set `fgtclb/academic-programs` and a
constant of the same name for a site on the static templates. It applies to
the program page, the :guilabel:`Program Details` content element and each card
of the :guilabel:`Program List`.

Only the categories assigned to the program are compared. A level that is not
assigned breaks the line: with "Bachelor" and a subcategory two levels below it
assigned, but not the level between them, both are shown.

The view helper :html:`<ace:program.facts>` takes the setting as its new
argument :html:`mostSpecificOnly`, so a card template of your own passes it
on:

..  code-block:: html

    <f:variable name="cardFacts"
                value="{ace:program.facts(program: program, fields: settings.card.fields, mostSpecificOnly: settings.facts.mostSpecificOnly)}" />

See :ref:`program-facts-most-specific-category`.

Impact
======

The setting is off by default, so the facts stay as they are until it is
switched on. Filters are not affected: the list and the finder still find a
program by every category it carries and offer the same options.

An override of :file:`Program/Item.html` that calls the view helper without
the new argument keeps showing every category on the card.

..  index:: Frontend, TypoScript, ext:academic_programs
