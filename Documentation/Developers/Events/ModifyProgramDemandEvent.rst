..  _developers-event-modify-program-demand-event:

========================
ModifyProgramDemandEvent
========================

:php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramDemandEvent` hands a
listener the demand a program list or a program finder is about to be queried
with. The demand a listener hands back is the one that is queried, and in the
list the one assigned to the view as :html:`{demand}`.

When it is dispatched
=====================

:php:`ProgramController` dispatches the event once per rendering, in two
actions:

*   :php:`listAction()` of the :guilabel:`Course List`, after the demand is
    built from the settings of the content element and the selection of the
    visitor, and before the programs are queried.
*   :php:`finderAction()` of the :guilabel:`Course Finder`, after the demand is
    built from the settings of the content element, and before the programs
    the finder offers its categories for are queried. The finder has no
    selection of the visitor to read.

The list answers a submission of its filter and sorting form with a redirect
to a URL carrying the selection, before the event. The event fires on the
request that follows, with the selection in the query string (or the route),
not in the parsed body, see :ref:`feature-1790226102`. The finder submits to
the list on its target page, so a finder submission reaches the event of that
list, not the one of the finder.

:ref:`developers-event-modify-program-list-event` follows once the programs
are queried.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Method
        -   What it does
    *   -   :php:`getDemand()`
        -   The demand,
            :php:`\FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand`.
            It is an object, so a change to it takes effect without
            :php:`setDemand()`.
    *   -   :php:`setDemand()`
        -   Replaces the demand that is queried.
    *   -   :php:`getPluginControllerActionContext()`
        -   The context of the rendering,
            :php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`:
            the request, the site and its language, the content object of the
            element, the settings of the content element, the plugin name and
            the action name.

What the query reads from the demand:

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Getter and setter
        -   Meaning
    *   -   :php:`getPages()`, :php:`setPages()`
        -   The uids of the pages the program pages sit directly below: the
            :guilabel:`Startingpoint` of the element with its
            :guilabel:`Recursive` levels already resolved. Empty means every
            program page of the installation.
    *   -   :php:`getFilterCollection()`, :php:`setFilterCollection()`
        -   The selected categories, those of the visitor or the
            :guilabel:`Default categories` of the element, as a
            :php:`\FGTCLB\CategoryTypes\Collection\FilterCollection`, or
            :php:`null` for none. A program has to carry every one of them.
    *   -   :php:`getIncludeSubcategories()`, :php:`setIncludeSubcategories()`
        -   Whether each selected category also finds the programs that carry
            one of its subcategories, the field :guilabel:`Include
            subcategories` of the element.
    *   -   :php:`getSorting()`, :php:`setSorting()`, and the field and the
            direction on their own
        -   The ordering, one of the values of
            :php:`\FGTCLB\AcademicPrograms\Enumeration\SortingOptions`, for
            example `title asc`.
    *   -   :php:`getShowHiddenRecords()`, :php:`setShowHiddenRecords()`
        -   Whether hidden program pages are listed, the field
            :guilabel:`Show hidden records` of the element.

The plugin name on the context is the name the plugin was **registered** with,
not the content element type: :php:`ProgramList` for the list and
:php:`ProgramFinder` for the finder. It is how one listener serves both and
acts on one:

..  code-block:: php

    if ($event->getPluginControllerActionContext()->getPluginName() !== 'ProgramList') {
        return;
    }

Example
=======

A site lists only full-time programs in its program lists, whatever else the
visitor selects. Full-time is the category with the uid 42 of the category
type `program_type`. The listener adds it to the categories of the demand:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ListOnlyFullTimePrograms.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPrograms\Event\ModifyProgramDemandEvent;
    use FGTCLB\CategoryTypes\Collection\FilterCollection;
    use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class ListOnlyFullTimePrograms
    {
        public function __construct(
            private CategoryRepository $categoryRepository,
        ) {}

        #[AsEventListener(identifier: 'my-extension/list-only-full-time-programs')]
        public function __invoke(ModifyProgramDemandEvent $event): void
        {
            $demand = $event->getDemand();
            // The categories the visitor selected, and the full-time category 42.
            $uids = [42];
            foreach ($demand->getFilterCollection()?->getFilterCategories() ?? [] as $category) {
                $uids[] = $category->getUid();
            }
            $demand->setFilterCollection(new FilterCollection(
                $this->categoryRepository->findByGroupAndUidList('programs', $uids),
            ));
        }
    }

The listener changes the demand it was handed rather than building a new one,
so the storage, the sorting and the other choices of the editor and the
visitor stay. It acts on the list and the finder alike: the finder then
disables every category no full-time program carries.

..  _developers-program-list-events-rules:

Rules and pitfalls
==================

..  warning::

    **A demand listener widens as easily as it narrows.** The demand *is* the
    query here, not a constraint added to one, and nothing guards it:
    :php:`setShowHiddenRecords(true)` shows hidden program pages to every
    visitor, :php:`setPages([])` drops the storage restriction the editor
    chose in the content element, and :php:`setSorting()` overrides the
    editor's ordering for every element that renders, though only for a
    value that is one of the :php:`SortingOptions` constants, since anything
    else is ignored without a word. Two listeners that disagree are resolved by
    the order they run in, and the last one wins.

    What a listener cannot undo: the page type is pinned unconditionally,
    :php:`showHiddenRecords` reaches the :php:`disabled` flag alone (start- and
    endtime, :php:`fe_group` and :php:`deleted` stay in effect), and a
    :php:`uid` tiebreaker is always appended to the ordering.

**Subcategories are added when the programs are queried.** With the field
:guilabel:`Include subcategories` of the element switched on, the demand holds
the categories as they were selected, and the query widens each of them by its
subcategories (see :ref:`program-list-subcategories`). A category a listener
adds is widened the same way, and a listener that calls
:php:`setIncludeSubcategories()` changes how every selected category matches.

**A category a listener adds is shown as an active filter.** The active filter
tags of :ref:`configuration-active-filters` read the demand the list was
queried with, as the selects of the filter form do. A category a listener adds
to every request is therefore a tag the visitor cannot remove, since the
listener adds it again on the page the tag leads to, and every other tag link
carries it. A listener that forces a category should leave the tags switched
off, or override :file:`Partials/Program/ActiveFilters.html`. A collection a
listener builds without the type identifiers of the group, through
:php:`new CategoryCollection()` and :php:`attach()`, still filters the list
but gets no tags, because the tags are grouped by type.

**A replaced demand starts from the defaults.** :php:`setDemand()` is there for
a listener that builds its own demand, and such a demand carries none of what
the plugin put in the one it was handed: the storage pages, the
:php:`showHiddenRecords` choice of the editor, the subcategory option, and the
two the visitor can set themselves through the filter form, the sorting and
the :php:`filterCollection`. A replacement therefore resets the ordering and
the category filter under them. Mutate the demand where that is enough, and
carry :php:`getPages()`, :php:`getShowHiddenRecords()`,
:php:`getIncludeSubcategories()`, :php:`getFilterCollection()` and the sorting
over where it is not.

**A field the project adds to the filter form does not reach the event.** The
redirect after a submission carries the selection the plugin accepted and
nothing else, so a listener cannot read a field of its own from the request
that follows.

Introduced in
=============

*   :ref:`feature-1790604743` added the event in 3.0.
*   :ref:`breaking-1791043408` made the controllers final and names the event
    as the replacement of a subclass that changed the demand.
*   :ref:`feature-1790226102` answers a filter submission with a redirect, so
    the event fires on the request that follows it.
