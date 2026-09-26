<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Controller;

use FGTCLB\AcademicBase\Controller\GetCurrentContentRecordMethodTrait;
use FGTCLB\AcademicPrograms\Domain\Model\Program;
use FGTCLB\AcademicPrograms\Domain\Repository\ProgramRepository;
use FGTCLB\AcademicPrograms\Enumeration\ProgramFactsPlace;
use FGTCLB\AcademicPrograms\Factory\DemandFactory;
use FGTCLB\AcademicPrograms\Service\ProgramFactsBuilder;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class DetailsController extends ActionController
{
    use GetCurrentContentRecordMethodTrait;

    private ProgramFactsBuilder $programFactsBuilder;

    public function __construct(
        protected ProgramRepository $programRepository,
        protected DemandFactory $programDemandFactory
    ) {}

    /**
     * @return ResponseInterface
     */
    public function showAction(): ResponseInterface
    {
        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $program = $this->programRepository->findByUid((int)($contentElementData['pid'] ?? 0));

        $facts = $program instanceof Program
            ? $this->programFactsBuilder->build($program, $this->factsFields(), ProgramFactsPlace::Details)
            : [];

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'program' => $program,
            'facts' => $facts,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Method injection keeps the constructor, which project subclasses call, unchanged.
     * Final, and named after what it is for, so a subclass cannot collide with it.
     */
    final public function injectProgramFactsBuilder(ProgramFactsBuilder $programFactsBuilder): void
    {
        $this->programFactsBuilder = $programFactsBuilder;
    }

    /**
     * The field list of "plugin.tx_academicprograms.settings.facts.fields".
     */
    private function factsFields(): string
    {
        $facts = $this->settings['facts'] ?? [];
        return is_array($facts) && is_string($facts['fields'] ?? null) ? $facts['fields'] : '';
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
