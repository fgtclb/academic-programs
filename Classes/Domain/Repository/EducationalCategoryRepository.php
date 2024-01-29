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
 * ToDo: Rename to "CategoryRepository"
 */
class EducationalCategoryRepository
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

    private ConnectionPool $connection;

    public function __construct(
        ?ConnectionPool $connectionPool = null
    ) {
        $this->connection = $connectionPool ?? GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * @param Category $categoryType
     * @return EducationalCategory[]
     */
    public function findCategoryByType(Category $categoryType): array
    {
        $queryBuilder = $this->buildQueryBuilder();
        $result = $queryBuilder->select(...$this->categoryFieldList)
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter((string)$categoryType))
            )->executeQuery();

        if ($result->rowCount() === 0) {
            return [];
        }

        $category = [];
        foreach ($result->fetchAllAssociative() as $record) {
            $category[] = $this->categoryObjectMapping($record);
        }

        return $category;
    }

    public function findChildren(int $uid): ?CategoryCollection
    {
        $queryBuilder = $this->buildQueryBuilder();

        $statement = $queryBuilder
            ->select(...$this->categoryFieldList)
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq(
                    'parent',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $this->categoryTypeCondition($queryBuilder)
            );

        $result = $statement->executeQuery();

        if ($result->rowCount() === 0) {
            return null;
        }

        $children = new CategoryCollection();

        foreach ($result->fetchAllAssociative() as $child) {
            $category = $this->categoryObjectMapping($child);
            $children->attach($category);
        }
        return $children;
    }

    public function findParent(int $parent): ?EducationalCategory
    {
        $queryBuilder = $this->buildQueryBuilder();

        $result = $queryBuilder
            ->select(...$this->categoryFieldList)
            ->from('sys_category')
            ->where(
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

        $record = $result->fetchAssociative();

        return $this->categoryObjectMapping($record);
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
