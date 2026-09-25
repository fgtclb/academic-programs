<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Service;

use FGTCLB\AcademicPrograms\Domain\Model\ProgramFact;
use FGTCLB\AcademicPrograms\Domain\Model\ProgramFactsSourceInterface;
use FGTCLB\AcademicPrograms\Enumeration\ProgramFactsPlace;
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
 * "Every category type" is taken in the order of
 * {@see \FGTCLB\CategoryTypes\Collection\CategoryCollection::getAllCategoriesByType()},
 * which is the order of the category type registry for the group. This class keeps no type
 * list of its own and never sorts types, so the facts follow whatever order the registry
 * defines.
 */
final readonly class ProgramFactsBuilder
{
    public const BUILT_IN_FACTS = ['creditPoints', 'jobProfile', 'performanceScope', 'prerequisites'];

    public const CREDIT_POINTS_ICON = 'tx-academicprograms-info-credit-points';

    /**
     * @return list<ProgramFact>
     */
    public function build(ProgramFactsSourceInterface $program, string $fields, ProgramFactsPlace $place): array
    {
        $categoriesByType = $program->getCategoryCollection()->getAllCategoriesByType();
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
        return $value !== '' ? ProgramFact::forBuiltIn($identifier, $value) : null;
    }
}
