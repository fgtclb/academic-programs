<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Utility;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class PagesUtility
{
    /**
     * @param int[] $pageIds
     * @param int|null $maxDepth Maximum depth to recurse. null means traverse tree until no children pages could be found.
     * @return int[]
     */
    public static function getPagesRecursively(array $pageIds, ?int $maxDepth = null): array
    {
        if ($maxDepth === 0) {
            return [];
        }

        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);

        $foundSubPages = [];
        $currentDepth = 0;
        $restrictDepth = $maxDepth !== null;

        do {
            $subPages = $pageRepository->getMenu($pageIds, 'uid');
            $pageIds = array_keys($subPages);
            $foundSubPages = array_merge($foundSubPages, $pageIds);
            $currentDepth++;

            // Stop reaching maxDepth in case depth restriction is active.
            if ($restrictDepth && $currentDepth >= $maxDepth) {
                break;
            }
        } while (count($subPages)); // @todo Consider using `$subPages !== []` after covering with tests first.

        return array_unique($foundSubPages);
    }
}
