<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Repository;

use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Domain\Model\Program;
use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Program>
 */
class ProgramRepository extends Repository
{
    /**
     * @return QueryResult<Program>
     * @throws InvalidEnumerationValueException
     */
    public function findByDemand(ProgramDemand $demand): QueryResult
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $constraints = [];
        $constraints[] = $query->equals('doktype', PageTypes::TYPE_ACADEMIC_PROGRAM);
        if (!empty($demand->getPages())) {
            $constraints[] = $query->in('pid', $demand->getPages());
        }
        if ($demand->getFilterCollection() !== null) {
            foreach ($demand->getFilterCollection()->getFilterCategories() as $category) {
                $constraints[] = $query->contains('categories', $category->getUid());
            }
        }
        // The method signature of logicalAnd and logicalOr has changed in TYPO3 v12
        // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-96044-HardenMethodSignatureOfLogicalAndAndLogicalOr.html
        $query->matching(
            $query->logicalAnd(...array_values($constraints))
        );
        $query->setOrderings(
            [
                $demand->getSortingField() => strtoupper($demand->getSortingDirection()),
            ]
        );
        return $query->execute();
    }
}
