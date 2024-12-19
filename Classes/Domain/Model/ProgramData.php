<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Model;

use FGTCLB\AcademicPrograms\Collection\CategoryCollection;
use FGTCLB\AcademicPrograms\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProgramData
{
    protected int $pid;

    protected int $uid;

    protected int $doktype;

    protected string $title = '';

    protected string $subtitle = '';

    protected string $abstract = '';

    protected int $creditPoints = 0;

    protected string $jobProfile = '';

    protected string $performanceScope = '';

    protected string $prerequisites = '';

    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function setUid(int $uid): void
    {
        $this->uid = $uid;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function setDoktype(int $doktype): void
    {
        $this->doktype = $doktype;
    }

    public function getDoktype(): int
    {
        return $this->doktype;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setSubtitle(string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function setAbstract(string $abstract): void
    {
        $this->abstract = $abstract;
    }

    public function getAbstract(): string
    {
        return $this->abstract;
    }

    public function setCreditPoints(int $creditPoints): void
    {
        $this->creditPoints = $creditPoints;
    }

    public function getCreditPoints(): int
    {
        return $this->creditPoints;
    }

    public function setJobProfile(string $jobProfile): void
    {
        $this->jobProfile = $jobProfile;
    }

    public function getJobProfile(): string
    {
        return $this->jobProfile;
    }

    public function setPerformanceScope(string $performanceScope): void
    {
        $this->performanceScope = $performanceScope;
    }

    public function getPerformanceScope(): string
    {
        return $this->performanceScope;
    }

    public function setPrerequisites(string $prerequisites): void
    {
        $this->prerequisites = $prerequisites;
    }

    public function getPrerequisites(): string
    {
        return $this->prerequisites;
    }

    public function getCategories(): ?CategoryCollection
    {
        return GeneralUtility::makeInstance(CategoryRepository::class)->findAllByPageId($this->uid);
    }
}
