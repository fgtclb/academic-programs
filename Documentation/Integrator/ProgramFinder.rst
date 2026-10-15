..  _integrator-program-finder:

=============
Course Finder
=============

..  image:: /Images/Icons/programs.svg
    :width: 48px
    :alt: Icon of the Course Finder content element

:CType:
    ``academicprograms_programfinder``

:Plugin:
    `AcademicPrograms`, plugin `ProgramFinder`, action
    :php:`ProgramController::finderAction()`

:Set:
    `fgtclb/academic-programs-program-finder`, included in
    `fgtclb/academic-programs`

The :guilabel:`Course Finder` is a compact entry into a program list, for a
home page hero for example: a few selects and a button that open a
:ref:`Course List <integrator-program-list>` on another page with the
selection applied. It shows no programs itself. Use it where a visitor should
start a search, and the list for the page the search leads to.

The element renders the template :file:`Program/Finder.html`, described in
:ref:`styling-finder`. Like the list it is rendered on every request rather
than from the page cache: which options are disabled depends on program pages
elsewhere in the page tree, and a cached finder would keep offering what
changed there.

..  _program-finder:

What the finder renders
=======================

The block below is the markup the server renders for the finder of the
development instance, which preselects a degree. It is the state before the
module of the finder wrote the number of programs on the button, which the
figure shows.

..  code-block:: text
    :caption: What the finder renders, unstyled, before the module wrote the count

    Degree   [ Bachelor of Engineering v ]   Topic [ All options v ]   [ Show programs ]

..  figure:: /Images/Screenshots/program-finder-default.png
    :alt: Two selects, Degree set to "Bachelor of Engineering" and Topic set to "All options", and a button "Show 2 programs"
    :class: with-border with-shadow

    The Course Finder with a preselected degree and the number of programs
    on its button.

The options of each select are the categories of its type. A category that no
program in the storage of the finder carries is offered disabled, as in the
filter of the list, or left out when the site hides options without results
with the setting
:typoscript:`plugin.tx_academicprograms.filter.hideDisabledOptions`, see
:ref:`configuration-filter-settings`. That section also describes the label
of the "All" option, which the finder reads the same way. The storage is the
:guilabel:`Startingpoint` and :guilabel:`Recursive` of the finder element, and
it should be the storage of the list the finder targets. The finder cannot
read the storage of another element, and a finder pointing elsewhere offers
categories the list does not show, or disables ones it does. An empty
:guilabel:`Startingpoint` means every program page of the installation, of
every site, for the finder as for the list.

In the browser the finder narrows the options further, to the combinations
that find a program, and states the number of programs on its button, see
:ref:`program-finder-narrowing`.

Submitting the form posts the selection to the list plugin of the target page,
in the argument shape of the list's own filter form:
:html:`tx_academicprograms_programlist[demand][filterCollection][<type>]`, one
category uid per type. The list answers with a redirect to its filtered URL, so
the target page opens with the programs that carry every selected category, and
with the selection shown in its own filter, unless that list hides its filter.

The selection of the finder replaces the :guilabel:`Default categories` of the
target list, as a selection in the list's own filter does. The list keeps its
:guilabel:`Default sorting`: the finder submits no sorting, and a submission
without one is sorted the way the element is configured.

A finder renders no form without a target page, with a target page that cannot
be linked, because it is hidden, deleted or access restricted, and when none
of its category types has a category. The frame and the header of the element
still render.
The backend does not save a finder without a target page, but a record written
by an import or an upgrade can lack it, and a page can be hidden later.

..  versionadded:: 3.0

    Up to 2.x a project that wanted a finder registered one itself. See
    :ref:`breaking-program-finder-registered-upstream` for what such a project
    removes.

..  _integrator-program-finder-configuration:

Settings
========

The tab :guilabel:`Configuration` of the content element holds every option.

..  confval:: Program list page
    :name: program-finder-list-pid
    :type: page
    :Default: empty

    The page whose :guilabel:`Course List` the finder opens. The field is
    required: the backend does not save the element without it. The finder submits to the list plugin of
    that page, so the page needs a :guilabel:`Course List` element. A page
    without one shows its content and ignores the selection.

..  confval:: Filter types
    :name: program-finder-filter-types
    :type: select, several, ordered
    :Default: empty

    One select per chosen category type, in the chosen order, as the field of
    the same name of the list. A chosen type without categories is left out.

    The field is listed in :typoscript:`ignoreFlexFormSettingsIfEmpty`, like
    the field of the list. Empty, the finder offers the types of the site
    setting :typoscript:`plugin.tx_academicprograms.filter.categoryTypes`, see
    :ref:`program-list-filter-types`, and the degree followed by the topic when
    that is empty as well. That last fallback differs from the list, which
    offers every type with categories, so a finder and a list that both leave
    the field and the setting empty offer different selects.

..  confval:: Include subcategories
    :name: program-finder-include-subcategories
    :type: checkbox
    :Default: off

    Offers a category as soon as a program in the storage of the finder
    carries one of its subcategories, and preselects it as well, see
    :ref:`program-list-subcategories`. The list on the target page needs the
    field as well, because it decides which programs are found.

