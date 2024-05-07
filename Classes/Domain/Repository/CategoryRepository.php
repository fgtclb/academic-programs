<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Repository;

use FGTCLB\EducationalCourse\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Model\Category;
use FGTCLB\EducationalCourse\Enumeration\CategoryTypes;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\LimitToTablesRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CategoryRepository
{
    private ?ConnectionPool $connectionPool;

    private PageRepository $pageRepository;

    /**
     * @param ConnectionPool|null $connectionPool
     */
    public function __construct(
        ?ConnectionPool $connectionPool = null
    ) {
        $this->connection = $connectionPool ?? GeneralUtility::makeInstance(ConnectionPool::class);
        $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class);
    }

    /**
     * @param int $pageId
     * @param Category $type
     * @return CategoryCollection
     */
    public function findByType(
        int $pageId,
        Category $type
    ): CategoryCollection {
        $queryBuilder = $this->buildQueryBuilder();

        $result = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->join(
                'sys_category',
                'sys_category_record_mm',
                'mm',
                'sys_category.uid=mm.uid_local'
            )
            ->join(
                'mm',
                'pages',
                'pages',
                'mm.uid_foreign=pages.uid'
            )
            ->where(
                $this->categoryTypeCondition($queryBuilder),
                $this->siteDefaultLanguageCondition($queryBuilder),
                $queryBuilder->expr()->eq(
                    'sys_category.type',
                    $queryBuilder->createNamedParameter((string)$type)
                ),
                $queryBuilder->expr()->eq(
                    'mm.tablenames',
                    $queryBuilder->createNamedParameter('pages')
                ),
                $queryBuilder->expr()->eq(
                    'mm.fieldname',
                    $queryBuilder->createNamedParameter('categories')
                ),
                $queryBuilder->expr()->eq(
                    'pages.uid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
            )->executeQuery();

        $categories = new CategoryCollection();
        if ($result->rowCount() === 0) {
            return $categories;
        }

        foreach ($result->fetchAllAssociative() as $row) {
            $categoryRow = $this->pageRepository->getLanguageOverlay('sys_category', $row);
            if ($categoryRow === null) {
                continue;
            }
            $category = $this->categoryObjectMapping($categoryRow);
            $categories->attach($category);
        }

        return $categories;
    }

    /**
     * @param int $pageId
     * @return CategoryCollection
     */
    public function findAllByPageId(int $pageId): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $result = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->join(
                'sys_category',
                'sys_category_record_mm',
                'mm',
                'sys_category.uid=mm.uid_local'
            )
            ->join(
                'mm',
                'pages',
                'pages',
                'mm.uid_foreign=pages.uid'
            )
            ->where(
                $this->categoryTypeCondition($queryBuilder),
                $this->siteDefaultLanguageCondition($queryBuilder),
                $queryBuilder->expr()->eq(
                    'mm.tablenames',
                    $queryBuilder->createNamedParameter('pages')
                ),
                $queryBuilder->expr()->eq(
                    'mm.fieldname',
                    $queryBuilder->createNamedParameter('categories')
                ),
                $queryBuilder->expr()->eq(
                    'pages.uid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
            )->executeQuery();

        $categories = new CategoryCollection();
        if ($result->rowCount() === 0) {
            return $categories;
        }

        foreach ($result->fetchAllAssociative() as $row) {
            $categoryRow = $this->pageRepository->getLanguageOverlay('sys_category', $row);
            if ($categoryRow === null) {
                continue;
            }
            $category = $this->categoryObjectMapping($categoryRow);
            $categories->attach($category);
        }

        return $categories;
    }

    /**
     * @param int $uid
     * @param string $table
     * @param string $field
     * @return CategoryCollection
     */
    public function getByDatabaseFields(
        int $uid,
        string $table = 'tt_content',
        string $field = 'pi_flexform'
    ): CategoryCollection {
        $queryBuilder = $this->buildQueryBuilder();

        $result = $queryBuilder
            ->select('sys_category.*')
            ->distinct()
            ->from('sys_category')
            ->join(
                'sys_category',
                'sys_category_record_mm',
                'sys_category_record_mm',
                'sys_category.uid=sys_category_record_mm.uid_local'
            )
            ->join(
                'sys_category_record_mm',
                $table,
                $table,
                sprintf('sys_category_record_mm.uid_foreign=%s.uid', $table)
            )
            ->where(
                $this->categoryTypeCondition($queryBuilder),
                $this->siteDefaultLanguageCondition($queryBuilder),
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.tablenames',
                    $queryBuilder->createNamedParameter($table)
                ),
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.fieldname',
                    $queryBuilder->createNamedParameter($field)
                ),
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.uid_foreign',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            )->executeQuery();

        $categories = new CategoryCollection();

        if ($result->rowCount() === 0) {
            return $categories;
        }

        foreach ($result->fetchAllAssociative() as $row) {
            $categoryRow = $this->pageRepository->getLanguageOverlay('sys_category', $row);
            $category = $this->categoryObjectMapping($categoryRow);
            $categories->attach($category);
        }
        return $categories;
    }

    /**
     * @param array<int>|null $idList
     * @return array<CategoryTypes>|null
     */
    public function findByUidListAndType(
        array $idList,
        CategoryTypes $categoryType
    ): ?array {
        $queryBuilder = $this->buildQueryBuilder();

        $queryBuilder->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $this->categoryTypeCondition($queryBuilder),
                $this->siteDefaultLanguageCondition($queryBuilder),
                $queryBuilder->expr()->in('uid', $idList),
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter((string)$categoryType))
            );

        $result = $queryBuilder->executeQuery();

        if ($result->rowCount() === 0) {
            return null;
        }

        $category = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $categoryRow = $this->pageRepository->getLanguageOverlay('sys_category', $row);
            $category[] = $this->categoryObjectMapping($categoryRow);
        }

        return $category;
    }

    /**
     * @param CategoryCollection $applicableCategories
     * @return CategoryCollection
     */
    public function findAllWitDisabledStatus(CategoryCollection $applicableCategories): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $result = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $this->categoryTypeCondition($queryBuilder),
                $this->siteDefaultLanguageCondition($queryBuilder)
            )->executeQuery();

        $categories = new CategoryCollection();

        if ($result->rowCount() === 0) {
            return $categories;
        }

        foreach ($result->fetchAllAssociative() as $row) {
            if (!in_array($row['type'], CategoryTypes::getConstants())) {
                continue;
            }

            $categoryRow = $this->pageRepository->getLanguageOverlay('sys_category', $row);
            if ($categoryRow === null) {
                continue;
            }
            $category = $this->categoryObjectMapping($categoryRow);
            $category->setDisabled(!$applicableCategories->exist($category));
            $categories->attach($category);
        }
        return $categories;
    }

    /**
     * @param int $uid
     * @return ?CategoryCollection
     */
    public function findChildren(int $uid): ?CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $statement = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $this->categoryTypeCondition($queryBuilder),
                $this->siteDefaultLanguageCondition($queryBuilder),
                $queryBuilder->expr()->eq(
                    'parent',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $this->categoryTypeCondition($queryBuilder)
            );

        $result = $statement->executeQuery();

        $categories = new CategoryCollection();

        if ($result->rowCount() === 0) {
            return $categories;
        }

        foreach ($result->fetchAllAssociative() as $row) {
            $categoryRow = $this->pageRepository->getLanguageOverlay('sys_category', $row);
            if ($categoryRow === null) {
                continue;
            }
            $category = $this->categoryObjectMapping($categoryRow);
            $categories->attach($category);
        }
        return $categories;
    }

    public function findParent(int $parent): ?Category
    {
        $queryBuilder = $this->buildQueryBuilder();

        $result = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $this->categoryTypeCondition($queryBuilder),
                $this->siteDefaultLanguageCondition($queryBuilder),
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($parent, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery();

        if ($result->rowCount() === 0) {
            return null;
        }

        $row = $result->fetchAssociative();
        $categoryRow = $this->pageRepository->getLanguageOverlay('sys_category', $row);
        $category = $this->categoryObjectMapping($categoryRow);

        return $category;
    }

    /**
     * @param array<string, mixed> $data
     * @return Category
     */
    private function categoryObjectMapping(array $data): Category
    {
        return new Category(
            $data['uid'],
            $data['parent'],
            $data['title'],
            $data['type']
        );
    }

    /**
     * General check to exclude all non-course related category records
     *
     * @param QueryBuilder $queryBuilder
     * @return string
     */
    private function categoryTypeCondition(QueryBuilder $queryBuilder): string
    {
        return $queryBuilder->expr()->in(
            'sys_category.type',
            array_map(function (string $value) {
                return '\'' . $value . '\'';
            }, array_values(CategoryTypes::getConstants()))
        );
    }

    /**
     * General check to exclude all translated category records
     *
     * @param QueryBuilder $queryBuilder
     * @return string
     */
    private function siteDefaultLanguageCondition(QueryBuilder $queryBuilder): string
    {
        $defaultLanguageUid = $GLOBALS['TYPO3_REQUEST']
            ->getAttribute('site')
            ->getDefaultLanguage()
            ->getLanguageId();

        return $queryBuilder->expr()->in(
            'sys_category.sys_language_uid',
            [$defaultLanguageUid, -1]
        );
    }

    /**
     * @param string $tableName
     * @return QueryBuilder
     */
    private function buildQueryBuilder(string $tableName = 'sys_category'): QueryBuilder
    {
        $queryBuilder = $this->connection->getQueryBuilderForTable($tableName);

        // Add workspace/versioning restrictions (needs handling by PageRepository->versionOL())
        /*
        $queryBuilder->getRestrictions()
            ->add(
                GeneralUtility::makeInstance(LimitToTablesRestrictionContainer::class)
                    ->addForTables(GeneralUtility::makeInstance(WorkspaceRestriction::class), [$tableName])
            );
        */

        return $queryBuilder;
    }
}
