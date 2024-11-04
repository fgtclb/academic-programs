<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Factory;

use FGTCLB\AcademicPrograms\Domain\Model\Course;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Factory class for programs
 */
class CourseFactory
{
    /**
     * @param array<int|string, mixed> $properties page properties of the current page
     * @return Course
     */
    public function get(array $properties): Course
    {
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        $programModels = $dataMapper->map(Course::class, [$properties]);
        return $programModels[0];
    }
}
