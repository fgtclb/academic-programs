<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Model;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\GetCategoryCollectionInterface;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Program extends AbstractEntity implements GetCategoryCollectionInterface
{
    protected int $doktype;

    protected string $title = '';

    protected string $subtitle = '';

    protected string $abstract = '';

    protected int $creditPoints = 0;

    protected string $jobProfile = '';

    protected string $performanceScope = '';

    protected string $prerequisites = '';

    protected ?CategoryCollection $attributes = null;

    /** @var ObjectStorage<FileReference> */
    protected $media;

    /**
     * @return int<0, max>|null
     */
    public function getPid(): ?int
    {
        return $this->pid;
    }

    /**
     * @return int<0, max>|null
     */
    public function getUid(): ?int
    {
        return $this->uid;
    }

    public function getDoktype(): int
    {
        return $this->doktype;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function getAbstract(): string
    {
        return $this->abstract;
    }

    public function getCreditPoints(): int
    {
        return $this->creditPoints;
    }

    public function getJobProfile(): string
    {
        return $this->jobProfile;
    }

    public function getPerformanceScope(): string
    {
        return $this->performanceScope;
    }

    public function getPrerequisites(): string
    {
        return $this->prerequisites;
    }

    public function getAttributes(): CategoryCollection
    {
        return $this->attributes ??= GeneralUtility::makeInstance(CategoryRepository::class)
            ->findByGroupAndPageId('programs', (int)$this->getUid());
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getMedia(): ObjectStorage
    {
        return $this->media;
    }

    public function getCategoryCollection(): CategoryCollection
    {
        return $this->getAttributes();
    }
}
