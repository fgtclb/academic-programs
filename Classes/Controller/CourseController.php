<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\EducationalCourse\Domain\Collection\CourseCollection;
use FGTCLB\EducationalCourse\Domain\Model\Dto\CourseFilter;
use FGTCLB\EducationalCourse\Domain\Repository\CourseCategoryRepository;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class CourseController extends ActionController
{
    public function __construct(
        protected readonly CourseCategoryRepository $categoryRepository
    ) {
    }

    /**
     * @throws Exception
     * @throws DBALException
     * @throws CategoryExistException
     */
    public function listAction(?CourseFilter $filter = null): ResponseInterface
    {
        if ($filter === null) {
            if ((int)$this->settings['categories'] > 0) {
                if ($this->configurationManager->getContentObject() !== null) {
                    $uid = $this->configurationManager->getContentObject()->data['uid'];
                    $filterCategories = $this->categoryRepository->getByDatabaseFields($uid);
                    $filter = CourseFilter::createByCategoryCollection($filterCategories);
                }
            }
        }
        $filter ??= CourseFilter::createEmpty();
        $courses = CourseCollection::getByFilter($filter);
        $categories = $this->categoryRepository->findAll();

        $assignedValues = [
            'courses' => $courses,
            'filter' => $filter,
            'categories' => $categories,
        ];
        $this->view->assignMultiple($assignedValues);

        return $this->htmlResponse();
    }
}
