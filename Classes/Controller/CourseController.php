<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Controller;

use FGTCLB\EducationalCourse\Domain\Collection\CourseCollection;
use FGTCLB\EducationalCourse\Domain\Model\Dto\CourseFilter;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class CourseController extends ActionController
{
    public function listAction(?CourseFilter $filter = null): ResponseInterface
    {
        $courses = CourseCollection::getByFilter($filter);

        $assignedValues = [
            'courses' => $courses,
            'filter' => $filter,
        ];
        $this->view->assignMultiple($assignedValues);

        return $this->htmlResponse();
    }
}
