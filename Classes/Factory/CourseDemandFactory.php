<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Factory;

use FGTCLB\EducationalCourse\Collection\FilterCollection;
use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Model\Dto\FilterDemand;
use FGTCLB\EducationalCourse\Domain\Repository\CourseCategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CourseDemandFactory
{
    public function __construct(
        private CourseCategoryRepository $categoryRepository
    ) {}

    /**
     * @param array{settings: array<string, mixed>, filters: array<string, int>|empty, currentPageId: int|null} $settings
     */
    public function createDemandObject(array $settings): FilterDemand
    {
        $demand = GeneralUtility::makeInstance(FilterDemand::class);

        if (isset($settings['settings']['sorting'])) {
            [$field, $sorting] = GeneralUtility::trimExplode(' ', $settings['settings']['sorting']);
            $demand->setSorting($sorting);
            $demand->setSortingField($field);
        }

        $uid = null;
        // Categories ~ Filter Options
        if (empty($settings['filters'])
            && isset($settings['settings']['categories'])
            && (int)$settings['settings']['categories'] > 0
        ) {
            $uid = $settings['currentPageId'] ?? null;
        }

        if ($uid !== null) {
            $filterCategories = $this->categoryRepository->getByDatabaseFields($uid);
            $filter = FilterCollection::createByCategoryCollection($filterCategories);
        } elseif ($settings['filters'] !== null) {
            // find by Selected Type Categories
            $filterCategories = new CategoryCollection();

            foreach ($settings['filters'] as $type => $categoriesIds) {
                $formatType = GeneralUtility::camelCaseToLowerCaseUnderscored($type);
                $categoriesIdList = GeneralUtility::intExplode(',', $categoriesIds);

                $categoryFilterObject = $this->categoryRepository->findByUidListAndType($categoriesIdList, Category::cast($formatType));
                if ($categoryFilterObject === null) {
                    continue;
                }

                foreach ($categoryFilterObject as $educationalCategory) {
                    $filterCategories->attach($educationalCategory);
                }
            }

            $filter = FilterCollection::createByCategoryCollection($filterCategories);
        } else {
            $filter = FilterCollection::resetCollection();
        }

        $demand->setFilterCollection($filter);

        return $demand;
    }
}
