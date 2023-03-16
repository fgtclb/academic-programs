<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CourseCategoryRepository
{
    protected QueryBuilder $connection;
    public function __construct()
    {
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category');
    }

    /**
     * @return array<int|string, mixed>
     * @throws DBALException
     * @throws Exception
     */
    public function findByType(int $pageId, Category $type): array
    {
        $statement = $this->connection->select('sys_category.*')
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
                $this->connection->expr()->eq(
                    'sys_category.type',
                    $this->connection->createNamedParameter((string)$type)
                ),
                $this->connection->expr()->eq(
                    'mm.tablenames',
                    $this->connection->createNamedParameter('pages')
                ),
                $this->connection->expr()->eq(
                    'mm.fieldname',
                    $this->connection->createNamedParameter('categories')
                ),
                $this->connection->expr()->eq(
                    'pages.uid',
                    $this->connection->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
            );
        return $statement->executeQuery()->fetchAllAssociative() ?: [];
    }

    /**
     * @return array{
     *     applicationPeriod: array<int|string, mixed>,
     *     beginCourse: array<int|string, mixed>,
     *     costs: array<int|string, mixed>,
     *     degree: array<int|string, mixed>,
     *     standardPeriod: array<int|string, mixed>,
     *     courseType: array<int|string, mixed>,
     *     teachingLanguage: array<int|string, mixed>
     * }|array<int|string, mixed>
     * @throws DBALException
     * @throws Exception
     */
    public function findAllByPageId(int $pageId): array
    {
        $statement = $this->connection->select('sys_category.*')
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
                $this->connection->expr()->neq(
                    'sys_category.type',
                    $this->connection->createNamedParameter('')
                ),
                $this->connection->expr()->eq(
                    'mm.tablenames',
                    $this->connection->createNamedParameter('pages')
                ),
                $this->connection->expr()->eq(
                    'mm.fieldname',
                    $this->connection->createNamedParameter('categories')
                ),
                $this->connection->expr()->eq(
                    'pages.uid',
                    $this->connection->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
            );

        $attributes = [];

        foreach (Category::getConstants() as $type) {
            $attributes[$type] = [];
        }

        foreach ($statement->executeQuery()->fetchAllAssociative() as $row) {
            $attributes[$row['type']][] = $row;
        }
        return $attributes;
    }
}
