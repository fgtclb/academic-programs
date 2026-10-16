..  _integrator:

===============
For integrators
===============

This chapter describes the content elements of :guilabel:`academic_programs`:
what each one renders, where it belongs and what every option in its backend
form does. The site sets, the site settings and the TypoScript the content
elements read are described in :ref:`configuration`.

An option an editor stores in a content element wins over a TypoScript value of
the same name below :typoscript:`plugin.tx_academicprograms.settings`, and so
does an empty one: Extbase merges the stored options over the TypoScript
settings with empty values included. A TypoScript value of an option therefore
reaches only a content element that was never saved with that field.

One option is the exception. :guilabel:`Filter types` of the
:guilabel:`Course List` and of the :guilabel:`Course Finder` is listed in
:typoscript:`plugin.tx_academicprograms.ignoreFlexFormSettingsIfEmpty`, so an
element that leaves the field empty keeps the site setting
:typoscript:`plugin.tx_academicprograms.filter.categoryTypes`. That is how one
site setting decides the filters of every program list and finder of a site,
while a single element can still choose its own. No other option of this
extension has a TypoScript value of the same name.

..  list-table::
    :header-rows: 1
    :widths: 10 25 30 35

    *   -   Icon
        -   Content element
        -   CType
        -   Shows
    *   -   ..  figure:: /Images/Icons/programs.svg
                :width: 32px
                :alt: Icon of the Course List content element
        -   :ref:`Course List <integrator-program-list>`
        -   ``academicprograms_programlist``
        -   The program pages of a storage as cards, with category filters
            and a sorting the visitor can change.
    *   -   ..  figure:: /Images/Icons/programs.svg
                :width: 32px
                :alt: Icon of the Course Data content element
        -   :ref:`Course Data <integrator-program-details>`
        -   ``academicprograms_programdetails``
        -   The facts of the program page the element is placed on.
    *   -   ..  figure:: /Images/Icons/programs.svg
                :width: 32px
                :alt: Icon of the Course Finder content element
        -   :ref:`Course Finder <integrator-program-finder>`
        -   ``academicprograms_programfinder``
        -   A few category selects and a button that open a
            :guilabel:`Course List` with that selection.

The three content elements are hidden until a site enables them, see
:ref:`configuration-components`.

..  toctree::
    :hidden:
    :titlesonly:

    ProgramList
    ProgramDetails
    ProgramFinder
