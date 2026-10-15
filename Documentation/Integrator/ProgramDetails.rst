..  _integrator-program-details:

===========
Course Data
===========

..  image:: /Images/Icons/programs.svg
    :width: 48px
    :alt: Icon of the Course Data content element

:CType:
    ``academicprograms_programdetails``

:Plugin:
    `AcademicPrograms`, plugin `ProgramDetails`, action
    :php:`DetailsController::showAction()`

:Set:
    `fgtclb/academic-programs-program-details`, included in
    `fgtclb/academic-programs`

The :guilabel:`Course Data` element shows the facts of the program page it is
placed on: its categories per category type, and with the site setting the
credit points and the text fields of the program. It reads the page the
content element belongs to, so it is meant for the content of a program page,
a page of the type :guilabel:`Academic program`. It has no program to pick.

..  figure:: /Images/Screenshots/program-details-default.png
    :alt: A list of facts with an icon, a label and a value each, Degree "Bachelor of Engineering" among them
    :class: with-border with-shadow

    The Course Data element lists the categories of the program page per
    category type.

A program page renders its facts already, through the partial
:file:`Program/Page/Facts.html`, see :ref:`program-page-partials`. The element
is for a site that wants the facts at a place of the editor's choosing
instead, between two text elements or in another column, or for a site package
whose page template leaves the facts out.

Which facts it shows, and in which order, is decided for the whole site by
the site settings :typoscript:`plugin.tx_academicprograms.facts.fields` and
:typoscript:`plugin.tx_academicprograms.facts.mostSpecificOnly`, which the
program page reads as well, see :ref:`program-facts`. With an empty field
list it shows every category type of the group `programs` the program carries
and no program field, while the program page adds the four program fields.

The element renders the template :file:`Details/Show.html` with the partial
:file:`Program/Facts.html`, described in :ref:`styling-detail`. A program
without a single fact renders an empty element.

..  _integrator-program-details-settings:

Settings
========

The element has no options of its own. An editor sets the header and the
appearance of the content element, as for any other content element, and the
site settings above decide what it shows.
