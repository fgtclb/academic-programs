<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Model;

use FGTCLB\AcademicPrograms\Collection\CategoryCollection;
use FGTCLB\AcademicPrograms\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Program extends AbstractEntity
{
    protected int $doktype;

    protected string $title = '';

    protected string $subtitle = '';

    protected string $abstract = '';

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

    public function getAttributes(): ?CategoryCollection
    {
        $attributes = GeneralUtility::makeInstance(CategoryCollection::class);
        if ($this->uid !== null) {
            $attributes = GeneralUtility::makeInstance(CategoryRepository::class)->findAllByPageId($this->uid);
        }
        return $attributes;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getMedia(): ObjectStorage
    {
        return $this->media;
    }
}
