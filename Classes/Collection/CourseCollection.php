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
    public function getAll(): CourseCollection
    {
        $statement = $this->buildDefaultQuery();
        $coursePages = $statement->executeQuery()->fetchAllAssociative();

        return $this->buildCollection($coursePages);
    }

    /**
     * @param CourseDemand $demand
     * @param int[] $fromPid
     * @return CourseCollection
     */
    public function getByDemand(
        CourseDemand $demand,
        array $fromPid = []
    ): CourseCollection {
        $statement = $this->buildDefaultQuery($demand, $fromPid);
        $coursePages = $statement->executeQuery()->fetchAllAssociative();

        return $this->buildCollection($coursePages);
    }

    /**
     * @param array<int|string, mixed> $coursePages
     * @return CourseCollection
     */
    private function buildCollection(array $coursePages): CourseCollection
    {
        $courseCollection = new self();
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
            $courseCollection->attach($course);
        }

        return $courseCollection;
    }

    /**
     * @param ?CourseDemand $demand
     * @param int[] $fromPid
     * @return QueryBuilder
     */
    private function buildDefaultQuery(
        ?CourseDemand $demand = null,
        array $fromPid = []
    ): QueryBuilder {
        if ($demand === null) {
            /** @var CourseDemand $demand */
            $demand = GeneralUtility::makeInstance(CourseDemand::class);
        }

        $queryBuilder = $this->buildQueryBuilder();

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

        $andWhere = [];
        $orWhere = [];

        foreach ($demand->getFilterCollection()->getFilterCategories() as $filterCategory) {
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

        if (count($andWhere) > 0 || count($orWhere) > 0) {
            $statement->join(
                'pages',
                'sys_category_record_mm',
                'mm',
                'mm.uid_foreign=pages.uid'
            )
                ->addSelectLiteral(
                    'group_concat(uid_local) as filtercategories'
                )
                ->groupBy('pages.uid');
            $addWhere = [];
            if (count($orWhere) > 0) {
                foreach ($orWhere as $parent => $children) {
                    $addOrWhere = [];
                    foreach ($children as $child) {
                        $addOrWhere[] = $statement->expr()->inSet('filtercategories', $statement->createNamedParameter($child, Connection::PARAM_INT));
                    }
                    if (count($addOrWhere) > 0) {
                        $addWhere[] = $statement->expr()->or(...$addOrWhere);
                    }
                }
            }
            foreach ($andWhere as $value) {
                $addWhere[] = $statement->expr()->inSet('filtercategories', $statement->createNamedParameter($value, Connection::PARAM_INT));
            }
            $statement->having(
                ...$addWhere
            );
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
    private function buildQueryBuilder(string $tableName = 'pages'): QueryBuilder
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
        return $queryBuilder;
    }
}
