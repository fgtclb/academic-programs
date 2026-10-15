..  _developers-event-modify-program-data-event:
..  _developers-program-data-event:

======================
ModifyProgramDataEvent
======================

:php:`\FGTCLB\AcademicPrograms\Event\ModifyProgramDataEvent` hands a listener
the data of a program page before the page renders. The data a listener hands
back is what the page template receives as :html:`{program}`, and the facts of
the page show it.

When it is dispatched
=====================

The data processor of the program page, :typoscript:`program-data`
(:php:`\FGTCLB\AcademicPrograms\DataProcessing\ProgramDataProcessor`),
dispatches the event once per rendering of a program page, after it built the
data of the page from the page record, and before it builds the facts of the
page from that data. The processor is part of the page object of the page type
:guilabel:`Academic program`, which every set of this extension and the static
template of the shared block deliver, see :ref:`program-page-content`.

The event belongs to the page, not to a plugin. The
:guilabel:`Course Data` content element reads the program model instead, and
:ref:`developers-event-modify-plugin-view-event` is the way to change what it
renders. A site package whose page object does not call the data processor
dispatches no event either.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Method
        -   What it does
    *   -   :php:`getProgram()`
        -   The data of the page,
            :php:`\FGTCLB\AcademicPrograms\Domain\Model\ProgramData`: the uid,
            the pid, the doktype, the title, the subtitle, the abstract, the
            credit points, the job profile, the performance scope, the
            prerequisites and the application link with its label, each with a
            setter.
    *   -   :php:`setProgram()`
        -   Replaces the data, with a :php:`ProgramData` or a subclass of it.
    *   -   :php:`getPageRecord()`
        -   The record of the program page the data was built from, overlaid
            for the language of the page, for reading.
    *   -   :php:`getRequest()`
        -   The request of the page.

A listener may change the data it is handed, or hand back an object of its own.
A subclass of :php:`ProgramData` carries whatever a project template needs
beyond the fields the extension maps. The categories are looked up by the uid
of the data whenever they are read, so a listener that keeps the uid keeps the
categories of the page, and the data offers no setter for them.

Example
=======

A site shows a large hero image on its program pages, from a file field the
site package adds to the page properties. :php:`ProgramDataWithHeroImage` is a
subclass of :php:`ProgramData` in the site package, with a hero image property
and a named constructor that copies the fields of the data it is given:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddProgramHeroImage.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPrograms\Event\ModifyProgramDataEvent;
    use MyVendor\MyExtension\Domain\Model\ProgramDataWithHeroImage;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Resource\FileRepository;

    final readonly class AddProgramHeroImage
    {
        public function __construct(
            private FileRepository $fileRepository,
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

The page template of the site package renders :html:`{program.heroImage}`.

Rules and pitfalls
==================

*   The facts are built from the data the listener hands back. A listener that
    changes the credit points or a text field changes the facts of the page as
    well, and the site setting :typoscript:`plugin.tx_academicprograms.facts.fields`
    still decides which of them are shown.
*   The data reaches the program page only. The cards of the
    :guilabel:`Course List` and the :guilabel:`Course Data` element read the
    program model, which this event does not touch.
*   :php:`ProgramData` is shared by every listener. A listener that changes the
    object it was handed changes it for the listeners after it, and the last
    one to call :php:`setProgram()` decides which object the page receives.

Introduced in
=============

*   :ref:`feature-1790604743` added the event in 3.0.
