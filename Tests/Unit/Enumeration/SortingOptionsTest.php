<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Unit\Enumeration;

use FGTCLB\AcademicPrograms\Enumeration\SortingOptions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The values are used verbatim as Extbase ordering strings, and `getConstants()` is
 * what `ProgramDemand::setSorting()` validates against - so a value that drops out of
 * this list stops being selectable without anything failing.
 */
final class SortingOptionsTest extends UnitTestCase
{
    /**
     * The full set, asserted as a whole rather than by counting: a renamed constant or
     * a changed ordering string has to show up here, since both are part of what a
     * stored FlexForm value refers to.
     *
     * Note the asymmetry - `sorting` is offered ascending only, unlike the two other
     * fields. `ProgramDemandTest` covers what that means for `setSortingDirection()`.
     */
    #[Test]
    public function everySortingOptionIsOffered(): void
    {
        $this->assertSame(
            [
                'SORT_BY_TITLE_ASC' => 'title asc',
                'SORT_BY_TITLE_DESC' => 'title desc',
                'SORT_BY_LASTUPDATED_ASC' => 'lastUpdated asc',
                'SORT_BY_LASTUPDATED_DESC' => 'lastUpdated desc',
                'SORT_BY_SORTING_ASC' => 'sorting asc',
            ],
            SortingOptions::getConstants(),
        );
    }

    /**
     * `__default` is an alias of an option that is already in the list, so offering it
     * would present the same ordering twice.
     */
    #[Test]
    public function theDefaultAliasIsNotAnOptionOfItsOwn(): void
    {
        $this->assertArrayNotHasKey('__default', SortingOptions::getConstants());
        $this->assertSame(SortingOptions::SORT_BY_TITLE_ASC, SortingOptions::__default);
    }

    /**
     * `ProgramDemand::setSorting()` splits an accepted option with a plain
     * `explode(' ', $sorting)` into exactly two variables, so an option carrying no
     * space - or more than one - would raise "Undefined array key 1" instead of
     * sorting. The suite runs with `failOnNotice`, so that would be a failure rather
     * than a warning, but only if an option like that ever reached the list. This
     * asserts it cannot.
     */
    #[Test]
    public function everyOptionIsExactlyAFieldAndADirection(): void
    {
        foreach (SortingOptions::getConstants() as $name => $value) {
            $this->assertMatchesRegularExpression(
                '/^[a-zA-Z0-9_]+ (asc|desc)$/',
                $value,
                sprintf('%s is not a "<field> <asc|desc>" pair', $name),
            );
        }
    }

    /**
     * Two constants sharing a value would make the option list ambiguous - the FlexForm
     * stores the value, not the constant name, so the second one could never be selected.
     */
    #[Test]
    public function noOrderingIsOfferedTwice(): void
    {
        $values = array_values(SortingOptions::getConstants());
        $this->assertSame($values, array_values(array_unique($values)));
    }
}
