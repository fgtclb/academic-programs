<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Domain\Model;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The data of a program page, built from its page record by
 * {@see \FGTCLB\AcademicPrograms\Factory\ProgramDataFactory} and handed to the page template as
 * `program`.
 *
 * @api
 */
class ProgramData implements ProgramFactsSourceInterface
{
    protected int $pid = 0;
    protected int $uid = 0;
    protected int $doktype = 0;
    protected string $title = '';
    protected string $subtitle = '';
    protected string $abstract = '';
    protected int $creditPoints = 0;
    protected string $jobProfile = '';
    protected string $performanceScope = '';
    protected string $prerequisites = '';
    protected string $applicationLink = '';
    protected string $applicationLinkLabel = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void {}

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

    public function setApplicationLink(string $applicationLink): void
    {
        $this->applicationLink = $applicationLink;
    }

    /**
     * The link to the application for the program, as the link field stores it: a
     * `t3://` link or an external URL, for `f:link.typolink`. Empty when the editor set
     * none.
     */
    public function getApplicationLink(): string
    {
        return $this->applicationLink;
    }

    public function setApplicationLinkLabel(string $applicationLinkLabel): void
    {
        $this->applicationLinkLabel = $applicationLinkLabel;
    }

    /**
     * The label of the application link. Empty when the editor set none, the page then
     * renders a translated default.
     */
    public function getApplicationLinkLabel(): string
    {
        return $this->applicationLinkLabel;
    }

    public function getCategories(): ?CategoryCollection
    {
        return $this->getCategoryCollection();
    }

    public function getCategoryCollection(): CategoryCollection
    {
        return GeneralUtility::makeInstance(CategoryRepository::class)->findByGroupAndPageId('programs', $this->uid);
    }
}
