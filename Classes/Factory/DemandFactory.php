<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Factory;

use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Utility\PagesUtility;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DemandFactory
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
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
        $categoryCollection = null;

        // Initialise demand from settings if there is no demand from form
        if ($demandFromForm === null) {
            if (isset($settings['sorting'])) {
                [$sortingField, $sortingDirection] = GeneralUtility::trimExplode(' ', $settings['sorting']);
                $demand->setSortingField($sortingField);
                $demand->setSortingDirection($sortingDirection);
            }

            if (isset($settings['categories'])
                && (int)$settings['categories'] > 0
            ) {
                $categoryCollection = $this->categoryRepository->getByDatabaseFields('programs', (int)$contentElementData['uid']);
            }
        } else {
            // Either use combined sorting or separate sorting field and direction
            if (isset($demandFromForm['sorting'])) {
                $demand->setSorting($demandFromForm['sorting']);
            } else {
                if (isset($demandFromForm['sortingField'])) {
                    $demand->setSortingField($demandFromForm['sortingField']);
                }
                if (isset($demandFromForm['sortingDirection'])) {
                    $demand->setSortingDirection($demandFromForm['sortingDirection']);
                }
            }

            if (isset($demandFromForm['filterCollection'])) {
                $categoryUids = [];
                foreach ($demandFromForm['filterCollection'] as $uids) {
                    $categoryUids = array_merge($categoryUids, GeneralUtility::intExplode(',', $uids));
                }

                $categoryCollection = $this->categoryRepository->findByGroupAndUidList(
                    'programs',
                    $categoryUids,
                );
            }
        }

        if ($categoryCollection !== null) {
            $demand->setFilterCollection(new FilterCollection($categoryCollection));
        }

        // Set demand properties, which are always defined by plugin settings
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
}
