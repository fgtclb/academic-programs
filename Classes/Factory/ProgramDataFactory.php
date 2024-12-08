<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Factory;

use FGTCLB\AcademicPrograms\Domain\Model\ProgramData;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory class for programs
 */
class ProgramDataFactory
{
    /**
     * @param array<int|string, mixed> $properties page properties of the current page
     * @return Program
     */
    public function get(array $properties): ProgramData
    {
        $program = GeneralUtility::makeInstance(ProgramData::class);
        $program->setPid((int)$properties['pid']);
        $program->setUid((int)$properties['uid']);
        $program->setDoktype((int)$properties['doktype']);
        $program->setTitle((string)$properties['title']);
        $program->setSubtitle((string)$properties['subtitle']);
        $program->setAbstract((string)$properties['abstract']);
        $program->setJobProfile((string)$properties['job_profile']);
        $program->setPerformanceScope((string)$properties['performance_scope']);
        $program->setPrerequisites((string)$properties['prerequisites']);

        return $program;
    }
}
