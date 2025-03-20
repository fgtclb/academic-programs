<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Collection;

use ArrayAccess;
use FGTCLB\AcademicPrograms\Domain\Model\Category;

/**
 * @implements ArrayAccess<string, Category[]>
 */
class FilterCollection implements \ArrayAccess, \Stringable
{
    protected CategoryCollection $filterCategories;

    public function __construct()
    {
        $this->filterCategories = new CategoryCollection();
    }

    public static function createByCategoryCollection(CategoryCollection $filterCategories): FilterCollection
    {
        $filter = new self();
        $filter->filterCategories = $filterCategories;
        return $filter;
    }

    public static function resetCollection(): FilterCollection
    {
        $filter = new self();
        $filter->filterCategories = new CategoryCollection();
        return $filter;
    }

    public function offsetExists(mixed $offset): bool
    {
        try {
            $this->filterCategories->getCategoriesByTypeName($offset);
        } catch (\InvalidArgumentException) {
            return false;
        }
        return true;
    }

    /**
     * @return array<int, Category>|false
     */
    public function offsetGet(mixed $offset): array|false
    {
        try {
            $categories = $this->filterCategories->getCategoriesByTypeName($offset);
        } catch (\InvalidArgumentException) {
            return false;
        }
        return $categories;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \InvalidArgumentException(
            'Method should never be called',
            1683633632593
        );
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \InvalidArgumentException(
            'Method should never be called',
            1683633656658
        );
    }

    public function getFilterCategories(): CategoryCollection
    {
        return $this->filterCategories;
    }

    public function __toString(): string
    {
        return self::class;
    }
}
