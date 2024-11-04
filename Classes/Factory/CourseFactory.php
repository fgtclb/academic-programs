<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Factory;

use FGTCLB\AcademicPrograms\Domain\Model\Program;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Factory class for programs
 */
class ProgramFactory
{
    /**
     * @param array<int|string, mixed> $properties page properties of the current page
     * @return Program
     */
    public function get(array $properties): Program
    {
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        $programModels = $dataMapper->map(Program::class, [$properties]);
        return $programModels[0];
    }
}
