<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Repository;

use FGTCLB\AcademicBase\Persistence\HiddenRecordsFetcher;
use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Domain\Model\Program;
use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Program>
 */
class ProgramRepository extends Repository
{
    private CategoryRepository $categoryRepository;

    private HiddenRecordsFetcher $hiddenRecordsFetcher;

    /**
     * Method injection keeps the constructor of the Extbase repository, which project
     * subclasses may call, unchanged.
     */
    final public function injectCategoryRepository(CategoryRepository $categoryRepository): void
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Method injection keeps the constructor of the Extbase repository, which project
     * subclasses may call, unchanged.
     */
    final public function injectHiddenRecordsFetcher(HiddenRecordsFetcher $hiddenRecordsFetcher): void
    {
        $this->hiddenRecordsFetcher = $hiddenRecordsFetcher;
    }

    /**
     * @return QueryResult<Program>
     * @throws InvalidEnumerationValueException
     */
    public function findByDemand(ProgramDemand $demand): QueryResult
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        if ($demand->getShowHiddenRecords() === true) {
            // Include hidden (disabled) records; other enable fields
            // (deleted, start-/endtime, fe_group) stay in effect.
            $query->getQuerySettings()->setIgnoreEnableFields(true);
            $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);
        }

        $constraints = [];
        $constraints[] = $query->equals('doktype', PageTypes::TYPE_ACADEMIC_PROGRAM);
        if (!empty($demand->getPages())) {
            $constraints[] = $query->in('pid', $demand->getPages());
        }
        if ($demand->getFilterCollection() !== null) {
            $categoryUids = [];
            foreach ($demand->getFilterCollection()->getFilterCategories() as $category) {
                $categoryUids[] = $category->getUid();
            }
            // Each selection is widened by its own subtree and the selections stay joined with
            // AND. One `in()` over all uids would not do: Extbase joins the relation once per
            // query, so two such constraints would have to match the same category row.
            $descendantUids = $demand->getIncludeSubcategories()
                ? $this->categoryRepository->findDescendantUids('programs', ...$categoryUids)
                : [];
            foreach ($categoryUids as $categoryUid) {
                $matches = [$query->contains('categories', $categoryUid)];
                foreach ($descendantUids[$categoryUid] ?? [] as $descendantUid) {
                    $matches[] = $query->contains('categories', $descendantUid);
                }
                $constraints[] = count($matches) === 1 ? $matches[0] : $query->logicalOr(...$matches);
            }
        }
        // The method signature of logicalAnd and logicalOr has changed in TYPO3 v12
        // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-96044-HardenMethodSignatureOfLogicalAndAndLogicalOr.html
        $query->matching(
            $query->logicalAnd(...array_values($constraints))
        );
        $query->setOrderings(
            [
                $demand->getSortingField() => strtoupper($demand->getSortingDirection()),
                // Records equal in the demanded ordering would otherwise follow the DBMS
                // row order, which is not the same list twice (ACE-491). None of the
                // `SortingOptions` sorts by `uid`, so the tiebreaker never collides.
                'uid' => QueryInterface::ORDER_ASCENDING,
            ]
        );
        return $this->hiddenRecordsFetcher->execute($query);
    }
}
