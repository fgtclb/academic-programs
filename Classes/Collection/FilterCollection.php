<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Collection;

use ArrayAccess;
use FGTCLB\EducationalCourse\Domain\Model\Category;
use InvalidArgumentException;

/**
 * @implements ArrayAccess<string, Category[]>
 */
class FilterCollection implements ArrayAccess
{
    public function __construct(
        protected CategoryCollection $filterCategories
    ) {}

    public static function createByCategoryCollection(CategoryCollection $filterCategories): FilterCollection
    {
        $filter = new self($filterCategories);
        return $filter;
    }

    public static function resetCollection(): FilterCollection
    {
        $filterCategories = new CategoryCollection();
        $filter = new self($filterCategories);
        return $filter;
    }

    public function offsetExists(mixed $offset): bool
    {
        try {
            $this->filterCategories->getCategoriesByTypeName($offset);
        } catch (InvalidArgumentException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param mixed $offset
     * @return array<int, Category>|false
     */
    public function offsetGet(mixed $offset): array|false
    {
        try {
            $categories = $this->filterCategories->getCategoriesByTypeName($offset);
        } catch (InvalidArgumentException $e) {
            return false;
        }
        return $categories;
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
        throw new InvalidArgumentException(
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
