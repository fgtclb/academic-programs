<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Collection;

use Countable;
use FGTCLB\EducationalCourse\Domain\Model\Course;
use FGTCLB\EducationalCourse\Domain\Model\Dto\CourseDemand;
use FGTCLB\EducationalCourse\Enumeration\PageTypes;
use FGTCLB\EducationalCourse\Utility\PagesUtility;
use InvalidArgumentException;
use Iterator;
use RuntimeException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
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

    /**
     * @return CourseCollection
     */
    public static function getAll(): CourseCollection
    {
        $statement = self::buildDefaultQuery();
        $coursePages = $statement->executeQuery()->fetchAllAssociative();

        return self::buildCollection($coursePages);
    }

    /**
     * @param CourseDemand $demand
     * @param int[] $fromPid
     * @return CourseCollection
     */
    public static function getByDemand(
        CourseDemand $demand,
        array $fromPid = []
    ): CourseCollection {
        $statement = self::buildDefaultQuery($demand, $fromPid);
        $coursePages = $statement->executeQuery()->fetchAllAssociative();

        return self::buildCollection($coursePages, $demand);
    }

    /**
     * @param array<int|string, mixed> $coursePages
     * @param ?CourseDemand $demand
     * @return CourseCollection
     */
    private static function buildCollection(
        array $coursePages,
        ?CourseDemand $demand = null
    ): CourseCollection {
        $courseArray = [];
        foreach ($coursePages as $coursePage) {
            if ($coursePage['doktype'] !== PageTypes::TYPE_EDUCATIONAL_COURSE) {
                try {
                    $course = Course::loadFromLink($coursePage['uid']);
                } catch (InvalidArgumentException|RuntimeException $exception) {
                    // Silent catch, avoid logging here, as multiple doktypes can be excluded this way
                    continue;
                }
            } else {
                $course = new Course($coursePage['uid']);
            }
            $courseArray[] = $course;
        }

        if ($demand !== null && $demand->getSortingField() === 'title') {
            usort($courseArray, function ($a, $b) {
                return strcoll($a->getTitle(), $b->getTitle());
            });
        }

        $courseCollection = new self();
        foreach ($courseArray as $course) {
            $courseCollection->attach($course);
        }

        return $courseCollection;
    }

    /**
     * @param ?CourseDemand $demand
     * @param int[] $fromPid
     * @return QueryBuilder
     */
    private static function buildDefaultQuery(
        ?CourseDemand $demand = null,
        array $fromPid = []
    ): QueryBuilder {
        if ($demand === null) {
            /** @var CourseDemand $demand */
            $demand = GeneralUtility::makeInstance(CourseDemand::class);
        }

        $queryBuilder = self::buildQueryBuilder();

        $doktypes = $queryBuilder->expr()->or(
            $queryBuilder->expr()->eq(
                'doktype',
                $queryBuilder->createNamedParameter(
                    PageTypes::TYPE_EDUCATIONAL_COURSE,
                    Connection::PARAM_INT
                )
            ),
            $queryBuilder->expr()->eq(
                'doktype',
                $queryBuilder->createNamedParameter(
                    PageRepository::DOKTYPE_SHORTCUT,
                    Connection::PARAM_INT
                )
            )
        );

        $statement = $queryBuilder
            ->select('pages.uid', 'pages.doktype')
            ->from('pages')
            ->where($doktypes)
            ->orderBy(sprintf('pages.%s', $demand->getSortingField()), $demand->getSortingDirection());

        $typeSortedCategories = [];
        foreach ($demand->getFilterCollection()->getFilterCategories() as $category) {
            $typeSortedCategories[(string)$category->getType()][] = $category->getUid();
            if (
                ($children = $category->getChildren()) !== null
                && $children->count() > 0
            ) {
                foreach ($children as $childCategory) {
                    $typeSortedCategories[(string)$category->getType()][] = $childCategory->getUid();
                }
            }
        }

        if (count($typeSortedCategories)) {
            $i = 1;
            foreach ($typeSortedCategories as $categoryUids) {
                $statement->andWhere(
                    $statement->join(
                        'pages',
                        'sys_category_record_mm',
                        'mm' . $i,
                        'mm' . $i . '.uid_foreign=pages.uid'
                    )
                    ->groupBy('pages.uid')
                    ->expr()
                    ->in('mm' . $i . '.uid_local', $categoryUids)
                );
                $i++;
            }
        }

        if (count($fromPid)) {
            $searchPids = PagesUtility::getPagesRecursively($fromPid);
            if (count($searchPids)) {
                $statement->andWhere(
                    $statement->expr()->in('uid', $searchPids)
                );
            }
        }

        return $statement;
    }

    /**
     * @return CategoryCollection
     */
    public function getApplicableCategories(): CategoryCollection
    {
        /** @var CategoryCollection $applicableCategories */
        $applicableCategories = GeneralUtility::makeInstance(CategoryCollection::class);
        foreach ($this->courses as $course) {
            foreach ($course->getCategories() as $category) {
                $applicableCategories->attach($category);
            }
        }
        return $applicableCategories;
    }

    /**
     * @param string $tableName
     * @return QueryBuilder
     */
    private static function buildQueryBuilder(string $tableName = 'pages'): QueryBuilder
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
        return $queryBuilder;
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
