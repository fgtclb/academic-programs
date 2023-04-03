<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Model;

use Countable;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
use Iterator;

/**
 * @implements Iterator<int, EducationalCategory>
 */
class CategoryCollection implements Countable, Iterator
{
    /**
     * @var EducationalCategory[]
     */
    protected array $container = [];

    /**
     * @var array<string, EducationalCategory[]>
     */
    protected array $typeSortedContainer = [
        Category::TYPE_APPLICATION_PERIOD => [],
        Category::TYPE_BEGIN_COURSE => [],
        Category::TYPE_COURSE_TYPE => [],
        Category::TYPE_COSTS => [],
        Category::TYPE_DEGREE => [],
        Category::TYPE_DEPARTMENT => [],
        Category::TYPE_STANDARD_PERIOD => [],
        Category::TYPE_TEACHING_LANGUAGE => [],
        Category::TYPE_TOPIC => [],
    ];
    public function current(): EducationalCategory|false
    {
        return current($this->container);
    }

    public function next(): void
    {
        next($this->container);
    }

    public function key(): string|int|null
    {
        return key($this->container);
    }

    public function valid(): bool
    {
        return current($this->container) !== false;
    }

    public function rewind(): void
    {
        reset($this->container);
    }

    public function count(): int
    {
        return count($this->container);
    }

    /**
     * @throws CategoryExistException
     */
    public function attach(EducationalCategory $category): void
    {
        if (in_array($category, $this->container, true)) {
            throw new CategoryExistException(
                'Category already defined in container.',
                1678979375329
            );
        }
        $this->container[] = $category;
        $this->typeSortedContainer[(string)$category->getType()][] = $category;
    }

    /**
     * @return array<string, EducationalCategory[]>
     */
    public function getAllAttributesByType(): array
    {
        return $this->typeSortedContainer;
    }

    public function getAttributesByType(Category $type): Iterator|Countable
    {
        return new class (
            $this->typeSortedContainer[(string)$type]
        ) implements Iterator, Countable {
            /**
             * @var EducationalCategory[]
             */
            private array $container;

            /**
             * @param EducationalCategory[] $attributes
             */
            public function __construct(array $attributes)
            {
                $this->container = $attributes;
            }
            public function current(): EducationalCategory|false
            {
                return current($this->container);
            }

            public function next(): void
            {
                next($this->container);
            }

            public function key(): string|int|null
            {
                return key($this->container);
            }

            public function valid(): bool
            {
                return current($this->container) !== false;
            }

            public function rewind(): void
            {
                reset($this->container);
            }

            public function count(): int
            {
                return count($this->container);
            }
        };
    }
}
