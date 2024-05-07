<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Model;

use FGTCLB\EducationalCourse\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Repository\CategoryRepository;
use FGTCLB\EducationalCourse\Enumeration\CategoryTypes;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Category
{
    protected int $uid;

    protected int $parentId;

    protected ?CategoryTypes $type;

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
            $type = CategoryTypes::cast($type);
        }

        $this->type = $type;
        $this->title = $title;
        $this->disabled = $disabled;
        $this->children = GeneralUtility::makeInstance(CategoryRepository::class)
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

    public function getType(): ?CategoryTypes
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

    public function getParent(): ?Category
    {
        if (!$this->hasParent()) {
            return null;
        }

        return GeneralUtility::makeInstance(CategoryRepository::class)
            ->findParent($this->parentId);
    }

    public function isRoot(): bool
    {
        $parent = $this->getParent();
        if ($parent === null
            || (string)$this->type !== (string)$parent->getType()
        ) {
            return true;
        }
        return false;
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
