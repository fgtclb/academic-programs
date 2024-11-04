<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Controller;

use FGTCLB\AcademicPrograms\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicPrograms\Domain\Repository\ProgramRepository;
use FGTCLB\AcademicPrograms\Factory\DemandFactory;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ProgramController extends ActionController
{
    public function __construct(
        protected ProgramRepository $programRepository,
        protected CategoryRepository $categoryRepository,
        protected DemandFactory $programDemandFactory
    ) {}

    /**
     * @param array<string, mixed>|null $demand
     * @return ResponseInterface
     */
    public function listAction(array $demand = null): ResponseInterface
    {
        /** @var ContentObjectRenderer $contentObjectRenderer */
        $contentObjectRenderer = $this->configurationManager->getContentObject();
        $contentElementData = $contentObjectRenderer->data;

        $demandObject = $this->programDemandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

        $programs = $this->programRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable($programs);

        $this->view->assignMultiple([
            'programs' => $programs,
            'data' => $contentElementData,
            'demand' => $demandObject,
            'categories' => $categories,
        ]);

        return $this->htmlResponse();
    }
}
