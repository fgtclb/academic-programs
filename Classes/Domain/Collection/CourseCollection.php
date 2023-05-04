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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
