<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Repository;

use FGTCLB\EducationalCourse\Database\Query\Restriction\LanguageRestriction;
use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Model\EducationalCategory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ToDo: Merge repository with "EducationalCategoryRepository"
 */
class CourseCategoryRepository
{
    /**
     * @var string[]
     */
    private array $categoryFieldList = [
        'sys_category.uid',
        'sys_category.type',
        'sys_category.parent',
        'sys_category.title',
    ];

    protected ConnectionPool $connection;

    public function __construct(
        ?ConnectionPool $connectionPool = null
    ) {
        $this->connection = $connectionPool ?? GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * Find Category relation
     */
    public function findByType(int $pageId, Category $type): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $result = $queryBuilder
            ->select(...$this->categoryFieldList)
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

        $attributes = new CategoryCollection();
        if ($result->rowCount() === 0) {
            return $attributes;
        }

        foreach ($result->fetchAllAssociative() as $attribute) {
            $category = $this->categoryObjectMapping($attribute);
            $attributes->attach($category);
        }

        return $attributes;
    }

    public function findAllByPageId(int $pageId): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $result = $queryBuilder
            ->select(...$this->categoryFieldList)
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

        $attributes = new CategoryCollection();
        if ($result->rowCount() === 0) {
            return $attributes;
        }

        foreach ($result->fetchAllAssociative() as $row) {
            $category = $this->categoryObjectMapping($row);
            $attributes->attach($category);
        }

        return $attributes;
    }

    public function findAll(): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $result = $queryBuilder
            ->select(...$this->categoryFieldList)
            ->from('sys_category')
            ->where(
                $this->categoryTypeCondition($queryBuilder)
            )->executeQuery();

        $attributes = new CategoryCollection();

        if ($result->rowCount() === 0) {
            return $attributes;
        }

        foreach ($result->fetchAllAssociative() as $row) {
            $category = $this->categoryObjectMapping($row);
            $attributes->attach($category);
        }
        return $attributes;
    }

    public function getByDatabaseFields(int $uid, string $table = 'tt_content', string $field = 'pi_flexform'): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $result = $queryBuilder
            ->select(...$this->categoryFieldList)
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

        $attributes = new CategoryCollection();

        if ($result->rowCount() === 0) {
            return $attributes;
        }

        foreach ($result->fetchAllAssociative() as $row) {
            $category = $this->categoryObjectMapping($row);
            $attributes->attach($category);
        }
        return $attributes;
    }

    /**
     * @param array<int>|null $idList
     * @return array<EducationalCategory>|null
     */
    public function findByUidListAndType(array $idList, Category $categoryType): ?array
    {
        $queryBuilder = $this->buildQueryBuilder();

        $queryBuilder->select(...$this->categoryFieldList)
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->in('uid', $idList),
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter((string)$categoryType))
            );

        $result = $queryBuilder->executeQuery();

        if ($result->rowCount() === 0) {
            return null;
        }

        $category = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $category[] = $this->categoryObjectMapping($row);
        }

        return $category;
    }

    public function findAllWitDisabledStatus(CategoryCollection $applicableCategories): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $result = $queryBuilder
            ->select(...$this->categoryFieldList)
            ->from('sys_category')
            ->where(
                $this->categoryTypeCondition($queryBuilder)
            )->executeQuery();

        $categories = new CategoryCollection();

        if ($result->rowCount() === 0) {
            return $categories;
        }

        foreach ($result->fetchAllAssociative() as $attribute) {
            if (!in_array($attribute['type'], Category::getConstants())) {
                continue;
            }

            $category = $this->categoryObjectMapping($attribute);
            $category->setDisabled(!$applicableCategories->exist($category));

            $categories->attach($category);
        }
        return $categories;
    }

    private function categoryObjectMapping(array $data): EducationalCategory
    {
        return new EducationalCategory($data['uid'], $data['parent'], $data['title'], $data['type']);
    }

    private function categoryTypeCondition(QueryBuilder $queryBuilder): string
    {
        return $queryBuilder->expr()->in(
            'sys_category.type',
            array_map(function (string $value) {
                return '\'' . $value . '\'';
            }, array_values(Category::getConstants()))
        );
    }

    private function buildQueryBuilder(string $tableName = 'sys_category'): QueryBuilder
    {
        $queryBuilder = $this->connection->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(LanguageRestriction::class));

        return $queryBuilder;
    }
}
