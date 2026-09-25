<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Unit\Service;

use FGTCLB\AcademicPrograms\Domain\Model\ProgramFact;
use FGTCLB\AcademicPrograms\Domain\Model\ProgramFactsSourceInterface;
use FGTCLB\AcademicPrograms\Enumeration\ProgramFactsPlace;
use FGTCLB\AcademicPrograms\Service\ProgramFactsBuilder;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Domain\Model\Category;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The program carries a degree, a standard period and a location, in the category type
 * order degree - department - standard period - location of the collection, where the
 * department has no category. The collection is a stub, so the order is the one given here
 * and the builder must not reorder it.
 */
final class ProgramFactsBuilderTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: ProgramFactsPlace, 2: list<string>}>
     */
    public static function factsDataProvider(): \Generator
    {
        yield 'configured order' => ['creditPoints,degree', ProgramFactsPlace::Page, ['creditPoints', 'degree']];
        yield 'configured order against the type order' => ['location,degree', ProgramFactsPlace::Details, ['location', 'degree']];
        yield 'configured order on a card' => ['standard_period,degree', ProgramFactsPlace::Card, ['standard_period', 'degree']];
        yield 'blanks trimmed, unknown and repeated items skipped' => [' location , nonsense,title,location, prerequisites ', ProgramFactsPlace::Page, ['location', 'prerequisites']];
        yield 'type without category skipped' => ['department,degree', ProgramFactsPlace::Page, ['degree']];
        yield 'empty text skipped' => ['performanceScope,jobProfile', ProgramFactsPlace::Page, ['jobProfile']];
        yield 'empty list on the page' => ['', ProgramFactsPlace::Page, ['degree', 'standard_period', 'location', 'creditPoints', 'jobProfile', 'prerequisites']];
        yield 'empty list in the details element' => ['', ProgramFactsPlace::Details, ['degree', 'standard_period', 'location']];
        yield 'empty list on the card' => ['', ProgramFactsPlace::Card, ['degree']];
        yield 'list of blanks on the card' => [' , ', ProgramFactsPlace::Card, ['degree']];
    }

    /**
     * @param list<string> $expected
     */
    #[Test]
    #[DataProvider('factsDataProvider')]
    public function buildsTheListedFactsInOrder(string $fields, ProgramFactsPlace $place, array $expected): void
    {
        $facts = (new ProgramFactsBuilder())->build($this->program(creditPoints: 180), $fields, $place);

        $this->assertSame($expected, array_map(static fn(ProgramFact $fact): string => $fact->identifier, $facts));
    }

    /**
     * The types come in the order of the collection, whatever it is: a builder with a
     * type order of its own fails here.
     */
    #[Test]
    public function emptyListFollowsTheTypeOrderOfTheCollection(): void
    {
        $categoriesByType = [
            'location' => [3 => new Category(3, 0, 'Campus A')],
            'degree' => [1 => new Category(1, 0, 'Bachelor of Science')],
        ];

        $expected = [
            'page' => ['location', 'degree', 'jobProfile', 'prerequisites'],
            'details' => ['location', 'degree'],
        ];
        foreach ([ProgramFactsPlace::Page, ProgramFactsPlace::Details] as $place) {
            $facts = (new ProgramFactsBuilder())->build($this->program(creditPoints: 0, categoriesByType: $categoriesByType), '', $place);
            $this->assertSame($expected[$place->value], array_map(static fn(ProgramFact $fact): string => $fact->identifier, $facts));
        }
    }

    #[Test]
    public function creditPointsOfZeroAreNoFact(): void
    {
        $facts = (new ProgramFactsBuilder())->build($this->program(creditPoints: 0), 'creditPoints,degree', ProgramFactsPlace::Page);

        $this->assertSame(['degree'], array_map(static fn(ProgramFact $fact): string => $fact->identifier, $facts));
    }

    #[Test]
    public function categoryTypeFactCarriesItsCategoriesLabelAndIcon(): void
    {
        $facts = (new ProgramFactsBuilder())->build($this->program(creditPoints: 180), 'location', ProgramFactsPlace::Page);

        $this->assertCount(1, $facts);
        $this->assertTrue($facts[0]->isCategoryType);
        $this->assertSame('sys_category.programs.location', $facts[0]->labelKey);
        $this->assertSame('category_types.programs.location', $facts[0]->iconIdentifier);
        $this->assertSame(['Campus A', 'Campus B'], array_map(static fn(Category $category): string => $category->getTitle(), $facts[0]->categories));
        $this->assertSame('', $facts[0]->value);
    }

    #[Test]
    public function creditPointsFactCarriesItsValueLabelAndIcon(): void
    {
        $facts = (new ProgramFactsBuilder())->build($this->program(creditPoints: 180), 'creditPoints', ProgramFactsPlace::Page);

        $this->assertCount(1, $facts);
        $this->assertFalse($facts[0]->isCategoryType);
        $this->assertSame('program.creditPoints', $facts[0]->labelKey);
        $this->assertSame('tx-academicprograms-info-credit-points', $facts[0]->iconIdentifier);
        $this->assertSame('180', $facts[0]->value);
        $this->assertSame([], $facts[0]->categories);
    }

    #[Test]
    public function textFactCarriesItsValueWithoutIcon(): void
    {
        $facts = (new ProgramFactsBuilder())->build($this->program(creditPoints: 180), 'jobProfile', ProgramFactsPlace::Page);

        $this->assertCount(1, $facts);
        $this->assertSame('program.jobProfile', $facts[0]->labelKey);
        $this->assertSame('', $facts[0]->iconIdentifier);
        $this->assertSame('<p>Research and development.</p>', $facts[0]->value);
    }

    /**
     * @param array<string, array<int, Category>>|null $categoriesByType
     */
    private function program(int $creditPoints, ?array $categoriesByType = null): ProgramFactsSourceInterface
    {
        $collection = $this->createStub(CategoryCollection::class);
        $collection->method('getAllCategoriesByType')->willReturn($categoriesByType ?? [
            'degree' => [1 => new Category(1, 0, 'Bachelor of Science')],
            'department' => [],
            'standard_period' => [2 => new Category(2, 0, '6 semesters')],
            'location' => [3 => new Category(3, 0, 'Campus A'), 4 => new Category(4, 0, 'Campus B')],
        ]);

        return new class ($collection, $creditPoints) implements ProgramFactsSourceInterface {
            public function __construct(
                private readonly CategoryCollection $collection,
                private readonly int $creditPoints,
            ) {}

            public function getCategoryCollection(): CategoryCollection
            {
                return $this->collection;
            }

            public function getCreditPoints(): int
            {
                return $this->creditPoints;
            }

            public function getJobProfile(): string
            {
                return '<p>Research and development.</p>';
            }

            public function getPerformanceScope(): string
            {
                return '';
            }

            public function getPrerequisites(): string
            {
                return '<p>General qualification for university entrance.</p>';
            }
        };
    }
}
