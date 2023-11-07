<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Controller;

use FGTCLB\EducationalCourse\Domain\Collection\CourseCollection;
use FGTCLB\EducationalCourse\Domain\Repository\CourseCategoryRepository;
use FGTCLB\EducationalCourse\Factory\FilterDemandFactory;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class CourseController extends ActionController
{
    public function __construct(
        protected CourseCategoryRepository $categoryRepository,
        protected FilterDemandFactory $filterDemandFactory
    ) {}

    public function listAction(array $filter = null): ResponseInterface
    {
        $demandSettings = [
            'settings' => $this->settings,
            'filters' => $filter,
        ];

        $filterDemand = $this->filterDemandFactory->createDemandObject($demandSettings);

        $courses = CourseCollection::getByFilter(
            $filterDemand->getFilterCollection(),
            GeneralUtility::intExplode(
                ',',
                $this->configurationManager->getContentObject()
                    ? $this->configurationManager->getContentObject()->data['pages']
                    : []
            ),
            $filterDemand->getSortingField() . ' ' . $filterDemand->getSorting()
        );

        $this->view->assignMultiple([
            'courses' => $courses,
            'filter' => $filterDemand->getFilterCollection(),
            'categories' => $this->categoryRepository->findAll() ?? [],
        ]);

        return $this->htmlResponse();
    }
}
