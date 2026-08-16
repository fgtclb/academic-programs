<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Unit\Domain\Model\Dto;

use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Enumeration\SortingOptions;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `ProgramDemand` is assembled from FlexForm settings and request arguments and then
 * handed to `ProgramRepository::findByDemand()`. Everything it accepts reaches a query,
 * so what it rejects matters as much as what it stores.
 */
final class ProgramDemandTest extends UnitTestCase
{
    #[Test]
    public function aFreshDemandSortsByTheDefaultOption(): void
    {
        $subject = new ProgramDemand();

        $this->assertSame(SortingOptions::__default, $subject->getSorting());
        $this->assertSame('title', $subject->getSortingField());
        $this->assertSame('asc', $subject->getSortingDirection());
    }

    #[Test]
    public function aFreshDemandSelectsNoPageAndNoFilter(): void
    {
        $subject = new ProgramDemand();

        $this->assertSame([], $subject->getPages());
        $this->assertNull($subject->getFilterCollection());
        $this->assertFalse($subject->getShowHiddenRecords());
    }

    #[Test]
    #[DataProvider('knownSortingOptions')]
    public function aKnownOptionIsSplitIntoFieldAndDirection(string $option, string $field, string $direction): void
    {
        $subject = new ProgramDemand();
        $subject->setSorting($option);

        $this->assertSame($option, $subject->getSorting());
        $this->assertSame($field, $subject->getSortingField());
        $this->assertSame($direction, $subject->getSortingDirection());
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function knownSortingOptions(): \Generator
    {
        yield 'title ascending' => [SortingOptions::SORT_BY_TITLE_ASC, 'title', 'asc'];
        yield 'title descending' => [SortingOptions::SORT_BY_TITLE_DESC, 'title', 'desc'];
        yield 'last updated ascending' => [SortingOptions::SORT_BY_LASTUPDATED_ASC, 'lastUpdated', 'asc'];
        yield 'last updated descending' => [SortingOptions::SORT_BY_LASTUPDATED_DESC, 'lastUpdated', 'desc'];
        yield 'backend sorting' => [SortingOptions::SORT_BY_SORTING_ASC, 'sorting', 'asc'];
    }

    /**
     * An unknown option leaves the demand as it was rather than clearing the ordering.
     * A stored FlexForm value that refers to a since-renamed option therefore falls back
     * to the previous sorting instead of reaching Extbase as an empty `ORDER BY`.
     */
    #[Test]
    #[DataProvider('unusableSortingValues')]
    public function anUnknownOptionIsIgnored(string $value): void
    {
        $subject = new ProgramDemand();
        $subject->setSorting(SortingOptions::SORT_BY_LASTUPDATED_DESC);

        $subject->setSorting($value);

        $this->assertSame(SortingOptions::SORT_BY_LASTUPDATED_DESC, $subject->getSorting());
        $this->assertSame('lastUpdated', $subject->getSortingField());
        $this->assertSame('desc', $subject->getSortingDirection());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function unusableSortingValues(): \Generator
    {
        yield 'empty' => [''];
        yield 'field without direction' => ['title'];
        yield 'unknown field' => ['uid asc'];
        yield 'unknown direction' => ['title sideways'];
        yield 'wrong case' => ['TITLE ASC'];
        yield 'constant name instead of value' => ['SORT_BY_TITLE_ASC'];
        yield 'sql injected into the ordering' => ['title asc; DROP TABLE pages'];
    }

    /**
     * The direction is kept, so switching the column in a list view does not silently
     * flip the order back to ascending.
     */
    #[Test]
    public function changingTheFieldKeepsTheDirection(): void
    {
        $subject = new ProgramDemand();
        $subject->setSorting(SortingOptions::SORT_BY_TITLE_DESC);

        $subject->setSortingField('lastUpdated');

        $this->assertSame(SortingOptions::SORT_BY_LASTUPDATED_DESC, $subject->getSorting());
        $this->assertSame('lastUpdated', $subject->getSortingField());
        $this->assertSame('desc', $subject->getSortingDirection());
    }

    #[Test]
    public function changingTheDirectionKeepsTheField(): void
    {
        $subject = new ProgramDemand();
        $subject->setSorting(SortingOptions::SORT_BY_TITLE_ASC);

        $subject->setSortingDirection('desc');

        $this->assertSame(SortingOptions::SORT_BY_TITLE_DESC, $subject->getSorting());
        $this->assertSame('title', $subject->getSortingField());
        $this->assertSame('desc', $subject->getSortingDirection());
    }

    /**
     * `sorting` is the one field offered ascending only, so asking for it descending
     * reassembles to an option that does not exist and is dropped. The demand keeps
     * the ordering it had - it does not end up sorting by `sorting asc` either.
     *
     * This is the asymmetry `SortingOptionsTest` points at, and it is what a list
     * plugin offering "backend order, reversed" would silently run into.
     */
    #[Test]
    public function aFieldOfferedInOneDirectionOnlyRejectsTheOther(): void
    {
        $subject = new ProgramDemand();
        $subject->setSorting(SortingOptions::SORT_BY_SORTING_ASC);

        $subject->setSortingDirection('desc');

        $this->assertSame(SortingOptions::SORT_BY_SORTING_ASC, $subject->getSorting());
        $this->assertSame('sorting', $subject->getSortingField());
        $this->assertSame('asc', $subject->getSortingDirection());
    }

    /**
     * There is no option without a direction, so the direction cannot be cleared. Worth
     * pinning: it means `getSortingDirection()` never returns an empty string once the
     * constructor has run.
     */
    #[Test]
    public function theDirectionCannotBeCleared(): void
    {
        $subject = new ProgramDemand();

        $subject->setSortingDirection('');

        $this->assertSame('asc', $subject->getSortingDirection());
        $this->assertSame(SortingOptions::SORT_BY_TITLE_ASC, $subject->getSorting());
    }

    /**
     * The pages list reaches `findByDemand()` as an uid list for an `IN` constraint, and
     * an empty one is valid there since ACE-349 - so the demand must not invent a value.
     */
    #[Test]
    public function thePageListIsStoredAsGiven(): void
    {
        $subject = new ProgramDemand();
        $subject->setPages([12, 34]);

        $this->assertSame([12, 34], $subject->getPages());
    }

    /**
     * The category filter is optional, and clearing it has to be possible - the
     * repository branches on `null` to skip the category join entirely.
     */
    #[Test]
    public function theFilterCollectionCanBeSetAndClearedAgain(): void
    {
        $subject = new ProgramDemand();
        $filterCollection = new FilterCollection();

        $subject->setFilterCollection($filterCollection);
        $this->assertSame($filterCollection, $subject->getFilterCollection());

        $subject->setFilterCollection(null);
        $this->assertNull($subject->getFilterCollection());
    }
}
