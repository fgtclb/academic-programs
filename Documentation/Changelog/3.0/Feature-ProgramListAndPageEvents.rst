..  _feature-1790604743:

==========================================================
Feature: Change the program list, finder and page by event
==========================================================

Description
===========

Three PSR-14 events let a project change what the program plugins query and
what a program page shows, without replacing a class of the extension:

*   :php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramDemandEvent` fires in the
    :guilabel:`Program List` and the :guilabel:`Program Finder` after the
    demand has been built from the content element settings and the request,
    and before the programs are queried. The demand a listener hands back is
    the one that is queried, and in the list the one assigned to the view as
    :html:`{demand}`.
*   :php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramListEvent` fires in both
    after the query and before the view variables are assigned. A listener
    replaces the programs, replaces the applicable categories, which are the
    filter options of the list and the select options of the finder, or
    assigns further view variables.
*   :php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramDataEvent` fires on a
    program page once the data processor :typoscript:`program-data` has built
    the data of the page from the page record, and before the facts of the
    page are built from it. The data a listener hands back is what the page
    template receives as :html:`{program}`, and the facts show it.

The list and finder events carry
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`,
so a listener knows the request, the site, the content element, its settings
and whether the list or the finder is rendering. The page event carries the
page record and the request.

The events have the names, the arguments and the rules of the partner and
project list events. :php:`\FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand`
and :php:`\FGTCLB\AcademicPrograms\Domain\Model\ProgramData`, which they hand
over, are public API from this version on. The events, their rules and an
example listener for each are described in :ref:`developers`.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ReplaceProgramSubtitle.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPrograms\Event\ModifyProgramDataEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class ReplaceProgramSubtitle
    {
        #[AsEventListener(identifier: 'my-extension/replace-program-subtitle')]
        public function __invoke(ModifyProgramDataEvent $event): void
        {
            $page = $event->getPageRecord();
            if (($page['nav_title'] ?? '') !== '') {
                $event->getProgram()->setSubtitle((string)$page['nav_title']);
            }
        }
    }

Impact
======

Nothing changes in an installation that has no listener. A project that
queries the database from a ViewHelper for data of the program page, or
replaces the data processor of the program page, can listen instead. A project
that subclasses :php:`ProgramController` to adjust the demand, the result or the
view variables has to listen instead, because the controller is final in 3.0
and the subclass no longer loads, see :ref:`breaking-1791043408`.

The constructor of
:php:`\FGTCLB\AcademicPrograms\DataProcessing\ProgramDataProcessor` takes two
more arguments, both resolved by dependency injection:

..  code-block:: php

    public function __construct(
        ProgramFactsBuilder $programFactsBuilder,
        ProgramDataFactory $programDataFactory,
        EventDispatcherInterface $eventDispatcher,
    )

A project that extends the processor and calls the parent constructor passes
them on. The processor is no public API, and a listener of
:php:`ModifyProgramDataEvent` replaces such a subclass.

..  warning::

    A demand listener widens as easily as it narrows. The demand *is* the
    query, so :php:`setShowHiddenRecords(true)` shows hidden program pages to
    every visitor, :php:`setPages([])` drops the storage restriction the editor
    chose, and :php:`setSorting()` overrides the editor's ordering. What a
    listener cannot undo is the page type, the enable fields other than
    :php:`disabled` and the :php:`uid` tiebreaker of the ordering. A result
    handed to :php:`setPrograms()` is rendered in the order it carries, and a
    demand built from scratch rather than mutated drops the editor's settings
    and the category filter the visitor submitted.

..  index:: Frontend, PHP-API, ext:academic_programs
