.. _feature-1791035982:

====================================================
Feature: Own category types are named by their title
====================================================

Description
===========

A project may register category types of its own for the group `programs`. The
frontend named a type by the label :xml:`sys_category.programs.<type>` of this
extension, which only knows the shipped types, so such a type showed no label
unless a site added one through :typoscript:`_LOCAL_LANG`.

A type without a label is now named by the title it is registered with in
:file:`Configuration/CategoryTypes.yaml`, in the language of the page. A title
that is an :php:`LLL:` reference is translated, a literal title is shown as
written:

..  code-block:: yaml
    :caption: EXT:site_package/Configuration/CategoryTypes.yaml

    types:
      - identifier: teaching_form
        title: 'LLL:EXT:site_package/Resources/Private/Language/locallang_be.xlf:teaching_form'
        group: programs
        icon: 'EXT:site_package/Resources/Public/Icons/TeachingForm.svg'

A label of this extension, and one a site sets under
:typoscript:`plugin.tx_academicprograms._LOCAL_LANG` or under the
path of one plugin, still wins. The shipped types keep their labels.

It applies to

*   the facts of the program page, of the :guilabel:`Program Details` content
    element and of each card of the :guilabel:`Program List`,
    :file:`Partials/Program/Facts/Item.html`;
*   the filter selects of the :guilabel:`Program List`,
    :file:`Partials/Program/DemandCategories.html`;
*   the selects of the :guilabel:`Program Finder`,
    :file:`Templates/Program/Finder.html`.

See :ref:`configuration-labels`.

Impact
======

A template override of one of the partials or templates above keeps the old
lookup until it hands the label to the view helper
:html:`ct:categoryTypeTitle` of `EXT:category_types`, which renders the title
when the label is empty:

..  code-block:: html
    :caption: EXT:site_package/Resources/Private/Partials/Program/DemandCategories.html

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
          xmlns:ct="http://typo3.org/ns/FGTCLB/CategoryTypes/ViewHelpers"
          data-namespace-typo3-fluid="true">

    {f:translate(key: 'sys_category.programs.{categoryKey}', extensionName: 'AcademicPrograms')
        -> ct:categoryTypeTitle(group: 'programs', identifier: categoryKey)}

An empty label counts as none: a label a site blanks through
:typoscript:`_LOCAL_LANG` now falls back to the registered title.

.. index:: Frontend, Fluid, NotScanned, ext:academic_programs
