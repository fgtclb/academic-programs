<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Model\Dto;

use FGTCLB\EducationalCourse\Collection\FilterCollection;

class CourseDemand
{
    protected string $sorting = 'asc';

    protected string $sortingField = 'title';

    protected FilterCollection $filterCollection;

    public function setSorting(string $sorting): void
    {
        $this->sorting = $sorting;
    }

    public function getSorting(): string
    {
        return $this->sorting;
    }

    public function setSortingField(string $sortingField): void
    {
        $this->sortingField = $sortingField;
    }

    public function getSortingField(): string
    {
        return $this->sortingField;
    }

    public function getFilterCollection(): FilterCollection
    {
        return $this->filterCollection;
    }

    public function setFilterCollection(FilterCollection $filterCollection): void
    {
        $this->filterCollection = $filterCollection;
    }
}
