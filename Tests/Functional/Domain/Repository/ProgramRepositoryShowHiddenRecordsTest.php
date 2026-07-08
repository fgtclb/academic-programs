<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Domain\Model\Program;
use FGTCLB\AcademicPrograms\Domain\Repository\ProgramRepository;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

final class ProgramRepositoryShowHiddenRecordsTest extends AbstractAcademicProgramsTestCase
{
    private function getProgramRepository(): ProgramRepository
    {
        return $this->get(ProgramRepository::class);
    }

    private function createDemand(bool $showHiddenRecords): ProgramDemand
    {
        $demand = new ProgramDemand();
        $demand->setShowHiddenRecords($showHiddenRecords);
        return $demand;
    }

    /**
     * @param QueryResult<Program> $result
     * @return int[]
     */
    private function resultUids(QueryResult $result): array
    {
        $uids = [];
        foreach ($result as $program) {
            $uids[] = (int)$program->getUid();
        }
        sort($uids);
        return $uids;
    }

    #[Test]
    public function findByDemandExcludesHiddenRecordsByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProgramRepositoryShowHidden/programs.csv');
        $result = $this->getProgramRepository()->findByDemand($this->createDemand(false));
        $this->assertSame([1, 3], $this->resultUids($result));
    }

    #[Test]
    public function findByDemandIncludesHiddenRecordsWhenRequested(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProgramRepositoryShowHidden/programs.csv');
        $result = $this->getProgramRepository()->findByDemand($this->createDemand(true));
        $this->assertSame([1, 2, 3, 4], $this->resultUids($result));
    }
}
