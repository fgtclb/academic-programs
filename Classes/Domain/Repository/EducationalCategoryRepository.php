<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Model\EducationalCategory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EducationalCategoryRepository
{
    public function findChildren(int $uid): ?CategoryCollection
    {
        $db = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category');
        $statement = $db
            ->select('uid', 'title', 'type')
            ->from('sys_category')
            ->where(
                $db->expr()->eq('parent', $db->createNamedParameter($uid, Connection::PARAM_INT))
            );
        $children = new CategoryCollection();

        try {
            $result = $statement->executeQuery()->fetchAllAssociative();
        } catch (DBALException|Exception $e) {
            return null;
        }

        foreach ($result as $child) {
            $children->attach(
                new EducationalCategory(
                    $child['uid'],
                    Category::cast($child['type']),
                    $child['title']
                )
            );
        }
        return $children;
    }
}
