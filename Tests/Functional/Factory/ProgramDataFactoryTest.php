<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Factory;

use FGTCLB\AcademicPrograms\Domain\Model\ProgramData;
use FGTCLB\AcademicPrograms\Factory\ProgramDataFactory;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * `ProgramDataFactory::get()` is the only thing standing between the raw `pages` row and
 * the `{program}` variable of a program page template - `ProgramDataProcessor` passes
 * `$processedData['data']` straight into it.
 *
 * It is a mapper, so a unit test can cover the assignments; what it cannot cover is the
 * two things the row actually brings with it. First, the value types: a database row is
 * strings on some drivers and integers on others, and every getter of `ProgramData` is
 * typed, so the casts in the factory are the only reason the model can be built at all.
 * Second, the columns of this extension are nullable (`credit_points`, `job_profile`,
 * `performance_scope`, `prerequisites`), so an untouched program page carries `NULL`
 * rather than an empty string. Both are asserted here against rows read back from the
 * database, not against a hand written array.
 *
 * The categories are part of it because `setUid()` is the only reason the uid is mapped:
 * `ProgramData::getCategories()` resolves them from it at render time. A factory writing
 * the pid there would produce a model that looks right and lists the wrong categories.
 */
final class ProgramDataFactoryTest extends AbstractAcademicProgramsTestCase
{
    private const PROGRAM_PAGE = 1;
    private const UNTOUCHED_PROGRAM_PAGE = 2;
    private const REGULAR_PAGE = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProgramDataFactory/programPages.csv');
    }

    #[Test]
    public function everyProgramPropertyIsTakenFromThePageRecord(): void
    {
        $program = $this->programOfPage(self::PROGRAM_PAGE);

        $this->assertSame(1, $program->getUid());
        $this->assertSame(5, $program->getPid());
        $this->assertSame(20, $program->getDoktype());
        $this->assertSame('Applied Physics', $program->getTitle());
        $this->assertSame('Bachelor of Science', $program->getSubtitle());
        $this->assertSame('Optics, photonics and applied quantum mechanics.', $program->getAbstract());
        $this->assertSame(180, $program->getCreditPoints());
        $this->assertSame('<p>Research and development in industry.</p>', $program->getJobProfile());
        $this->assertSame('<p>Six semesters, 30 credit points each.</p>', $program->getPerformanceScope());
        $this->assertSame('<p>General qualification for university entrance.</p>', $program->getPrerequisites());
    }

    /**
     * The four columns this extension adds to `pages` are nullable, so a program page
     * nobody filled in hands the factory `NULL` for each of them. Without the casts the
     * typed setters of `ProgramData` would raise a `TypeError` on the first program page
     * created in the backend.
     */
    #[Test]
    public function nullColumnsBecomeAnEmptyStringOrZero(): void
    {
        $program = $this->programOfPage(self::UNTOUCHED_PROGRAM_PAGE);

        $this->assertSame(0, $program->getCreditPoints());
        $this->assertSame('', $program->getJobProfile());
        $this->assertSame('', $program->getPerformanceScope());
        $this->assertSame('', $program->getPrerequisites());
        $this->assertSame('Untouched Program', $program->getTitle());
    }

    /**
     * A `TypoScript` misconfiguration pointing the data processor at an ordinary page is
     * not rejected: the factory maps whatever row it is given and reports the doktype it
     * found. A template can therefore branch on `{program.doktype}`, but it will not be
     * told by an exception.
     */
    #[Test]
    public function aPageOfAnotherDoktypeIsMappedJustTheSame(): void
    {
        $program = $this->programOfPage(self::REGULAR_PAGE);

        $this->assertSame(3, $program->getUid());
        $this->assertSame(1, $program->getDoktype());
        $this->assertSame('Regular Page', $program->getTitle());
    }

    /**
     * `ProgramData::getCategories()` looks the categories up by the uid the factory set,
     * which is what makes the mapped uid load bearing rather than decorative.
     */
    #[Test]
    public function theCategoriesOfTheProgramAreResolvedFromTheMappedUid(): void
    {
        $categories = $this->programOfPage(self::PROGRAM_PAGE)->getCategories();

        $this->assertNotNull($categories);
        $titles = [];
        foreach ($categories as $category) {
            $titles[] = $category->getTitle();
        }
        sort($titles);

        $this->assertSame(['Restricted admission', 'Winter term'], $titles);
    }

    /**
     * A program page without categories still renders, so the collection is empty rather
     * than `null` - a template iterating over `{program.categories}` must not have to
     * guard against it.
     */
    #[Test]
    public function aProgramWithoutCategoriesYieldsAnEmptyCollection(): void
    {
        $categories = $this->programOfPage(self::UNTOUCHED_PROGRAM_PAGE)->getCategories();

        $this->assertNotNull($categories);
        $this->assertCount(0, $categories);
    }

    /**
     * The model is built with `GeneralUtility::makeInstance()`, which returns a shared
     * instance for a `SingletonInterface`. `ProgramData` is not one - and must not become
     * one, because a second `get()` call would then overwrite the first program.
     */
    #[Test]
    public function everyCallReturnsItsOwnModel(): void
    {
        $first = $this->programOfPage(self::PROGRAM_PAGE);
        $second = $this->programOfPage(self::UNTOUCHED_PROGRAM_PAGE);

        $this->assertNotSame($first, $second);
        $this->assertSame('Applied Physics', $first->getTitle());
        $this->assertSame('Untouched Program', $second->getTitle());
    }

    private function programOfPage(int $pageId): ProgramData
    {
        // Obtained the way `ProgramDataProcessor` obtains it - the factory is not a
        // public service, so `$this->get()` cannot be used here.
        return GeneralUtility::makeInstance(ProgramDataFactory::class)->get($this->pageRecord($pageId));
    }

    /**
     * @return array<string, mixed>
     */
    private function pageRecord(int $pageId): array
    {
        $record = $this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->select(['*'], 'pages', ['uid' => $pageId])
            ->fetchAssociative();

        $this->assertIsArray($record, sprintf('Page %d is missing in the fixture.', $pageId));

        return $record;
    }
}
