<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Model\Dto;

use FGTCLB\EducationalCourse\Collection\FilterCollection;

class CourseDemand
{
    public const SORTING_FIELDS = [
        'title',
        'lastUpdated',
        'sorting',
    ];

    public const DEFAULT_SORTING_FIELD = 'title';

    public const SORTING_DIRECTIONS = [
        'asc',
        'desc',
    ];

    public const DEFAULT_SORTING_DIRECTION = 'asc';

    /**
     * @var string
     */
    protected string $sortingField = self::DEFAULT_SORTING_FIELD;

    /**
     * @var string
     */
    protected string $sortingDirection = self::DEFAULT_SORTING_DIRECTION;

    public function __construct(
        protected FilterCollection $filterCollection
    ) {}

    /**
     * @return array<string>
     */
    public function getSortingDirectionOptions(): array
    {
        $options = [];
        foreach (self::SORTING_DIRECTIONS as $option) {
            $options[$option] = $option;
        }
        return $options;
    }

    /**
     * @return array<string>
     */
    public function getSortingFieldOptions(): array
    {
        $options = [];
        foreach (self::SORTING_FIELDS as $option) {
            $options[$option] = $option;
        }
        return $options;
    }

    public function setSortingField(string $sortingField): void
    {
        if (in_array($sortingField, self::SORTING_FIELDS)) {
            $this->sortingField = $sortingField;
        } else {
            $sortingField = self::DEFAULT_SORTING_FIELD;
        }
    }

    public function getSortingField(): string
    {
        return $this->sortingField;
    }

    public function setSortingDirection(string $sortingDirection): void
    {
        if (in_array($sortingDirection, self::SORTING_DIRECTIONS)) {
            $this->sortingDirection = $sortingDirection;
        } else {
            $sortingDirection = self::DEFAULT_SORTING_DIRECTION;
        }
    }

    public function getSortingDirection(): string
    {
        return $this->sortingDirection;
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
