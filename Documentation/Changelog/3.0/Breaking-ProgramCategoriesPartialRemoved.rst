..  _breaking-program-categories-partial-removed:

==============================================================
Breaking: The partial Program/Categories.html has been removed
==============================================================

Description
===========

The partial :file:`Resources/Private/Partials/Program/Categories.html`
rendered the categories of a program per category type, on the program page
and in the :guilabel:`Program Details` content element. The program page added
the program fields credit points, job profile, performance scope and
prerequisites as a second list of its own, and the program card of the
:guilabel:`Program List` rendered the degree with markup of its own.

The partial is removed. The program page, the details content element and the
program card render their facts through the new partials
:file:`Program/Facts.html` - the list - and :file:`Program/Facts/Item.html` -
one fact -, from the field lists of :ref:`feature-1790345444`.

The markup of the facts changes in all three places:

*   The facts are one :html:`<ul class="academic-programs-facts">`. On the
    program page the program fields are items of that list, after the
    categories, instead of a second :html:`<ul>`.
*   Every fact is an :html:`<li class="academic-programs-facts__item
    academic-programs-facts__item--{identifier}">`, the identifier being the
    category type (`degree`) or the program field (`creditPoints`).
*   Every label is followed by a colon, the program field labels included.
*   The credit points fact renders the icon
    `tx-academicprograms-info-credit-points` before its label.
*   The card keeps its classes: the list additionally carries
    `list-group list-group-flush`, every item `list-group-item`.

Impact
======

A project override of :file:`Partials/Program/Categories.html` is no longer
rendered: nothing of this extension renders that partial any more, and Fluid
does not report an override that nobody renders.

A project override of a template or partial that still renders
:html:`<f:render partial="Program/Categories" />` - an override of
:file:`Pages/AcademicProgram.html` or :file:`Templates/Details/Show.html` taken
from an earlier version, for example - fails with an exception of Fluid that
the partial cannot be found, unless the project ships that partial itself.

Site CSS or JavaScript that addressed the facts through the old markup, a
second list on the program page or a label without a colon has to follow the
new markup.

The data processor of the program page,
:php:`\FGTCLB\AcademicPrograms\DataProcessing\ProgramDataProcessor`, builds
the facts and takes the builder as a constructor argument. A project subclass of
it that declares a constructor of its own has to pass that argument on, and a
subclass named in TypoScript by its class name is only given the argument when
the project's own :file:`Services.yaml` registers it as a public service -
otherwise TYPO3 creates it without arguments, which is fatal.

Affected Installations
======================

Installations that override :file:`Partials/Program/Categories.html`, render
it from a template or partial of their own, style the facts of the program
page, of the details content element or of the program card, or extend the
data processor of the program page.

Search the site package for the partial:

..  code-block:: bash

    grep -rn "Program/Categories" packages/my_sitepackage/Resources/Private/

Migration
=========

*   Move a change of one fact row to an override of
    :file:`Program/Facts/Item.html`. It receives :html:`{fact}` with
    :html:`{fact.identifier}`, :html:`{fact.labelKey}`,
    :html:`{fact.iconIdentifier}`, :html:`{fact.isCategoryType}`,
    :html:`{fact.categories}` for a category type and :html:`{fact.value}` for
    a program field.
*   Move a change of the whole list to an override of
    :file:`Program/Facts.html`, which receives :html:`{facts}`.
*   Set :typoscript:`plugin.tx_academicprograms.facts.fields` where the
    override changed which facts are shown or their order, and
    :typoscript:`plugin.tx_academicprograms.card.fields` for the card.
*   Replace :html:`<f:render partial="Program/Categories" arguments="{_all}" />`
    in a template override by
    :html:`<f:render partial="Program/Facts" arguments="{_all}" />`: the
    program page and the details content element hand their templates the
    variable :html:`{facts}`. An override of :file:`Pages/AcademicProgram.html`
    from before 3.0 also renders the fixed list of the four program fields;
    remove that list as well, or the program page shows them twice, since the
    empty facts setting shows them after the categories.
*   Remove the override of :file:`Partials/Program/Categories.html`.
*   Pass a :php:`\FGTCLB\AcademicPrograms\Service\ProgramFactsBuilder` on
    to the parent constructor of a subclass of the data processor, and
    register the subclass as a public service (:yaml:`public: true`), or tag
    it `data.processor` with an identifier of its own and name that
    identifier in TypoScript.

..  index:: Frontend, Fluid, ext:academic_programs
