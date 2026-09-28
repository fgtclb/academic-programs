<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Factory;

use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Factory\DemandFactory;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * `settings.filter.includeSubcategories` of the list plugin reaches the demand, whether
 * the categories come from the element or from the filter form. The request never decides
 * it: the option belongs to the element.
 */
final class DemandFactoryIncludeSubcategoriesTest extends AbstractAcademicProgramsTestCase
{
    /**
     * FormEngine stores a checkbox toggle as "1" or "0". An element saved before the field
     * existed has none, and a broken TypoScript override may leave a string for the whole
     * `filter` array.
     *
     * @return \Generator<string, array{0: array<string, mixed>, 1: bool}>
     */
    public static function settingsDataProvider(): \Generator
    {
        yield 'switched on' => [['filter' => ['includeSubcategories' => '1']], true];
        yield 'switched off' => [['filter' => ['includeSubcategories' => '0']], false];
        yield 'saved before the field existed' => [['filter' => ['categoryTypes' => 'degree']], false];
        yield 'no filter settings at all' => [[], false];
        yield 'filter settings that are no array' => [['filter' => '1'], false];
    }

    /**
     * @param array<string, mixed> $settings
     */
    #[DataProvider('settingsDataProvider')]
    #[Test]
    public function theOptionOfTheElementReachesTheDemandWithoutAFilterForm(array $settings, bool $expected): void
    {
        $this->assertSame($expected, $this->createDemand(null, $settings)->getIncludeSubcategories());
    }

    /**
     * @param array<string, mixed> $settings
     */
    #[DataProvider('settingsDataProvider')]
    #[Test]
    public function theOptionOfTheElementReachesTheDemandWithAFilterForm(array $settings, bool $expected): void
    {
        $this->assertSame($expected, $this->createDemand(['filterCollection' => []], $settings)->getIncludeSubcategories());
    }

    #[Test]
    public function aSubmittedOptionIsIgnored(): void
    {
        $demand = $this->createDemand(['includeSubcategories' => '1', 'filter' => ['includeSubcategories' => '1']], []);

        $this->assertFalse($demand->getIncludeSubcategories());
    }

    /**
     * @param array<string, mixed>|null $demandFromForm
     * @param array<string, mixed> $settings
     */
    private function createDemand(?array $demandFromForm, array $settings): ProgramDemand
    {
        return $this->get(DemandFactory::class)->createDemandObject($demandFromForm, $settings, ['uid' => 1]);
    }
}
