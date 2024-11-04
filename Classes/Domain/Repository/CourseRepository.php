<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Repository;

use FGTCLB\AcademicPrograms\Domain\Model\Course;
use FGTCLB\AcademicPrograms\Domain\Model\Dto\CourseDemand;
use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Course>
 */
class CourseRepository extends Repository
{
    /**
     * @return QueryResult<Course>
     * @throws InvalidEnumerationValueException
     */
    public function findByDemand(CourseDemand $demand): QueryResult
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $constraints = [];
        $constraints[] = $query->equals('doktype', PageTypes::TYPE_EDUCATIONAL_COURSE);

        if (!empty($demand->getPages())) {
            $constraints[] = $query->in('pid', $demand->getPages());
        }

        if ($demand->getFilterCollection() !== null) {
            foreach ($demand->getFilterCollection()->getFilterCategories() as $category) {
                $constraints[] = $query->contains('categories', $category->getUid());
            }
        }

        $query->matching(
            $query->logicalAnd($constraints)
        );

        $query->setOrderings(
            [
                $demand->getSortingField() => strtoupper($demand->getSortingDirection()),
            ]
        );

        return $query->execute();
    }
}
