..  _developers-event-modify-program-list-event:

======================
ModifyProgramListEvent
======================

:php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramListEvent` hands a listener
the programs a program list or a program finder found, the categories its
filter offers, and its view. A listener replaces the programs, replaces the
categories, or assigns further variables to the view.

When it is dispatched
=====================

:php:`ProgramController` dispatches the event once per rendering, in
:php:`listAction()` of the :guilabel:`Course List` and :php:`finderAction()` of
the :guilabel:`Course Finder`. It follows
:ref:`developers-event-modify-program-demand-event`: the programs are queried
with the demand that event handed back, the categories are computed from
them, and then this event fires, before the action assigns its view
variables. :ref:`developers-event-modify-plugin-view-event` follows once they
are assigned.

What the two actions take from the event differs:

*   The list renders the programs as cards and the categories as the options of
    its filter.
*   The finder renders no program. Its selects offer the categories, and its
    preselection is read from them. The programs are the ones it narrows its
    options by in the browser and counts on its button, see
    :ref:`program-finder-narrowing`.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Method
        -   What it does
    *   -   :php:`getPrograms()`, :php:`setPrograms()`
        -   The programs, an Extbase query result of
            :php:`\FGTCLB\AcademicPrograms\Domain\Model\Program`. The setter
            takes a query result, not an array.
    *   -   :php:`getCategories()`, :php:`setCategories()`
        -   The categories of the group `programs` the filter or the finder
            offers, a :php:`\FGTCLB\CategoryTypes\Collection\CategoryCollection`
            in which the categories no program carries are marked disabled.
    *   -   :php:`getDemand()`
        -   The demand the programs were queried with, for reading.
    *   -   :php:`getView()`
        -   The view of the action. A variable assigned to it reaches the
            templates.
    *   -   :php:`getPluginControllerActionContext()`
        -   The context of the rendering, as in the demand event. The plugin
            name is :php:`ProgramList` or :php:`ProgramFinder`.

A listener assigns values a project template renders:

..  code-block:: php

    $event->getView()->assign('programCount', count($event->getPrograms()->toArray()));

The seven variables each action assigns after the event win over a variable of
the same name a listener assigned here: :html:`programs`, :html:`data`,
:html:`record`, :html:`demand`, :html:`categories`, :html:`filterTypes` and
:html:`visitorSelection` in the list, :html:`data`, :html:`record`,
:html:`categories`, :html:`filterTypes`, :html:`listUri`,
:html:`preselection` and :html:`programCategories` in the finder. The plugin
view event, which runs last, can replace them.

Example
=======

A site leads visitors from its finder only to programs they can apply for.
Programs without an application link stay in the list, but the finder neither
counts them nor offers the categories only they carry. The listener narrows
the query of the finder and computes the categories from what is left, the way
the controller does:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/FindOnlyProgramsOpenForApplication.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPrograms\Event\ModifyProgramListEvent;
    use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class FindOnlyProgramsOpenForApplication
    {
        public function __construct(
            private CategoryRepository $categoryRepository,
        ) {}

        #[AsEventListener(identifier: 'my-extension/find-only-programs-open-for-application')]
        public function __invoke(ModifyProgramListEvent $event): void
        {
            if ($event->getPluginControllerActionContext()->getPluginName() !== 'ProgramFinder') {
                return;
            }

            // The query of the result, with its constraints and its ordering.
            $query = $event->getPrograms()->getQuery();
            $withLink = $query->logicalNot($query->equals('applicationLink', ''));
            $constraint = $query->getConstraint();
            $query->matching($constraint === null ? $withLink : $query->logicalAnd($constraint, $withLink));
            $programs = $query->execute();
            $event->setPrograms($programs);

            $list = array_values($programs->toArray());
            $event->setCategories(
                $event->getDemand()->getIncludeSubcategories()
                    ? $this->categoryRepository->findAllApplicableWithSubcategories('programs', ...$list)
                    : $this->categoryRepository->findAllApplicable('programs', ...$list),
            );
        }
    }

The finder now disables a category that only programs without an application
link carry, and its count on the button agrees with that. The list on the
target page still finds every program of the selection, the listener leaves it
alone.

Rules and pitfalls
==================

**A replaced result is rendered as it is.** :php:`setPrograms()` takes whatever
query result a listener hands back, and the ordering of the repository is not
reapplied to it. A listener that builds its own result gives it its own
ordering, or the list is in whatever order the database happens to return,
which is not the same list twice on PostgreSQL. The query of the handed result,
as in the example, keeps the ordering.

**The categories are not recomputed.** They are computed from the queried
programs, once, before the event. A listener that replaces the programs and
wants the filter of the plugin to match them sets the categories too, and
builds them the way the controller does, as in the example. Both repository
methods keep every category of the group and mark the ones no listed program
carries as disabled options. :php:`findAllApplicableWithSubcategories()` also
enables a category as soon as a program carries one of its subcategories. :php:`findByGroupAndUidList()`
returns a bare list instead, so a listener that reaches for that one drops the
disabled options the filter otherwise shows.

**In the finder, the categories decide the options and the programs narrow
them.** Its selects offer the categories the event hands back, and its
preselection is read from them. A listener that removes a program from the
finder therefore changes the narrowing and the count. An option that only this
program carried is still offered by the server, and the browser disables it as
soon as the page has loaded. Without JavaScript it stays selectable, unless the
listener sets the categories as well. Which programs a finder submission finds
is decided by the list on the target page and its own events.

**Hidden programs on TYPO3 v13.** With :guilabel:`Show hidden records`
switched on, the extension fetches the programs so that a translated page shows
the translation of a hidden program page. A result a listener executes itself
does not get that treatment on TYPO3 v13: a hidden program page whose
translation is hidden as well renders in the default language there. TYPO3 v14
handles it in the core, and nothing differs.

Introduced in
=============

*   :ref:`feature-1790604743` added the event in 3.0.
*   :ref:`breaking-1791043408` made the controllers final and names the event
    as the replacement of a subclass that changed the result or the view
    variables.
*   :ref:`feature-1790975989` made the programs of the finder narrow its
    options in the browser.
