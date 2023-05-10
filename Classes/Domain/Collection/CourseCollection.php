<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Collection;

use Countable;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\EducationalCourse\Domain\Enumeration\Page;
use FGTCLB\EducationalCourse\Domain\Model\Course;
use FGTCLB\EducationalCourse\Domain\Model\Dto\CourseFilter;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
use Iterator;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @implements Iterator<int, Course>
 */
final class CourseCollection implements Iterator, Countable
{
    /**
     * @var Course[]
     */
    private array $courses = [];

    private function __construct()
    {
    }

    /**
     * @throws CategoryExistException
     * @throws Exception
     * @throws DBALException
     */
    public static function getAll(): CourseCollection
    {
        $courseCollection = new self();
        $coursePages = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->select(
                ['uid'],
                'pages',
                [
                    'doktype' => Page::TYPE_EDUCATIONAL_COURSE,
                ]
            )
            ->fetchAllAssociative();

        foreach ($coursePages as $coursePage) {
            $course = new Course($coursePage['uid']);
            $courseCollection->attach($course);
        }
        return $courseCollection;
    }

    /**
     * @throws CategoryExistException
     * @throws Exception
     * @throws DBALException
     */
    public static function getByFilter(?CourseFilter $filter = null): CourseCollection
    {
        $courseCollection = new self();

        $db = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $statement = $db
            ->select('pages.uid')
            ->from('pages')
            ->where(
                $db->expr()->eq(
                    'doktype',
                    $db->createNamedParameter(
                        Page::TYPE_EDUCATIONAL_COURSE,
                        Connection::PARAM_INT
                    )
                )
            );
        if ($filter !== null) {
            $andWhere = [];
            $orWhere = [];
            foreach ($filter->getFilterCategories() as $filterCategory) {
                if (
                    ($children = $filterCategory->getChildren()) !== null
                    && $children->count() > 0
                ) {
                    $orWhere[$filterCategory->getUid()] = [];
                    foreach ($children as $childCategory) {
                        $orWhere[$filterCategory->getUid()][] = $childCategory->getUid();
                    }
                } else {
                    $andWhere[] = $filterCategory->getUid();
                }
            }
            if (count($andWhere) > 0) {
                $statement->join(
                    'pages',
                    'sys_category_record_mm',
                    'mm',
                    'mm.uid_foreign=pages.uid'
                );
                $addWhere = [];
                if (count($orWhere) > 0) {
                    foreach ($orWhere as $parent => $children) {
                        $addOrWhere = [];
                        foreach ($children as $child) {
                            $addOrWhere[] = $db->expr()->eq('mm.uid_local', $db->createNamedParameter($child, Connection::PARAM_INT));
                        }
                        if (count($addOrWhere) > 0) {
                            $addWhere[] = $db->expr()->or(...$addOrWhere);
                        }
                    }
                }
                $addWhere[] = $db->expr()->in('mm.uid_local', $andWhere);
                $statement->andWhere(
                    ...$addWhere
                );
            }
        }
        $sql = $statement->getSQL();
        $params = $statement->getParameters();
        $coursePages = $statement->executeQuery()->fetchAllAssociative();

        foreach ($coursePages as $coursePage) {
            $course = new Course($coursePage['uid']);
            $courseCollection->attach($course);
        }

        return $courseCollection;
    }

    private function attach(Course $course): void
    {
        $this->courses[] = $course;
    }

    public function current(): Course|false
    {
        return current($this->courses);
    }

    public function next(): void
    {
        next($this->courses);
    }

    public function key(): string|int|null
    {
        return key($this->courses);
    }

    public function valid(): bool
    {
        return current($this->courses) !== false;
    }

    public function rewind(): void
    {
        reset($this->courses);
    }

    public function count(): int
    {
        return count($this->courses);
    }
}
