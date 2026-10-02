.. _breaking-1791043408:

===========================================
Breaking: The program controllers are final
===========================================

Description
===========

Both controllers of this extension are :php:`final`:

*   :php:`\FGTCLB\AcademicPrograms\Controller\ProgramController` serves the
    program list (`ProgramList`) and the program finder (`ProgramFinder`).
*   :php:`\FGTCLB\AcademicPrograms\Controller\DetailsController` serves the
    program details (`ProgramDetails`).

Their collaborators are private constructor arguments now. The methods
:php:`injectFilterRedirectExtensionService()`,
:php:`injectFilterTypeResolver()` and :php:`injectProgramFactsBuilder()` are
removed, and :php:`redirectFilterSubmission()` and
:php:`pluginControllerActionContext()` of the program controller are private.
All of them existed only so that a subclass could keep calling the old
constructor and the helpers of the shipped actions. The details controller no
longer takes the demand factory, which it never used.

Every plugin controller of the academic extensions is final in 3.0. A plugin is
extended through its events, not through a subclass of its controller.

Impact
======

A class that extends one of the two controllers stops loading with a fatal
error, :php:`Class ... cannot extend final class
FGTCLB\AcademicPrograms\Controller\ProgramController` for instance. That
happens as soon as anything loads the subclass, a plugin registered with it
renders, or the container is built with it.

An XCLASS of either controller fails the same way. The upgrade check
:bash:`academic:upgrade:check` of :guilabel:`EXT:academic_base` reports it as
an error. A :php:`configurePlugin()` call that points one of the three plugins
at a subclass is not reported.

The plugins, their templates and their settings are unchanged. The behaviour
is the same on TYPO3 v13 and v14.

Affected Installations
======================

Installations with a class that extends :php:`ProgramController` or
:php:`DetailsController`, registered for a plugin through
:php:`ExtensionUtility::configurePlugin()`, as an XCLASS, or in the service
container.

Migration
=========

Remove the subclass, and the :php:`configurePlugin()` call or the XCLASS
registration that points at it, so the shipped controller serves the plugin
again. Move each override to its replacement:

*   Changing the selection of the program list or the finder before the query:
    a listener of :php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramDemandEvent`.
    See :ref:`feature-1790604743`.
*   Replacing or reordering the programs, changing the offered filter
    categories, or additional view variables of the list and the finder: a
    listener of :php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramListEvent`.
*   A finder action of the project next to the list: the program finder of this
    extension, see :ref:`feature-1790367619` and
    :ref:`breaking-program-finder-registered-upstream`.
*   Filter selections that can be bookmarked: the list answers a filter
    submission with a redirect to a GET URL, see :ref:`feature-1790226102`.
*   Changing the program or adding view variables of the program details: a
    listener of the plugin view event of :guilabel:`academic_base`, see
    :ref:`feature-plugin-view-event`. :php:`ModifyProgramDataEvent` changes the
    data of the program page, which a data processor renders, not this
    plugin.

A subclass that adds a view variable to the details:

..  code-block:: php
    :caption: Before: EXT:my_extension/Classes/Controller/DetailsController.php

    final class DetailsController extends \FGTCLB\AcademicPrograms\Controller\DetailsController
    {
        public function showAction(): ResponseInterface
        {
            $this->view->assign('applicationPeriods', $this->applicationPeriodRepository->findAll());
            return parent::showAction();
        }
    }

does the same as a listener:

..  code-block:: php
    :caption: After: EXT:my_extension/Classes/EventListener/AddApplicationPeriodsToProgramDetails.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use MyVendor\MyExtension\Domain\Repository\ApplicationPeriodRepository;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class AddApplicationPeriodsToProgramDetails
    {
        public function __construct(
            private ApplicationPeriodRepository $applicationPeriodRepository,
        ) {}

        #[AsEventListener(identifier: 'my-extension/add-application-periods-to-program-details')]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            if ($context->getControllerExtensionName() !== 'AcademicPrograms'
                || $context->getPluginName() !== 'ProgramDetails'
            ) {
                return;
            }
            $event->getView()->assign('applicationPeriods', $this->applicationPeriodRepository->findAll());
        }
    }

The plugin view event is dispatched by every plugin of the academic
extensions, so the listener checks for the one it means.

..  index:: Frontend, PHP-API, NotScanned, ext:academic_programs
