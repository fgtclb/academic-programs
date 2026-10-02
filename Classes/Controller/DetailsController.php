<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Controller;

use FGTCLB\AcademicBase\Controller\DispatchModifyPluginViewEventMethodTrait;
use FGTCLB\AcademicBase\Controller\GetCurrentContentRecordMethodTrait;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicPrograms\Domain\Model\Program;
use FGTCLB\AcademicPrograms\Domain\Repository\ProgramRepository;
use FGTCLB\AcademicPrograms\Enumeration\ProgramFactsPlace;
use FGTCLB\AcademicPrograms\Service\ProgramFactsBuilder;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class DetailsController extends ActionController
{
    use DispatchModifyPluginViewEventMethodTrait;
    use GetCurrentContentRecordMethodTrait;

    public function __construct(
        private readonly ProgramRepository $programRepository,
        private readonly ProgramFactsBuilder $programFactsBuilder,
    ) {}

    /**
     * @return ResponseInterface
     */
    public function showAction(): ResponseInterface
    {
        $context = new PluginControllerActionContext($this->request, $this->settings);
        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $program = $this->programRepository->findByUid((int)($contentElementData['pid'] ?? 0));

        $facts = $program instanceof Program
            ? $this->programFactsBuilder->build(
                $program,
                $this->factsFields(),
                ProgramFactsPlace::Details,
                $this->mostSpecificCategoriesOnly(),
            )
            : [];

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'program' => $program,
            'facts' => $facts,
        ]);
        $this->dispatchModifyPluginViewEvent($context, $this->view, $this->eventDispatcher);

        return $this->htmlResponse();
    }

    /**
     * The field list of "plugin.tx_academicprograms.settings.facts.fields".
     */
    private function factsFields(): string
    {
        $facts = $this->settings['facts'] ?? [];
        return is_array($facts) && is_string($facts['fields'] ?? null) ? $facts['fields'] : '';
    }

    /**
     * The switch "plugin.tx_academicprograms.settings.facts.mostSpecificOnly".
     */
    private function mostSpecificCategoriesOnly(): bool
    {
        $facts = $this->settings['facts'] ?? [];
        return is_array($facts) && (bool)($facts['mostSpecificOnly'] ?? false);
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
