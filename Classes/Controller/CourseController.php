<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Controller;

use FGTCLB\EducationalCourse\Collection\CourseCollection;
use FGTCLB\EducationalCourse\Domain\Repository\CategoryRepository;
use FGTCLB\EducationalCourse\Factory\CourseDemandFactory;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class CourseController extends ActionController
{
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected CourseDemandFactory $courseDemandFactory
    ) {}

    public function listAction(array $demand = null): ResponseInterface
    {
        $demandObject = $this->courseDemandFactory->createDemandObject(
            $demand,
            $this->settings,
            $this->configurationManager->getContentObject()->data['uid'] ?? null
        );

        $courses = CourseCollection::getByDemand(
            $demandObject,
            GeneralUtility::intExplode(
                ',',
                $this->configurationManager->getContentObject()
                    ? $this->configurationManager->getContentObject()->data['pages']
                    : []
            )
        );

        $this->view->assignMultiple([
            'courses' => $courses,
            'demand' => $demandObject,
            'categories' => $this->categoryRepository->findAllWitDisabledStatus($courses->getApplicableCategories()) ?? [],
        ]);

        return $this->htmlResponse();
    }
}
