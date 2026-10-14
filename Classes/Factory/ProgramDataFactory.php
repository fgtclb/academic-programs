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
     * @return ProgramData
     */
    public function get(array $properties): ProgramData
    {
        $program = GeneralUtility::makeInstance(ProgramData::class);
        // Read with a fallback: the data processor may be attached to a record that is no
        // page, and code updated before the database compare meets a page record without
        // the columns of this extension.
        $program->setPid((int)($properties['pid'] ?? 0));
        $program->setUid((int)($properties['uid'] ?? 0));
        $program->setDoktype((int)($properties['doktype'] ?? 0));
        $program->setTitle((string)($properties['title'] ?? ''));
        $program->setSubtitle((string)($properties['subtitle'] ?? ''));
        $program->setAbstract((string)($properties['abstract'] ?? ''));
        $program->setCreditPoints((int)($properties['credit_points'] ?? 0));
        $program->setJobProfile((string)($properties['job_profile'] ?? ''));
        $program->setPerformanceScope((string)($properties['performance_scope'] ?? ''));
        $program->setPrerequisites((string)($properties['prerequisites'] ?? ''));
        $program->setApplicationLink((string)($properties['application_link'] ?? ''));
        $program->setApplicationLinkLabel((string)($properties['application_link_label'] ?? ''));

        return $program;
    }
}
