<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\EducationalCourse\Database\Query\Restriction\LanguageRestriction;
use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Model\EducationalCategory;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CourseCategoryRepository
{
    protected ConnectionPool $connection;

    public function __construct()
    {
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @throws CategoryExistException
     */
    public function findByType(int $pageId, Category $type): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $statement = $queryBuilder
            ->select(
                'sys_category.uid',
                'sys_category.type',
                'sys_category.title'
            )
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
            );
        $attributes = new CategoryCollection();

        foreach ($statement->executeQuery()->fetchAllAssociative() as $attribute) {
            $attributes->attach(
                new EducationalCategory(
                    $attribute['uid'],
                    Category::cast($attribute['type']),
                    $attribute['title']
                )
            );
        }
        return $attributes;
    }

    /**
     * @throws DBALException
     * @throws Exception
     * @throws CategoryExistException
     */
    public function findAllByPageId(int $pageId): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $statement = $queryBuilder
            ->select(
                'sys_category.uid',
                'sys_category.parent',
                'sys_category.type',
                'sys_category.title'
            )
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
                $queryBuilder->expr()->in(
                    'sys_category.type',
                    array_map(function (string $value) {
                        return '\'' . $value . '\'';
                    }, array_values(Category::getConstants()))
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
            );

        $attributes = new CategoryCollection();

        foreach ($statement->executeQuery()->fetchAllAssociative() as $row) {
            $attributes->attach(
                new EducationalCategory(
                    $row['uid'],
                    $row['parent'],
                    Category::cast($row['type']),
                    $row['title']
                )
            );
        }
        return $attributes;
    }

    public function findAll(): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $statement = $queryBuilder
            ->select(
                'sys_category.uid',
                'sys_category.parent',
                'sys_category.type',
                'sys_category.title'
            )
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->in(
                    'sys_category.type',
                    array_map(function (string $value) {
                        return '\'' . $value . '\'';
                    }, array_values(Category::getConstants()))
                ),
            );

        $attributes = new CategoryCollection();

        foreach ($statement->executeQuery()->fetchAllAssociative() as $row) {
            $attributes->attach(
                new EducationalCategory(
                    $row['uid'],
                    $row['parent'],
                    Category::cast($row['type']),
                    $row['title']
                )
            );
        }
        return $attributes;
    }

    public function getByDatabaseFields(
        int $uid,
        string $table = 'tt_content',
        string $field = 'pi_flexform'
    ): CategoryCollection {
        $queryBuilder = $this->buildQueryBuilder();

        $statement = $queryBuilder
            ->select(
                'sys_category.uid',
                'sys_category.parent',
                'sys_category.type',
                'sys_category.title'
            )
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
            ->groupBy('sys_category.uid')
            ->where(
                $queryBuilder->expr()->in(
                    'sys_category.type',
                    array_map(function (string $value) {
                        return '\'' . $value . '\'';
                    }, array_values(Category::getConstants()))
                ),
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
            );

        $attributes = new CategoryCollection();

        foreach ($statement->executeQuery()->fetchAllAssociative() as $row) {
            $attributes->attach(
                new EducationalCategory(
                    (int)$row['uid'],
                    (int)$row['parent'],
                    Category::cast($row['type']),
                    $row['title']
                )
            );
        }
        return $attributes;
    }

    /**
     * @param array<int>|null $idList
     * @return EducationalCategory[]
     */
    public function findByUidListAndType(array $idList, Category $categoryType): ?array
    {
        $queryBuilder = $this->buildQueryBuilder();

        $queryBuilder->select(
            'sys_category.uid',
            'sys_category.parent',
            'sys_category.type',
            'sys_category.title'
        )
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
            $category[] = new EducationalCategory(
                (int)$row['uid'],
                (int)$row['parent'],
                Category::cast($row['type']),
                $row['title']
            );
        }

        return $category;
    }

    /**
     * @param CategoryCollection $applicableCategories
     * @throws CategoryExistException
     * @throws DBALException
     * @throws Exception
     */
    public function findAllWitDisabledStatus(CategoryCollection $applicableCategories): CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $statement = $queryBuilder
            ->select(
                'sys_category.uid',
                'sys_category.parent',
                'sys_category.type',
                'sys_category.title'
            )
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->in(
                    'sys_category.type',
                    array_map(function (string $value) {
                        return '\'' . $value . '\'';
                    }, array_values(Category::getConstants()))
                ),
            );

        $categories = new CategoryCollection();

        foreach ($statement->executeQuery()->fetchAllAssociative() as $attribute) {
            if (in_array($attribute['type'], Category::getConstants())) {
                $category = new EducationalCategory(
                    $attribute['uid'],
                    $attribute['parent'],
                    Category::cast($attribute['type']),
                    $attribute['title']
                );

                $category->setDisabled(!$applicableCategories->exist($category));

                $categories->attach($category);
            }
        }
        return $categories;
    }

    private function buildQueryBuilder(string $tableName = 'sys_category'): QueryBuilder
    {
        $queryBuilder = $this->connection->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(LanguageRestriction::class));

        return $queryBuilder;
    }
}
