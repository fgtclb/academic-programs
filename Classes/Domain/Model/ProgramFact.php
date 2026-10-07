<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Model;

use FGTCLB\CategoryTypes\Domain\Model\Category;

/**
 * One row of the facts of a program, as `Partials/Program/Facts/Item.html` renders it.
 *
 * A category type fact carries the categories of the program of that type, a built-in fact
 * the value of its program field. `labelKey` is a key of `locallang.xlf` of this extension,
 * and `iconIdentifier` is empty for a fact without an icon. An icon identifier is one of the
 * frontend icon registry of EXT:academic_base, which the partial renders with `ab:icon`:
 * `category_types.programs.<type>` for a category type fact, which EXT:category_types
 * registers there, and {@see \FGTCLB\AcademicPrograms\Service\ProgramFactsBuilder::CREDIT_POINTS_ICON}
 * for the credit points.
 *
 * `isRichText` says whether `value` is HTML from a rich text field. The builder sets it for
 * a program field whose column has the rich text editor enabled for the program page type,
 * see {@see \FGTCLB\AcademicPrograms\Service\ProgramFactsBuilder}. It is `false` for a
 * category type fact, for the credit points and for a text field without the editor, whose
 * value the partial escapes.
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
        public bool $isRichText,
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
            isRichText: false,
        );
    }

    public static function forBuiltIn(string $identifier, string $value, string $iconIdentifier = '', bool $isRichText = false): self
    {
        return new self(
            identifier: $identifier,
            isCategoryType: false,
            labelKey: 'program.' . $identifier,
            iconIdentifier: $iconIdentifier,
            categories: [],
            value: $value,
            isRichText: $isRichText,
        );
    }
}
