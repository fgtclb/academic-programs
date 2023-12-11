<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Factory;

use FGTCLB\EducationalCourse\Collection\FilterCollection;
use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Model\Dto\CourseDemand;
use FGTCLB\EducationalCourse\Domain\Repository\CourseCategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CourseDemandFactory
{
    public function __construct(
        private CourseCategoryRepository $categoryRepository
    ) {}

    /**
     * @param ?array<mixed> $demandFromForm
     * @param array{settings: array<string, mixed>, filters: array<string, int>|empty, currentPageId: int|null} $settings
     */
    public function createDemandObject(
        ?array $demandFromForm,
        array $settings,
        int $pluginUid
    ): CourseDemand {
        $demand = GeneralUtility::makeInstance(CourseDemand::class);
        $filterCollection = GeneralUtility::makeInstance(FilterCollection::class);

        // Intitialise demand from settings if there is no demand from form
        if ($demandFromForm === null) {
            if (isset($settings['sorting'])) {
                [$sortingField, $sortingDirection] = GeneralUtility::trimExplode(' ', $settings['sorting']);
                $demand->setSortingField($sortingField);
                $demand->setSortingDirection($sortingDirection);
            }

            if (isset($settings['categories'])
                && (int)$settings['categories'] > 0
            ) {
                $categoryCollection = $this->categoryRepository->getByDatabaseFields($pluginUid);
                $filterCollection = FilterCollection::createByCategoryCollection($categoryCollection);
            }
        } else {
            if (isset($demandFromForm['sortingField'])) {
                $demand->setSortingField($demandFromForm['sortingField']);
            }

            if (isset($demandFromForm['sortingDirection'])) {
                $demand->setSortingDirection($demandFromForm['sortingDirection']);
            }

            if ($demandFromForm['filterCollection'] !== null) {
                // Find by selected type categories
                $categoryCollection = new CategoryCollection();

                foreach ($demandFromForm['filterCollection'] as $type => $categoriesIds) {
                    $formatType = GeneralUtility::camelCaseToLowerCaseUnderscored($type);
                    $categoriesIdList = GeneralUtility::intExplode(',', $categoriesIds);
                    $categoryFilterObject = $this->categoryRepository->findByUidListAndType($categoriesIdList, Category::cast($formatType));
                    if ($categoryFilterObject === null) {
                        continue;
                    }

                    foreach ($categoryFilterObject as $educationalCategory) {
                        $categoryCollection->attach($educationalCategory);
                    }
                }

                $filterCollection = FilterCollection::createByCategoryCollection($categoryCollection);
            }
        }

        $demand->setFilterCollection($filterCollection);

        return $demand;
    }
}
