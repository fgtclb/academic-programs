<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Service;

use FGTCLB\AcademicPrograms\Domain\Model\ProgramFact;
use FGTCLB\AcademicPrograms\Domain\Model\ProgramFactsSourceInterface;
use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use FGTCLB\AcademicPrograms\Enumeration\ProgramFactsPlace;
use TYPO3\CMS\Core\Schema\Field\TextFieldType;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Which facts of a program are shown, and in which order: the one resolver behind the
 * program page, the program details content element and the program card.
 *
 * The field list is comma-separated. An item is the identifier of a category type of the
 * `programs` group or one of {@see self::BUILT_IN_FACTS}; any other item is skipped, and so
 * is a fact the program has no value for - a category type without a category, an empty
 * text, credit points of 0. A repeated item is shown once, where it is named first.
 *
 * A non-empty list is shown in the order it states. An empty list stands for what each
 * place showed before the list existed:
 *
 * - the program page: every category type, then the four built-in facts;
 * - the details content element: every category type;
 * - the card: the degree.
 *
 * With `$mostSpecificCategoriesOnly`, a category type fact leaves out every category that is
 * an ancestor of another category of the same type the program carries, see
 * {@see \FGTCLB\CategoryTypes\Collection\CategoryCollection::getMostSpecificCategoriesByType()}.
 *
 * "Every category type" is taken in the order of
 * {@see \FGTCLB\CategoryTypes\Collection\CategoryCollection::getAllCategoriesByType()},
 * which is the order of the category type registry for the group. This class keeps no type
 * list of its own and never sorts types, so the facts follow whatever order the registry
 * defines.
 *
 * A text fact is rich text when its column of `pages` has the rich text editor enabled for
 * the program page type, read from the TCA schema on every build: the sub-schema of the
 * program page type, which carries its `columnsOverrides`, or the base schema of `pages`
 * when a project removed that type or left the field out of its form. Every source is a
 * program page, the interface exposes no doktype. Credit points and category type facts
 * are never rich text.
 */
final readonly class ProgramFactsBuilder
{
    public const BUILT_IN_FACTS = ['creditPoints', 'jobProfile', 'performanceScope', 'prerequisites'];

    /**
     * The icon of the credit points fact, an identifier of the frontend icon registry of
     * EXT:academic_base. This extension registers it in `Configuration/FrontendIcons.php`.
     */
    public const CREDIT_POINTS_ICON = 'tx-academicprograms-info-credit-points';

    /**
     * The column of `pages` behind each text fact.
     */
    private const TEXT_FACT_COLUMNS = [
        'jobProfile' => 'job_profile',
        'performanceScope' => 'performance_scope',
        'prerequisites' => 'prerequisites',
    ];

    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * @return list<ProgramFact>
     */
    public function build(
        ProgramFactsSourceInterface $program,
        string $fields,
        ProgramFactsPlace $place,
        bool $mostSpecificCategoriesOnly = false,
    ): array {
        $categoryCollection = $program->getCategoryCollection();
        $categoriesByType = $mostSpecificCategoriesOnly
            ? $categoryCollection->getMostSpecificCategoriesByType()
            : $categoryCollection->getAllCategoriesByType();
        $identifiers = GeneralUtility::trimExplode(',', $fields, true);
        if ($identifiers === []) {
            $identifiers = match ($place) {
                ProgramFactsPlace::Page => [...array_keys($categoriesByType), ...self::BUILT_IN_FACTS],
                ProgramFactsPlace::Details => array_keys($categoriesByType),
                ProgramFactsPlace::Card => ['degree'],
            };
        }

        $facts = [];
        foreach (array_unique($identifiers) as $identifier) {
            $fact = in_array($identifier, self::BUILT_IN_FACTS, true)
                ? $this->builtInFact($program, $identifier)
                : $this->categoryTypeFact($identifier, $categoriesByType[$identifier] ?? []);
            if ($fact !== null) {
                $facts[] = $fact;
            }
        }
        return $facts;
    }

    /**
     * @param array<int|string, \FGTCLB\CategoryTypes\Domain\Model\Category> $categories
     */
    private function categoryTypeFact(string $identifier, array $categories): ?ProgramFact
    {
        if ($categories === []) {
            return null;
        }
        return ProgramFact::forCategoryType($identifier, array_values($categories));
    }

    private function builtInFact(ProgramFactsSourceInterface $program, string $identifier): ?ProgramFact
    {
        if ($identifier === 'creditPoints') {
            $creditPoints = $program->getCreditPoints();
            return $creditPoints > 0
                ? ProgramFact::forBuiltIn($identifier, (string)$creditPoints, self::CREDIT_POINTS_ICON)
                : null;
        }
        $value = match ($identifier) {
            'jobProfile' => $program->getJobProfile(),
            'performanceScope' => $program->getPerformanceScope(),
            'prerequisites' => $program->getPrerequisites(),
            default => '',
        };
        return $value !== ''
            ? ProgramFact::forBuiltIn($identifier, $value, isRichText: $this->isRichText(self::TEXT_FACT_COLUMNS[$identifier]))
            : null;
    }

    private function isRichText(string $column): bool
    {
        if (!$this->tcaSchemaFactory->has('pages')) {
            return false;
        }
        $schema = $this->tcaSchemaFactory->get('pages');
        $programPageType = (string)PageTypes::TYPE_ACADEMIC_PROGRAM;
        // A sub-schema holds only the fields of its form, a field left out of it is read
        // from the base column.
        if ($schema->hasSubSchema($programPageType) && $schema->getSubSchema($programPageType)->hasField($column)) {
            $schema = $schema->getSubSchema($programPageType);
        }
        if (!$schema->hasField($column)) {
            return false;
        }
        $field = $schema->getField($column);
        return $field instanceof TextFieldType && $field->isRichText();
    }
}
