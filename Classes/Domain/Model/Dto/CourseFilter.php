<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Model\Dto;

use ArrayAccess;
use Countable;
use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Model\EducationalCategory;
use InvalidArgumentException;
use Iterator;

final class CourseFilter implements ArrayAccess
{
    protected CategoryCollection $filterCategories;

    private function __construct()
    {
    }

    public static function createByCategoryCollection(CategoryCollection $categoryCollection): CourseFilter
    {
        $filter = new self();
        $filter->filterCategories = $categoryCollection;
        return $filter;
    }

    public static function createEmpty(): CourseFilter
    {
        $filter = new self();
        $filter->filterCategories = new CategoryCollection();
        return $filter;
    }

    public function offsetExists(mixed $offset): bool
    {
        try {
            $this->filterCategories->getAttributesByType($offset);
        } catch (InvalidArgumentException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param mixed $offset
     * @return Iterator<int, EducationalCategory>|false|Countable
     */
    public function offsetGet(mixed $offset): Iterator|false|Countable
    {
        try {
            $attributes = $this->filterCategories->getAttributesByType($offset);
        } catch (InvalidArgumentException $e) {
            return false;
        }
        return $attributes;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new InvalidArgumentException(
            'Method should never be called',
            1683633632593
        );
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \http\Exception\InvalidArgumentException(
            'Method should never be called',
            1683633656658
        );
    }

    /**
     * @return CategoryCollection
     */
    public function getFilterCategories(): CategoryCollection
    {
        return $this->filterCategories;
    }
}
