<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Backend\Tca;

use FGTCLB\AcademicPrograms\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Labels
{
    /**
     * @param array<mixed> $params
     */
    public function category(&$params): void
    {
        $record = BackendUtility::getRecord($params['table'], $params['row']['uid']);

        if ($record !== null) {
            $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
            $categoryRootline = $categoryRepository->getCategoryRootline($record['uid']);

            $titleParts = [];
            foreach ($categoryRootline as $category) {
                $titleParts[] = $category['title'];
            }

            $params['title'] = implode(' > ', $titleParts);
        }
    }
}
