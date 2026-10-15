<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Unit\Factory;

use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Factory\DemandFactory;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Filter\CategoryFilterNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The sorting a program list starts with, before a visitor chose one, comes from
 * `settings.sorting`. The backend select stores a field and a direction, but the setting
 * also arrives through an import, a hand-written FlexForm or TypoScript for an element
 * that stores no sorting, and then may be anything.
 */
final class DemandFactorySortingTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function completeSortingDataProvider(): \Generator
    {
        yield 'title ascending' => ['title asc', 'title asc'];
        yield 'title descending' => ['title desc', 'title desc'];
        yield 'last updated descending' => ['lastUpdated desc', 'lastUpdated desc'];
        yield 'page sorting descending' => ['sorting desc', 'sorting desc'];
        yield 'surrounded and separated by extra spaces' => ['  lastUpdated   desc ', 'lastUpdated desc'];
    }

    #[DataProvider('completeSortingDataProvider')]
    #[Test]
    public function aFieldAndADirectionAreApplied(string $sorting, string $expected): void
    {
        $this->assertSame($expected, $this->createDemand(['sorting' => $sorting])->getSorting());
    }

    /**
     * Everything that is not a field and a direction keeps the default of the demand,
     * title ascending, instead of raising "Undefined array key 1" or a `TypeError`.
     *
     * @return \Generator<string, array{0: mixed}>
     */
    public static function incompleteSortingDataProvider(): \Generator
    {
        yield 'empty' => [''];
        yield 'spaces only' => ['   '];
        yield 'a field only' => ['lastUpdated'];
        yield 'three words' => ['lastUpdated desc title'];
        yield 'not a string' => [['lastUpdated', 'desc']];
    }

    #[DataProvider('incompleteSortingDataProvider')]
    #[Test]
    public function anIncompleteSortingKeepsTheDefault(mixed $sorting): void
    {
        $this->assertSame('title asc', $this->createDemand(['sorting' => $sorting])->getSorting());
    }

    #[Test]
    public function noSortingSettingKeepsTheDefault(): void
    {
        $this->assertSame('title asc', $this->createDemand([])->getSorting());
    }

    /**
     * A field and a direction are applied one after the other, each only when the demand
     * knows the combination, the same as the sorting field and direction of a request. An
     * unknown part is therefore dropped while a known one still applies.
     *
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function unknownPartDataProvider(): \Generator
    {
        yield 'an unknown direction' => ['lastUpdated up', 'lastUpdated asc'];
        yield 'an unknown field' => ['uid desc', 'title desc'];
        yield 'an unknown field and direction' => ['uid up', 'title asc'];
    }

    #[DataProvider('unknownPartDataProvider')]
    #[Test]
    public function anUnknownPartIsDropped(string $sorting, string $expected): void
    {
        $this->assertSame($expected, $this->createDemand(['sorting' => $sorting])->getSorting());
    }

    /**
     * A request with a sorting of its own never reads the setting, so a broken one does
     * not stop a visitor from sorting the list either.
     */
    #[Test]
    public function aRequestSortingIgnoresTheSetting(): void
    {
        $demand = $this->createDemand(['sorting' => 'title'], ['sortingField' => 'lastUpdated', 'sortingDirection' => 'desc']);

        $this->assertSame('lastUpdated desc', $demand->getSorting());
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed>|null $demandFromRequest
     */
    private function createDemand(array $settings, ?array $demandFromRequest = null): ProgramDemand
    {
        $subject = new DemandFactory(
            $this->createStub(CategoryRepository::class),
            $this->createStub(CategoryFilterNormalizer::class),
        );

        return $subject->createDemandObject($demandFromRequest, $settings, ['uid' => 1]);
    }
}
