..  _developers:

==============
For developers
==============

The program list and the program finder dispatch two PSR-14 events each, and a
program page one. They are the supported way to change what a plugin queries
and what a page renders, and they replace what projects did before: subclassing
:php:`ProgramController`, querying the database from ViewHelpers, or replacing
the data processor of the program page.

Which classes of this extension are public API, and what that promises, is
stated for all academic extensions on the `extension points page of
academic_base <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Developers/ExtensionPoints/Index.html>`__.
Every plugin of this extension, the program details included, also dispatches
:php:`ModifyPluginViewEvent` of :guilabel:`academic_base` when it renders,
after the list event where there is one. That page describes it.

..  _developers-program-list-events:

The list and finder events
==========================

..  list-table::
    :header-rows: 1

    *   -   Event
        -   Dispatched
        -   A listener may
    *   -   :php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramDemandEvent`
        -   in :php:`listAction()` and :php:`finderAction()`, after the demand
            is built from the content element settings and the request and
            before the programs are queried
        -   replace the demand
    *   -   :php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramListEvent`
        -   in both actions, after the query and before the view variables are
            assigned
        -   replace the programs, replace the applicable categories, assign
            further view variables

Both carry
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
through :php:`getPluginControllerActionContext()`: the request, the site and
its language, the content object of the element, the settings of the content
element and the plugin name.

The list answers a submission of its filter and sorting form with a redirect to
a URL carrying the selection, before the demand event. Both events fire on the
request that follows, with the selection in the query string (or the route),
not in the parsed body. See :ref:`feature-1790226102`. The finder submits to
the list on its target page, so a finder submission reaches the events of that
list, not those of the finder.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ListOnlyFullTimePrograms.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPrograms\Event\ModifyProgramDemandEvent;
    use FGTCLB\CategoryTypes\Collection\FilterCollection;
    use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class ListOnlyFullTimePrograms
    {
        public function __construct(
            private readonly CategoryRepository $categoryRepository,
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

The plugin name is the name the plugin was **registered** with, not the content
element type: :php:`ProgramList` for the list and :php:`ProgramFinder` for the
finder. It is how one listener serves both and acts on one:

..  code-block:: php

    if ($event->getPluginControllerActionContext()->getPluginName() !== 'ProgramList') {
        return;
    }

:php:`ModifyProgramListEvent` carries the view as well, so a listener assigns
values a project template renders:

..  code-block:: php

    $event->getView()->assign('programCount', count($event->getPrograms()->toArray()));

The seven variables the action assigns after the event win over a variable of the
same name a listener assigned here. The plugin view event, which runs last, can
replace them.

..  _developers-program-list-events-rules:

Rules worth knowing
===================

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

**A replaced result is rendered as it is.** :php:`setPrograms()` takes whatever
query result a listener hands back, and the ordering of the repository is not
reapplied to it. A listener that builds its own result gives it its own
ordering, or the list is in whatever order the database happens to return,
which is not the same list twice on PostgreSQL.

**The categories are not recomputed.** They are computed from the queried
programs, once, before the list event. A listener that replaces the programs
and wants the filter of the plugin to match them sets the categories too, and
builds them the way the controller does:

..  code-block:: php

    $programs = $narrowedResult->toArray();
    $event->setPrograms($narrowedResult);
    $event->setCategories(
        $event->getDemand()->getIncludeSubcategories()
            ? $this->categoryRepository->findAllApplicableWithSubcategories('programs', ...$programs)
            : $this->categoryRepository->findAllApplicable('programs', ...$programs),
    );

Both keep every category of the group and mark the ones no listed program
carries as disabled options, the first one enabling a category as soon as a
program carries one of its subcategories. :php:`findByGroupAndUidList()`
returns a bare list instead, so a listener that reaches for that one drops the
disabled options the filter otherwise shows.

**The finder renders categories, not programs.** Its selects offer the
categories the list event hands back, and its preselection is read from them.
A listener that replaces the programs of the finder changes nothing a visitor
sees unless it sets the categories as well. Which programs a finder submission
finds is decided by the list on the target page and its own events.

..  _developers-program-data-event:

The program page event
======================

:php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramDataEvent` is dispatched by
the data processor of the program page, :typoscript:`program-data`, once it has
built the data of the page from the page record, and before the facts of the
page are built from that data. It carries the data
(:php:`\FGTCLB\AcademicPrograms\Domain\Model\ProgramData`, with
:php:`setProgram()`), the page record and the request. The data a listener
hands back is what the page template receives as :html:`{program}`, and the
facts show it.

A listener may change the data it is handed, or hand back an object of its own.
A subclass of :php:`ProgramData` carries whatever a project template needs
beyond the fields the extension maps. In this example,
:php:`ProgramDataWithHeroImage` is such a subclass of the project, with a hero
image property and a named constructor that copies the fields of the data it is
given:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddProgramHeroImage.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPrograms\Event\ModifyProgramDataEvent;
    use MyVendor\MyExtension\Domain\Model\ProgramDataWithHeroImage;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Resource\FileRepository;

    final class AddProgramHeroImage
    {
        public function __construct(
            private readonly FileRepository $fileRepository,
        ) {}

        #[AsEventListener(identifier: 'my-extension/add-program-hero-image')]
        public function __invoke(ModifyProgramDataEvent $event): void
        {
            $page = $event->getPageRecord();
            // File references belong to the page in the language it renders in,
            // and an overlaid record keeps the uid of the default language.
            $pageUid = (int)($page['_LOCALIZED_UID'] ?? $page['uid']);
            $program = ProgramDataWithHeroImage::fromProgramData($event->getProgram());
            $program->setHeroImage(
                $this->fileRepository->findByRelation('pages', 'tx_mysitepackage_hero', $pageUid)[0] ?? null,
            );
            $event->setProgram($program);
        }
    }

The event reaches the program page template only. The
:guilabel:`Program Details` content element reads the program model instead,
and the plugin view event is the way to change what it renders.

Nothing changes in an installation that has no listener: without one, both
plugins query and render, and program pages show, exactly what they did
before.

..  index:: Frontend, PHP-API, ext:academic_programs
