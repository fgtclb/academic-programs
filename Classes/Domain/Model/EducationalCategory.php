<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Model;

use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Repository\EducationalCategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EducationalCategory
{
    protected int $uid;

    protected int $parentId;

    protected ?Category $type;

    protected string $title;

    protected bool $disabled = false;

    protected ?CategoryCollection $children = null;

    public function __construct(
        int $uid,
        int $parentId,
        string $title,
        string $type = '',
        bool $disabled = false
    ) {
        $this->uid = $uid;
        $this->parentId = $parentId;

        if ($type === 'default' || $type === '') {
            $type = null;
        } else {
            $type = Category::cast($type);
        }

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

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function getType(): ?Category
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

    public function hasParent(): bool
    {
        return $this->parentId > 0;
    }

    public function getParent(): ?EducationalCategory
    {
        return GeneralUtility::makeInstance(EducationalCategoryRepository::class)
            ->findParent($this->parentId);
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
