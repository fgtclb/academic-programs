<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Model\Dto;

use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;

class CourseFilter
{
    protected CategoryCollection $selectedCategories;

    public function __construct()
    {
        $this->selectedCategories = new CategoryCollection();
    }
}
