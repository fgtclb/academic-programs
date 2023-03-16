<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Model;

use FGTCLB\EducationalCourse\Domain\Enumeration\Category;

class EducationalCategory
{
    protected int $uid;

    protected Category $type;

    protected string $title;

    public function __construct(
        int $uid,
        Category $type,
        string $title
    ) {
        $this->uid = $uid;
        $this->type = $type;
        $this->title = $title;
    }
    /**
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * @return Category
     */
    public function getType(): Category
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}
