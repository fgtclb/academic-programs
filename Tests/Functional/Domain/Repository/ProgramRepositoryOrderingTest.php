<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Domain\Repository\ProgramRepository;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The `uid` tiebreaker of `ProgramRepository::findByDemand()` (ACE-491).
 *
 * The query orders by the sorting option the plugin demands - and ordered by nothing
 * else, so records equal in that ordering were returned in whatever relative order the
 * DBMS yielded, which is not the same list twice on PostgreSQL.
 */
final class ProgramRepositoryOrderingTest extends AbstractAcademicProgramsTestCase
{
    /**
     * Three programs share one title, so the default `title asc` ordering leaves their
     * relative order undefined and the `uid` tiebreaker settles it. The fixture's
     * `sorting` values are deliberately reversed against uid order, so a `sorting`-based
     * accident cannot produce the expected list. SQLite cannot make the tie itself fail -
     * uid order is its natural order - so the assertion pins the contract for the DBMS
     * where a tie is otherwise resolved arbitrarily.
     */
    #[Test]
    public function programsEqualInTheDemandedOrderingFallBackToUidOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProgramRepositoryOrdering/programsWithEqualTitles.csv');

        $uids = [];
        foreach ($this->get(ProgramRepository::class)->findByDemand(new ProgramDemand()) as $program) {
            $uids[] = (int)$program->getUid();
        }

        $this->assertSame([10, 11, 12], $uids);
    }
}
