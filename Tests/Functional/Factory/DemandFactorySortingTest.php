<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Factory;

use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Factory\DemandFactory;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Which sorting a demand gets: the one the request asks for, and the one of the content
 * element when the request asks for none - no demand at all, or a demand without a sorting,
 * which the program finder and the filter of a list with a hidden sorting select send.
 */
final class DemandFactorySortingTest extends AbstractAcademicProgramsTestCase
{
    /**
     * @return \Generator<string, array{0: array<string, mixed>|null, 1: string}>
     */
    public static function demandDataProvider(): \Generator
    {
        yield 'no demand' => [null, 'lastUpdated asc'];
        yield 'a demand without a sorting' => [['filterCollection' => ['degree' => '']], 'lastUpdated asc'];
        yield 'a demand with a combined sorting' => [['sorting' => 'title desc'], 'title desc'];
        yield 'a demand with field and direction' => [['sortingField' => 'title', 'sortingDirection' => 'desc'], 'title desc'];
        // Half a sorting still is the request's: the field stays the default of the demand.
        yield 'a demand with a direction only' => [['sortingDirection' => 'desc'], 'title desc'];
    }

    /**
     * @param array<string, mixed>|null $demand
     */
    #[DataProvider('demandDataProvider')]
    #[Test]
    public function theSortingOfTheRequestWinsOverTheOneOfTheElement(?array $demand, string $expected): void
    {
        $this->assertSame($expected, $this->createDemand($demand, ['sorting' => 'lastUpdated asc'])->getSorting());
    }

    /**
     * A sorting setting without a direction keeps the default of the demand.
     *
     * @return \Generator<string, array{0: mixed}>
     */
    public static function incompleteSortingDataProvider(): \Generator
    {
        yield 'a field only' => ['lastUpdated'];
        yield 'empty' => [''];
        yield 'not a string' => [['title', 'desc']];
    }

    #[DataProvider('incompleteSortingDataProvider')]
    #[Test]
    public function anIncompleteSortingSettingKeepsTheDefault(mixed $sorting): void
    {
        $this->assertSame('title asc', $this->createDemand(['filterCollection' => ['degree' => '']], ['sorting' => $sorting])->getSorting());
        $this->assertSame('title asc', $this->createDemand(null, ['sorting' => $sorting])->getSorting());
    }

    /**
     * @param array<string, mixed>|null $demand
     * @param array<string, mixed> $settings
     */
    private function createDemand(?array $demand, array $settings): ProgramDemand
    {
        return $this->get(DemandFactory::class)->createDemandObject($demand, $settings, ['uid' => 1]);
    }
}
