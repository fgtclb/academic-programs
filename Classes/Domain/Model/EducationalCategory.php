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

    protected Category $type;

    protected string $title;

    protected ?CategoryCollection $children = null;

    public function __construct(
        int $uid,
        Category $type,
        string $title
    ) {
        $this->uid = $uid;
        $this->type = $type;
        $this->title = $title;
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
}
