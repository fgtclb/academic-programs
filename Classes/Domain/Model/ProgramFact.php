<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Model;

use FGTCLB\CategoryTypes\Domain\Model\Category;

/**
 * One row of the facts of a program, as `Partials/Program/Facts/Item.html` renders it.
 *
 * A category type fact carries the categories of the program of that type, a built-in fact
 * the value of its program field. `labelKey` is a key of `locallang.xlf` of this extension,
 * and `iconIdentifier` is empty for a fact without an icon.
 */
final readonly class ProgramFact
{
    /**
     * @param list<Category> $categories
     */
    private function __construct(
        public string $identifier,
        public bool $isCategoryType,
        public string $labelKey,
        public string $iconIdentifier,
        public array $categories,
        public string $value,
    ) {}

    /**
     * @param list<Category> $categories
     */
    public static function forCategoryType(string $identifier, array $categories): self
    {
        return new self(
            identifier: $identifier,
            isCategoryType: true,
            labelKey: 'sys_category.programs.' . $identifier,
            iconIdentifier: 'category_types.programs.' . $identifier,
            categories: $categories,
            value: '',
        );
    }

    public static function forBuiltIn(string $identifier, string $value, string $iconIdentifier = ''): self
    {
        return new self(
            identifier: $identifier,
            isCategoryType: false,
            labelKey: 'program.' . $identifier,
            iconIdentifier: $iconIdentifier,
            categories: [],
            value: $value,
        );
    }
}
