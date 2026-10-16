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
        // submission nor a URL may change what the editor did not offer to change (ACE-916).
        $hideSorting = (bool)($settings['hideSorting'] ?? false);
        $hideFilter = (bool)($settings['hideFilter'] ?? false);

        if ($demandFromForm === null || $hideSorting) {
            $this->applySortingFromSettings($demand, $settings);
        } elseif (isset($demandFromForm['sorting'])) {
            // Either use combined sorting or separate sorting field and direction
            $demand->setSorting($this->optionValue($demandFromForm['sorting']));
        } else {
            if (isset($demandFromForm['sortingField'])) {
                $demand->setSortingField($this->optionValue($demandFromForm['sortingField']));
            }
            if (isset($demandFromForm['sortingDirection'])) {
                $demand->setSortingDirection($this->optionValue($demandFromForm['sortingDirection']));
            }
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
     * A request without a demand gets the preset categories of the element, and a demand
     * replaces them with the categories it carries, as the visible filter submits them.
     * Where the element hides the filter, every preset stays and categories a request
     * carries are added to them: a record has to carry every category, so a link can
     * narrow the list but never leave what the editor set (ACE-916).
     *
     * @param ?array<string, mixed> $demandFromForm
     * @param array<string, mixed> $settings
     */
    private function resolveCategories(
        ?array $demandFromForm,
        array $settings,
        int $contentElementUid,
        bool $hideFilter
    ): ?CategoryCollection {
        $presetCategories = null;
        if (($demandFromForm === null || $hideFilter)
            && isset($settings['categories'])
            && (int)$settings['categories'] > 0
        ) {
            $presetCategories = $this->categoryRepository->getByDatabaseFields('programs', $contentElementUid);
        }
        $filter = $demandFromForm['filterCollection'] ?? null;
        if ($demandFromForm === null || ($hideFilter && $filter === null)) {
            return $presetCategories;
        }
        if ($filter === null) {
            return null;
        }

        $uids = $this->categoryFilterNormalizer->toUidList($filter);
        if ($presetCategories !== null) {
            foreach ($presetCategories as $category) {
                $uids[] = $category->getUid();
            }
        }

        return $this->categoryRepository->findByGroupAndUidList('programs', array_values(array_unique($uids)));
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
     * A value of a request as the string the setters of the demand take. A value that is
     * not a string, the array of `demand[sortingField][]=title` for example, which Extbase
     * hands over as it came, becomes the empty string. No setter accepts that, so the
     * value is ignored like an unknown option instead of raising a `TypeError`.
     */
    private function optionValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
