<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Model;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;

/**
 * What the facts of a program are built from: its categories and the four program fields
 * that are facts of their own. Implemented by the Extbase model of the list and details
 * content elements and by the data object of the program page, so both feed the same
 * {@see \FGTCLB\AcademicPrograms\Service\ProgramFactsBuilder}.
 */
interface ProgramFactsSourceInterface
{
    public function getCategoryCollection(): CategoryCollection;

    public function getCreditPoints(): int;

    public function getJobProfile(): string;

    public function getPerformanceScope(): string;

    public function getPrerequisites(): string;
}
