..  index:: Configuration; Labels
..  _configuration-labels:

======
Labels
======

The labels this extension shows in the frontend come from
:file:`EXT:academic_programs/Resources/Private/Language/locallang.xlf` and its
translations; the table below names the ones that come from another file. A site
changes a label without copying a template, in TypoScript:
under :typoscript:`plugin.tx_academicprograms._LOCAL_LANG` for every content element
of the extension, or under :typoscript:`plugin.tx_academicprograms_<plugin>._LOCAL_LANG`
for one of them. A label set for the plugin wins over one set for the extension.

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    plugin.tx_academicprograms._LOCAL_LANG {
      default.sorting.field.label = Sort by
      de.sorting.field.label = Sortieren nach
    }

    # Only in one content element:
    plugin.tx_academicprograms_programlist._LOCAL_LANG.default.sorting.field.label = Sort by

The dots of a key need no escaping: TypoScript reads them as levels of its tree,
and TYPO3 joins the levels to the key again. A label of another language goes
under its language key, :typoscript:`de` for German.

..  list-table:: The path of each content element
    :header-rows: 1

    *   - Content element
        - Path
    *   - :guilabel:`Program List` (:typoscript:`academicprograms_programlist`)
        - :typoscript:`plugin.tx_academicprograms_programlist._LOCAL_LANG`
    *   - :guilabel:`Program Details` (:typoscript:`academicprograms_programdetails`)
        - :typoscript:`plugin.tx_academicprograms_programdetails._LOCAL_LANG`

The page template of a program page is not rendered by a plugin: a site sets
its labels under the path of the extension.

A language file override works as well, and replaces the label of the file
itself: :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']`.

Earlier versions of this extension read these overrides on TYPO3 v12 and v13 from
:typoscript:`plugin.tx_academic_programs` instead, see
:ref:`the changelog <important-label-overrides-use-the-documented-path>`.

Where the labels are shown
==========================

Placeholders in angle brackets stand for a part of the key that the template
or the code fills in, a category type or a field name for example.

..  list-table::
    :header-rows: 1
    :widths: 45 55

    *   - Key
        - Shown by
    *   - :xml:`filter.moreFilters`
        - :file:`Partials/Program/DemandCategories.html`
    *   - :xml:`list.noProgramsFound`
        - :file:`Partials/Program/ItemList.html`
    *   - :xml:`program.<attribute>`
        - :file:`Pages/AcademicProgram.html`
    *   - :xml:`sorting.direction.label`
        - :file:`Partials/Program/DemandSorting.html`
    *   - :xml:`sorting.field.label`
        - :file:`Partials/Program/DemandSorting.html`
    *   - :xml:`sys_category.programs.<type>`
        - :file:`Partials/Program/Categories.html`, :file:`Partials/Program/DemandCategories.html`
    *   - :xml:`sys_category.programs.allOptions`
        - :file:`Partials/Program/DemandCategories.html`
    *   - :xml:`sys_category.programs.allOptions.<type>`
        - :file:`Partials/Program/DemandCategories.html`
    *   - :xml:`sys_category.programs.degree`
        - :file:`Partials/Program/Item.html`
    *   - :xml:`sorting.field.<field>`, :xml:`sorting.direction.<direction>`
        - The options of the sorting select, translated by its view helper
