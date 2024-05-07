<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Model;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\EducationalCourse\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Collection\FileReferenceCollection;
use FGTCLB\EducationalCourse\Enumeration\PageTypes;
use FGTCLB\EducationalCourse\Domain\Repository\CategoryRepository;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
use InvalidArgumentException;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Course
{
    protected int $uid;

    protected string $title;

    protected string $subtitle;

    protected string $abstract;

    protected string $jobProfile;

    protected string $performanceScope;

    protected string $prerequisites;

    protected CategoryCollection $categories;

    protected FileReferenceCollection $media;

    /**
     * @throws CategoryExistException
     * @throws Exception
     * @throws DBALException
     * @throws FileDoesNotExistException
     * @throws InvalidArgumentException
     */
    public function __construct(int $databaseId)
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $page = $pageRepository->getPage($databaseId);
        if (count($page) === 0) {
            throw new InvalidArgumentException(
                'Page not found',
                1683811343841
            );
        }
        $this->uid = $page['uid'];
        $this->title = $page['title'] ?? '';
        $this->subtitle = $page['subtitle'] ?? '';
        $this->abstract = $page['abstract'] ?? '';
        $this->jobProfile = $page['job_profile'] ?? '';
        $this->performanceScope = $page['performance_scope'] ?? '';
        $this->prerequisites = $page['prerequisites'] ?? '';

        $this->categories = GeneralUtility::makeInstance(CategoryRepository::class)
            ->findAllByPageId($databaseId);

        $this->media = self::loadMedia($this->uid);
    }

    /**
     * @throws Exception
     * @throws DBALException
     * @throws CategoryExistException
     * @throws FileDoesNotExistException
     */
    public static function loadFromLink(int $linkId): Course
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $pageToResolve = $pageRepository->getPage($linkId);
        $originalPage = match ($pageToResolve['doktype']) {
            PageRepository::DOKTYPE_SHORTCUT => $pageRepository->resolveShortcutPage($pageToResolve),
            // @todo Allow Mountpoints
            //PageRepository::DOKTYPE_MOUNTPOINT => $pageRepository->getMountPointInfo($linkId, $pageToResolve),
            default => throw new InvalidArgumentException(
                'Calling with doktypes other than 4 or 7 not allowed',
                1685532706120
            ),
        };
        if ($originalPage['doktype'] !== PageTypes::TYPE_EDUCATIONAL_COURSE) {
            throw new \RuntimeException(
                sprintf('Page "%d" has no Course page linked', $linkId),
                1685532982084
            );
        }

        return new self($originalPage['uid']);
    }

    /**
     * @throws FileDoesNotExistException
     * @throws Exception
     */
    protected static function loadMedia(int $pageId): FileReferenceCollection
    {
        return FileReferenceCollection::getCollectionByPageIdAndField($pageId, 'media');
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    /**
     * @return string
     */
    public function getAbstract(): string
    {
        return $this->abstract;
    }

    /**
     * @return CategoryCollection
     */
    public function getCategories(): CategoryCollection
    {
        return $this->categories;
    }

    /**
     * @return FileReferenceCollection
     */
    public function getMedia(): FileReferenceCollection
    {
        return $this->media;
    }

    /**
     * @return string
     */
    public function getJobProfile(): string
    {
        return $this->jobProfile;
    }

    /**
     * @return string
     */
    public function getPerformanceScope(): string
    {
        return $this->performanceScope;
    }

    /**
     * @return string
     */
    public function getPrerequisites(): string
    {
        return $this->prerequisites;
    }

    /**
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }
}
