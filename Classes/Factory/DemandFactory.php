<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Factory;

use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Utility\PagesUtility;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Filter\CategoryFilterNormalizer;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[Autoconfigure(public: true)]
class DemandFactory
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly CategoryFilterNormalizer $categoryFilterNormalizer,
    ) {}

    /**
     * @param ?array<string, mixed> $demandFromForm
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $contentElementData
     */
    public function createDemandObject(
        ?array $demandFromForm,
        array $settings,
        array $contentElementData
    ): ProgramDemand {
        $demand = GeneralUtility::makeInstance(ProgramDemand::class);

        // The content element presets every part of the form for a request without a demand,
        // and a part it hides for every request: a hidden part submits nothing, and neither a
        // submission nor a URL may change what the editor did not offer to change. Categories
        // may only narrow a hidden filter, see resolveCategories() (ACE-916).
        $hideSorting = (bool)($settings['hideSorting'] ?? false);
        $hideFilter = (bool)($settings['hideFilter'] ?? false);

        if ($demandFromForm === null || $hideSorting) {
            $this->applySortingFromSettings($demand, $settings);
        } elseif (isset($demandFromForm['sorting'])) {
            // Either use combined sorting or separate sorting field and direction
            $demand->setSorting($demandFromForm['sorting']);
        } elseif (isset($demandFromForm['sortingField']) || isset($demandFromForm['sortingDirection'])) {
            if (isset($demandFromForm['sortingField'])) {
                $demand->setSortingField($demandFromForm['sortingField']);
            }
            if (isset($demandFromForm['sortingDirection'])) {
                $demand->setSortingDirection($demandFromForm['sortingDirection']);
            }
        } else {
            // A demand without any sorting, the one of the program finder, keeps the sorting
            // of the element.
            $this->applySortingFromSettings($demand, $settings);
        }

        $categoryCollection = $this->resolveCategories(
            $demandFromForm,
            $settings,
            (int)($contentElementData['uid'] ?? 0),
            $hideFilter,
        );

        if ($categoryCollection !== null) {
            $demand->setFilterCollection(new FilterCollection($categoryCollection));
        }

        // Set demand properties, which are always defined by plugin settings
        $demand->setShowHiddenRecords((bool)($settings['showHiddenRecords'] ?? false));
        $filterSettings = is_array($settings['filter'] ?? null) ? $settings['filter'] : [];
        $demand->setIncludeSubcategories((bool)($filterSettings['includeSubcategories'] ?? false));
        $demand->setPages([]);
        if (isset($contentElementData['pages'])
            && is_string($contentElementData['pages'])
            && $contentElementData['pages'] !== ''
        ) {
            $pageIds = GeneralUtility::intExplode(',', $contentElementData['pages']);
            // Handle recursive page selection
            if ($pageIds !== []) {
                // Resolve recursive depth option from content element record with fallbacks.
                $recursiveDepth = (int)($contentElementData['recursive']
                    // First fallback to defined TCA default value
                    ?? $GLOBALS['TCA']['tt_content']['recursive']['config']['default']
                    // Second fallback to integer 0, which is the expected default doing nothing.
                    ?? 0);
                // 250 means infinite depth, reset to `null` for depth
                if ($recursiveDepth === 250) {
                    $recursiveDepth = null;
                }
                // Get $pageIds sub-pages ids.
                $subPageIds = PagesUtility::getPagesRecursively($pageIds, $recursiveDepth);
                // Merge original pages with subpages
                $pageIds = array_unique(array_merge($pageIds, $subPageIds));
                // Set merged page ids to the demand object
                $demand->setPages($pageIds);
            }
        }

        return $demand;
    }

    /**
     * The categories the list is filtered by, `null` for none.
     *
     * A request without a demand gets the preset categories of the element. For a demand,
     * {@see CategoryFilterNormalizer::toUidListKeepingPresets()} decides which presets stay:
     * every one where the element hides the filter, so a submission or a link narrows the
     * list but never leaves the presets, and those of the category types a form does not
     * offer otherwise. A demand without a filter has none, unless the filter is hidden.
     *
     * @param ?array<string, mixed> $demandFromForm
     * @param array<string, mixed> $settings
     */
    private function resolveCategories(
        ?array $demandFromForm,
        array $settings,
        int $contentElementUid,
        bool $hideFilter,
    ): ?CategoryCollection {
        $filter = $demandFromForm['filterCollection'] ?? null;
        $presetCategories = null;
        if (($demandFromForm === null || $this->categoryFilterNormalizer->keepsPresets($filter, $hideFilter))
            && isset($settings['categories'])
            && (int)$settings['categories'] > 0
        ) {
            $presetCategories = $this->categoryRepository->getByDatabaseFields('programs', $contentElementUid);
        }
        if ($demandFromForm === null || $filter === null) {
            return $presetCategories;
        }

        return $this->categoryRepository->findByGroupAndUidList(
            'programs',
            $this->categoryFilterNormalizer->toUidListKeepingPresets(
                $filter,
                $presetCategories?->getAllCategoriesByType() ?? [],
                $hideFilter,
            ),
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function applySortingFromSettings(ProgramDemand $demand, array $settings): void
    {
        $sorting = $settings['sorting'] ?? null;
        if (!is_string($sorting)) {
            return;
        }
        // A field and a direction, as the FlexForm stores them. Anything else keeps the
        // default of the demand rather than raising "Undefined array key".
        $parts = GeneralUtility::trimExplode(' ', $sorting, true);
        if (count($parts) === 2) {
            $demand->setSortingField($parts[0]);
            $demand->setSortingDirection($parts[1]);
        }
    }

    /**
     * The reverse of {@see createDemandObject()}: the arguments of a list URL that
     * demands the same selection, read from what the factory accepted rather than from
     * the request. A category that is not a program category, a uid no category has and
     * the referrer and request hash fields of the form are therefore never part of it.
     *
     * The sorting is always included, even when it equals the default. Only a request
     * without any demand argument applies the content element's preset categories,
     * so a visitor who cleared a preset category would otherwise get it back. Presets the
     * demand keeps, those of a hidden part of the form included, are in the demand already,
     * so the URL carries them too. The categories
     * are one comma separated list, see {@see CategoryFilterNormalizer::toFilterArgument()},
     * and are left out when nothing is filtered.
     *
     * @return array<string, mixed>
     */
    public function createDemandArguments(ProgramDemand $demand): array
    {
        $arguments = [
            'sortingField' => $demand->getSortingField(),
            'sortingDirection' => $demand->getSortingDirection(),
        ];
        $filterArgument = $this->categoryFilterNormalizer->toFilterArgument($demand->getFilterCollection());
        if ($filterArgument !== '') {
            $arguments['filterCollection'] = ['categories' => $filterArgument];
        }

        return $arguments;
    }
}
