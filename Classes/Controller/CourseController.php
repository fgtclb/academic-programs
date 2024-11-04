<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Controller;

use FGTCLB\AcademicPrograms\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicPrograms\Domain\Repository\CourseRepository;
use FGTCLB\AcademicPrograms\Factory\DemandFactory;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class CourseController extends ActionController
{
    public function __construct(
        protected CourseRepository $courseRepository,
        protected CategoryRepository $categoryRepository,
        protected DemandFactory $courseDemandFactory
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

        $demandObject = $this->courseDemandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

        $courses = $this->courseRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable($courses);

        $this->view->assignMultiple([
            'courses' => $courses,
            'data' => $contentElementData,
            'demand' => $demandObject,
            'categories' => $categories,
        ]);

        return $this->htmlResponse();
    }
}
