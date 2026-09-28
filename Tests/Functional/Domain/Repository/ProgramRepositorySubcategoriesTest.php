<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Domain\Model\Program;
use FGTCLB\AcademicPrograms\Domain\Repository\ProgramRepository;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * `ProgramRepository::findByDemand()` with a demand that includes subcategories: each
 * selected category matches its whole visible subtree, and several selections still all
 * have to match.
 *
 * Degrees: Bachelor (1) with Bachelor of Science (3) below it and Bachelor of Science with
 * Honours (4) below that, the hidden Bachelor of Engineering (9) below Bachelor, and Master
 * (2) with Master of Science (8). Locations: Berlin (6) and Potsdam (7). Every program
 * carries one degree and one location:
 *
 * - Applied Physics: Bachelor of Science, Berlin
 * - Materials Science: Bachelor of Science with Honours, Potsdam
 * - Business Studies: Bachelor itself, Potsdam
 * - Molecular Chemistry: Master of Science, Berlin
 * - Electrical Engineering: the hidden Bachelor of Engineering, Berlin
 */
final class ProgramRepositorySubcategoriesTest extends AbstractAcademicProgramsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProgramRepositorySubcategories/programs.csv');
    }

    #[Test]
    public function withoutTheOptionASelectedCategoryMatchesOnlyItself(): void
    {
        $this->assertSame(['Business Studies'], $this->findTitles(false, 1));
    }

    #[Test]
    public function withTheOptionASelectedCategoryMatchesItsWholeSubtree(): void
    {
        $this->assertSame(
            ['Applied Physics', 'Business Studies', 'Materials Science'],
            $this->findTitles(true, 1),
        );
    }

    #[Test]
    public function withTheOptionASubcategoryMatchesItsOwnSubtreeOnly(): void
    {
        $this->assertSame(['Applied Physics', 'Materials Science'], $this->findTitles(true, 3));
    }

    #[Test]
    public function withTheOptionASiblingDoesNotMatch(): void
    {
        $this->assertSame(['Molecular Chemistry'], $this->findTitles(true, 2));
    }

    /**
     * Each selection is widened on its own: Bachelor and Berlin together is a Bachelor
     * degree in Berlin, not a Bachelor degree or anything in Berlin.
     */
    #[Test]
    public function withTheOptionSeveralSelectionsStillAllHaveToMatch(): void
    {
        $this->assertSame(['Applied Physics'], $this->findTitles(true, 1, 6));
    }

    #[Test]
    public function withTheOptionACategoryWithoutSubcategoriesMatchesOnlyItself(): void
    {
        $this->assertSame(
            ['Applied Physics', 'Electrical Engineering', 'Molecular Chemistry'],
            $this->findTitles(true, 6),
        );
    }

    #[Test]
    public function withTheOptionAndNoSelectionEveryProgramIsFound(): void
    {
        $this->assertCount(5, $this->findTitles(true));
    }

    /**
     * @return list<string>
     */
    private function findTitles(bool $includeSubcategories, int ...$categoryUids): array
    {
        $demand = new ProgramDemand();
        $demand->setIncludeSubcategories($includeSubcategories);
        if ($categoryUids !== []) {
            $demand->setFilterCollection(new FilterCollection(
                $this->get(CategoryRepository::class)->findByGroupAndUidList('programs', $categoryUids),
            ));
        }

        /** @var QueryResult<Program> $result */
        $result = $this->get(ProgramRepository::class)->findByDemand($demand);
        $titles = [];
        foreach ($result as $program) {
            $titles[] = $program->getTitle();
        }
        sort($titles);

        return $titles;
    }
}