..  confval:: Preselected categories
    :name: program-finder-preselected-categories
    :type: category tree
    :Default: empty

    Selected when the page loads, one per select. Of two categories of one
    type the one higher in the category tree counts. A category of a type the
    finder does not offer, and one the finder offers disabled, is not
    preselected. The tree starts at the categories the site setting
    :typoscript:`plugin.tx_academicprograms.categoryRootUids` names, see
    :ref:`configuration-category-tree-root`.

    Use it on a page about one subject, a faculty page for example, so the
    finder starts with that subject and the visitor only picks the degree.

..  confval:: Startingpoint
    :name: program-finder-pages
    :type: page
    :Default: empty

    The pages below which the finder looks for program pages, as the field of
    the same name of the list. It decides which categories the finder offers
    and which programs it counts. Select the same pages as in the list on the
    target page.

..  confval:: Recursive
    :name: program-finder-recursive
    :type: select
    :Default: 0 levels (only selected page)

    How many levels below the :guilabel:`Startingpoint` the finder looks, as
    the field of the same name of the list. Select the same depth as in the
    list on the target page.

..  _program-finder-narrowing:

Narrowing the options in the browser
====================================

The finder offers only the combinations that find a program. The selections
of all selects have to match together, as in the list the finder opens: once a
visitor selects a degree, every option of the other selects that no program of
that degree carries is disabled, and enabled again as soon as the degree is
cleared or changed. A preselected category narrows the other selects as soon
as the page has loaded. Nothing is requested from the server for it, and the
page does not reload.

..  code-block:: text
    :caption: The finder after selecting a degree, unstyled

    Degree   [ Master of Science v ]   Topic [ All options v ]   [ Show 2 programs ]
                                               ( Informatics, disabled )

The button states how many programs the current selection finds, with the
labels `finder.submit.count.one` and `finder.submit.count.other`, `%d` standing
for the number. A changed number is announced to screen reader users by a
visually hidden element with the role `status` and the classes
`ace-status visually-hidden`: politely, so it waits for the current speech,
and without moving the focus. Loading the page announces nothing. The element
is hidden by the class `visually-hidden` of Bootstrap, so a site without
Bootstrap provides that class itself, as it provides the other Bootstrap
classes the template keeps, the grid and the button.

..  figure:: /Images/Screenshots/program-finder-narrowed.png
    :alt: Degree set to "Master of Science", the Topic select opened with the option "Informatics" greyed out, and the button "Show 2 programs"
    :class: with-border with-shadow

    After a degree is selected, the finder disables the topics no program of
    that degree carries and states the number of programs on its button.

The number counts the programs of the finder's own storage, with its own
:guilabel:`Include subcategories`. It agrees with the list on the target page
as long as both elements share the storage and that field, the same condition
the options of the finder already depend on.

Narrowing disables an option, it never leaves one out, whatever
:typoscript:`plugin.tx_academicprograms.filter.hideDisabledOptions` says. The
setting decides which options the server renders: the ones no program in
storage carries are left out. An option that only the current selection
excludes stays in its place, so the selects do not change their length while
the visitor works through them. An option the server rendered disabled stays
disabled, and the option a select shows stays selectable even when the other
selections exclude it, so a preselection whose categories exclude each other
can still be changed.

The programs come with the page: the form carries, in the attribute
`data-academic-programs-finder-programs`, a JSON list with one entry per
program the finder found in its storage, the uids of the categories of the
offered selects it carries. The uids of the programs themselves are not part
of it. With :guilabel:`Include subcategories` switched on, a program carries
every category above its own as well, the way the server offers such a parent.
Hidden programs, programs outside the storage and programs a listener of
:ref:`ModifyProgramListEvent <developers-event-modify-program-list-event>`
removed are not part of it. A few hundred programs stay in the low kilobytes.

Without JavaScript the finder renders as described above, with every option
that a program in its storage carries selectable and the plain label on the
button. The module is
:js:`@fgtclb/academic-programs/frontend/program-finder.js`, loaded only on a
page whose finder renders a form. A site that brings its own script switches
it off, see :ref:`configuration-javascript`.

An override of :file:`Program/Finder.html` keeps the narrowing by keeping the
attributes the module reads:

..  list-table::
    :header-rows: 1

    *   -   Attribute
        -   On
        -   Carries
    *   -   `data-academic-programs-finder-programs`
        -   The form
        -   The programs as JSON, rendered by the action as
            `{programCategories}`. Without a list there the module does not
            start.
    *   -   `data-academic-programs-finder-count-one`, `data-academic-programs-finder-count-other`
        -   The form
        -   The patterns of the count sentence. Without both of them the
            options are narrowed, the count keeps its label and nothing is
            announced.
    *   -   `data-academic-programs-finder-select`
        -   Each category select
        -   Nothing. Only a marked select takes part, so a select the override
            adds for something else is left alone.
    *   -   `data-academic-programs-finder-count`
        -   A span inside the submit button, or any element in the form
        -   Nothing. Its text is replaced by the count sentence, which leaves
            an icon next to it in the button alone.
    *   -   `data-academic-programs-finder-status`
        -   An empty element in the form with `role="status"`
        -   Nothing. The count sentence is written into it when the number
            changes.

An option with an empty value is the "All" option, which is never disabled.

..  versionadded:: 3.0

    Up to 2.x projects reloaded the whole finder on every change, through a
    turbo frame for example, to offer only possible combinations.
