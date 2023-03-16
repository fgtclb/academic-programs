<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Model\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Model\EducationalCategory;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
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
     * @throws DBALException
     * @throws Exception
     * @throws CategoryExistException
     */
    public function findByType(int $pageId, Category $type): CategoryCollection
    {
        $statement = $this->connection->select(
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
        $statement = $this->connection->select(
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

        $attributes = new CategoryCollection();

        foreach ($statement->executeQuery()->fetchAllAssociative() as $row) {
            $attributes->attach(
                new EducationalCategory(
                    $row['uid'],
                    Category::cast($row['type']),
                    $row['title']
                )
            );
        }
        return $attributes;
    }
}
