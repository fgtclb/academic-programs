<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Controller;

use FGTCLB\EducationalCourse\Domain\Repository\CourseRepository;
use FGTCLB\EducationalCourse\Domain\Repository\CategoryRepository;
use FGTCLB\EducationalCourse\Factory\CourseDemandFactory;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class CourseController extends ActionController
{
    public function __construct(
        protected CourseRepository $courseRepository,
        protected CategoryRepository $categoryRepository,
        protected CourseDemandFactory $courseDemandFactory
    ) {}

    /**
     * @param array<string, mixed>|null $demand
     * @return ResponseInterface
     */
    public function listAction(array $demand = null): ResponseInterface
    {
        $demandObject = $this->courseDemandFactory->createDemandObject(
            $demand,
            $this->settings,
            $this->configurationManager->getContentObject()->data['uid'] ?? null
        );

        $courses = $this->courseRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable($courses);

        $this->view->assignMultiple([
            'courses' => $courses,
            'data' => $this->configurationManager->getContentObject()->data ?? [],
            'demand' => $demandObject,
            'categories' => $categories,
        ]);

        return $this->htmlResponse();
    }
}
