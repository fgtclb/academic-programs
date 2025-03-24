<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Backend\Tca;

use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Labels
{
    private ?CategoryRepository $categoryRepository = null;

    /**
     * @param array<string, mixed> $params
     */
    public function category(array &$params): void
    {
        // @todo needs hardening that param keys really exists.
        $record = BackendUtility::getRecord($params['table'], $params['row']['uid']);
        if ($record !== null) {
            $categoryRepository = $this->categoryRepository();
            /** Requires implementation of {@see CategoryRepository::getCategoryRootline()} first. */
            /*
            $categoryRootline = $categoryRepository->getCategoryRootline($record['uid']);
            $titleParts = [];
            foreach ($categoryRootline as $category) {
                $titleParts[] = $category['title'];
            }
            $params['title'] = implode(' > ', $titleParts);
            */
        }
    }

    private function categoryRepository(): CategoryRepository
    {
        return $this->categoryRepository ??= GeneralUtility::makeInstance(CategoryRepository::class);
    }
}
