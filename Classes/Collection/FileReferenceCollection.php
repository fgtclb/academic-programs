<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Collection;

use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @implements \Iterator<int, FileReference>
 */
final class FileReferenceCollection implements \Countable, \Iterator
{
    /**
     * @var FileReference[]
     */
    protected array $fileReferences = [];

    /**
     * @throws FileDoesNotExistException
     * @throws Exception
     */
    public static function getCollectionByPageIdAndField(
        int $pageId,
        string $field
    ): FileReferenceCollection {
        $fileReferenceCollection = new self();

        $queryBuilder = self::buildQueryBuilder();
        $references = $queryBuilder
            ->select('sys_file_reference.*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_file_reference.uid_foreign',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_file_reference.tablenames',
                    $queryBuilder->createNamedParameter('pages')
                ),
                $queryBuilder->expr()->eq(
                    'sys_file_reference.fieldname',
                    $queryBuilder->createNamedParameter($field)
                )
            )
            // The order the editor arranged the images in - `sorting_foreign` is the
            // per-relation counterpart of a `sortby` column. Without an ORDER BY the
            // gallery order belonged to the DBMS and could differ between two renders
            // of the same page on PostgreSQL (ACE-491). `uid` settles ties.
            ->orderBy('sys_file_reference.sorting_foreign', 'ASC')
            ->addOrderBy('sys_file_reference.uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($references as $reference) {
            $fileReference = new FileReference($reference);
            $fileReferenceCollection->attach($fileReference);
        }
        return $fileReferenceCollection;
    }

    private function attach(FileReference $reference): void
    {
        $this->fileReferences[] = $reference;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->fileReferences);
    }

    public function current(): FileReference|false
    {
        return current($this->fileReferences);
    }

    public function next(): void
    {
        next($this->fileReferences);
    }

    public function key(): string|int|null
    {
        return key($this->fileReferences);
    }

    public function valid(): bool
    {
        return current($this->fileReferences) !== false;
    }

    public function rewind(): void
    {
        reset($this->fileReferences);
    }

    /**
     * @param string $tableName
     * @return QueryBuilder
     */
    private static function buildQueryBuilder(string $tableName = 'sys_file_reference'): QueryBuilder
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
        return $queryBuilder;
    }
}
