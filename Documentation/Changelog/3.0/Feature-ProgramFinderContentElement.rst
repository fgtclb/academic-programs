..  _feature-1790367619:

=========================================
Feature: A program finder content element
=========================================

Description
===========

The new content element :guilabel:`Program Finder` is a compact entry into a
program list: one select per category type and a button, for a home page hero
for example. Submitting it opens a program list page with the selection
applied, and the list shows the selection in its own filter unless it hides
the filter.

..  list-table::
    :header-rows: 1

    *   -   Field
        -   Meaning
    *   -   :guilabel:`Program list page`
        -   Required. The page whose :guilabel:`Program List` the finder opens.
    *   -   :guilabel:`Filter types`
        -   The selects, in their order. Empty: the site setting
            :typoscript:`plugin.tx_academicprograms.filter.categoryTypes` of
            the program list, then the degree and the topic.
    *   -   :guilabel:`Preselected categories`
        -   Selected when the page loads, one per select; of two categories of
            one type the one higher in the category tree.

The options are the categories of the programs in the storage of the finder
element, the ones no program there carries disabled. Point the finder at the
storage of the list it targets.

The element is enabled by the new set `fgtclb/academic-programs-program-finder`,
which the aggregate set `fgtclb/academic-programs` includes, and by the static
template and page TSconfig :guilabel:`Academic Programs: Program Finder` for a
site without site sets.

See :ref:`program-finder`.

Impact
======

A site that depends on the aggregate set, or selects the static template or page
TSconfig :guilabel:`All components`, offers the new element to its editors
after the update. A site that names component sets only does not, until it adds
the finder set.

The element renders the template :file:`Program/Finder.html`. Its form posts
to the list plugin of the target page, in the argument shape of the list's own
filter form, :html:`tx_academicprograms_programlist[demand][filterCollection][<type>]`.

..  index:: Backend, Frontend, FlexForm, Fluid, TCA, TSConfig, TypoScript, ext:academic_programs
