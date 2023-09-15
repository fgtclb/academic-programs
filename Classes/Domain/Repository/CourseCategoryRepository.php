<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Model\EducationalCategory;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
        $db = $this->connection
            ->getQueryBuilderForTable('sys_category');
        $statement = $db
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
                $db->expr()->eq(
                    'sys_category.type',
                    $db->createNamedParameter((string)$type)
                ),
                $db->expr()->eq(
                    'mm.tablenames',
                    $db->createNamedParameter('pages')
                ),
                $db->expr()->eq(
                    'mm.fieldname',
                    $db->createNamedParameter('categories')
                ),
                $db->expr()->eq(
                    'pages.uid',
                    $db->createNamedParameter($pageId, Connection::PARAM_INT)
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
        $db = $this->connection
            ->getQueryBuilderForTable('sys_category');
        $statement = $db
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
                $db->expr()->in(
                    'sys_category.type',
                    array_values(Category::getConstants())
                ),
                $db->expr()->eq(
                    'mm.tablenames',
                    $db->createNamedParameter('pages')
                ),
                $db->expr()->eq(
                    'mm.fieldname',
                    $db->createNamedParameter('categories')
                ),
                $db->expr()->eq(
                    'pages.uid',
                    $db->createNamedParameter($pageId, Connection::PARAM_INT)
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

    /**
     * @throws CategoryExistException
     * @throws DBALException
     * @throws Exception
     */
    public function findAll(): CategoryCollection
    {
        $db = $this->connection
            ->getQueryBuilderForTable('sys_category');
        $statement = $db
            ->select(
                'sys_category.uid',
                'sys_category.type',
                'sys_category.title'
            )
            ->from('sys_category')
            ->where(
                $db->expr()->in(
                    'sys_category.type',
                    array_values(Category::getConstants())
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

    public function getByDatabaseFields(
        int $uid,
        string $table = 'tt_content',
        string $field = 'pi_flexform'
    ): CategoryCollection {
        $db = $this->connection
            ->getQueryBuilderForTable('sys_category');
        $statement = $db
            ->select(
                'sys_category.uid',
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
                $db->expr()->in(
                    'sys_category.type',
                    array_values(Category::getConstants())
                ),
                $db->expr()->eq(
                    'sys_category_record_mm.tablenames',
                    $db->createNamedParameter($table)
                ),
                $db->expr()->eq(
                    'sys_category_record_mm.fieldname',
                    $db->createNamedParameter($field)
                ),
                $db->expr()->eq(
                    'sys_category_record_mm.uid_foreign',
                    $db->createNamedParameter($uid, Connection::PARAM_INT)
                )
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
