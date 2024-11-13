<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Repository;

use FGTCLB\AcademicPrograms\Collection\CategoryCollection;
use FGTCLB\AcademicPrograms\Domain\Model\Category;
use FGTCLB\AcademicPrograms\Domain\Model\Program;
use FGTCLB\AcademicPrograms\Enumeration\CategoryTypes;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

class CategoryRepository
{
    protected int $languageUid = 0;

    protected int $workspaceUid = 0;

    /**
     * Query context
     */
    protected Context $context;

    public function __construct(
        protected ConnectionPool $connectionPool,
        protected PageRepository $pageRepository
    ) {
        $this->context = GeneralUtility::makeInstance(Context::class);
        $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class, $this->context);
        $this->languageUid = $this->context->getPropertyFromAspect('language', 'id', 0);
        $this->workspaceUid = (int)$this->context->getPropertyFromAspect('workspace', 'id', 0);
    }

    /**
     * @param int $pageId
     * @param CategoryTypes $type
     * @return CategoryCollection
     */
    public function findByType(
        int $pageId,
        CategoryTypes $type
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
            $category = $this->buildCategoryObjectFromArray($row);
            $categories->attach($category);
        }

        return $categories;
    }

    /**
     * @param QueryResult<Program> $programs
     */
    public function findAllApplicable(QueryResult $programs): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $result = $queryBuilder
            ->select('sys_category.*')
            ->from('sys_category')
            ->where(
                $this->categoryTypeCondition($queryBuilder),
                $this->siteDefaultLanguageCondition($queryBuilder),
            )->executeQuery();

        $categories = new CategoryCollection();

        if ($result->rowCount() === 0) {
            return $categories;
        }

        // Generate aa list of all categories which are assigned to the given programs
        $applicableCategories = [];
        foreach ($programs as $program) {
            foreach ($program->getAttributes() as $attribute) {
                $applicableCategories[] = $attribute->getUid();
            }
        }

        // Disable all categories which are not assigned to any of the given programs
        foreach ($result->fetchAllAssociative() as $row) {
            $category = $this->buildCategoryObjectFromArray($row);
            if (!in_array($row['uid'], $applicableCategories)) {
                $category->setDisabled(true);
            }
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
            $category = $this->buildCategoryObjectFromArray($row);
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
            $category = $this->buildCategoryObjectFromArray($row);
            $categories->attach($category);
        }
        return $categories;
    }

    /**
     * @param array<int> $idList
     * @param CategoryTypes $categoryType
     * @return ?Category[]
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
            $category[] = $this->buildCategoryObjectFromArray($row);
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

            $category = $this->buildCategoryObjectFromArray($row);
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
            $category = $this->buildCategoryObjectFromArray($row);
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
        if ($row === false) {
            return null;
        }
        $category = $this->buildCategoryObjectFromArray($row);

        return $category;
    }

    /**
     * @param array<string, mixed> $row
     * @return Category
     */
    private function buildCategoryObjectFromArray(array $row): Category
    {
        $row = $this->pageRepository->getLanguageOverlay('sys_category', $row) ?? $row;

        return new Category(
            $row['uid'],
            $row['parent'],
            $row['title'],
            $row['type']
        );
    }

    /**
     * General check to exclude all non-program related category records
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
     * @param array<int, mixed> $rootline
     * @return array<int, mixed>
     */
    public function getCategoryRootline(int $uid, array $rootline = []): array
    {
        $category = $this->getCategoryArray($uid);
        $rootline[] = $category;

        if ($category['parent'] !== 0) {
            $rootline = $this->getCategoryRootline($category['parent'], $rootline);
        } else {
            $rootline = array_reverse($rootline);
        }

        return $rootline;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCategoryArray(int $uid): array
    {
        $queryBuilder = $this->buildQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $category = $queryBuilder->select('*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter([0, $this->workspaceUid], Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery()
            ->fetchAssociative();
        $this->pageRepository->versionOL('sys_category', $category, false, true);
        $category = $this->pageRepository->getLanguageOverlay('sys_category', $category, $this->context->getAspect('language'));

        return $category;
    }

    /**
     * @param string $tableName
     * @return QueryBuilder
     */
    private function buildQueryBuilder(string $tableName = 'sys_category'): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        return $queryBuilder;
    }
}
