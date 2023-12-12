<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Model;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Repository\EducationalCategoryRepository;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EducationalCategory
{
    protected int $uid;

    protected Category $type;

    protected string $title;

    protected bool $disabled = false;

    protected ?CategoryCollection $children = null;

    /**
     * @throws CategoryExistException
     * @throws DBALException
     * @throws Exception
     */
    public function __construct(
        int $uid,
        Category $type,
        string $title,
        bool $disabled = false
    ) {
        $this->uid = $uid;
        $this->type = $type;
        $this->title = $title;
        $this->disabled = $disabled;
        $this->children = GeneralUtility::makeInstance(EducationalCategoryRepository::class)
            ->findChildren($this->uid);
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getType(): Category
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getChildren(): ?CategoryCollection
    {
        return $this->children;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }
}
