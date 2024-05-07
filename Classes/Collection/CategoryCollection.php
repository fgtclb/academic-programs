<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Collection;

use ArrayAccess;
use Countable;
use FGTCLB\EducationalCourse\Enumeration\CategoryTypes;
use FGTCLB\EducationalCourse\Domain\Model\Category;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
use Iterator;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @implements Iterator<int, Category>
 */
class CategoryCollection implements Countable, Iterator, ArrayAccess
{
    /**
     * @var Category[]
     */
    protected array $container = [];

    /**
     * @var array<string, Category[]>
     */
    protected array $typeSortedContainer;

    public function __construct()
    {
        $typeNames = CategoryTypes::getConstants();
        ksort($typeNames);

        $this->typeSortedContainer = array_map(function () {
            return [];
        }, array_flip(array_values($typeNames)));
    }

    /**
     * @return Category|false
     */
    public function current(): Category|false
    {
        return current($this->container);
    }

    public function next(): void
    {
        next($this->container);
    }

    /**
     * @return string|int|null
     */
    public function key(): string|int|null
    {
        return key($this->container);
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return current($this->container) !== false;
    }

    public function rewind(): void
    {
        reset($this->container);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->container);
    }

    /**
     * @param Category $category
     */
    public function attach(Category $category): void
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
     * @return array<string, Category[]>
     */
    public function getAllCategoriesByType(): array
    {
        return $this->typeSortedContainer;
    }

    /**
     * @param string $typeName
     * @return Category[]
     */
    public function getCategoriesByTypeName(string $typeName): array
    {
        $typeName = GeneralUtility::camelCaseToLowerCaseUnderscored($typeName);
        if (!array_key_exists($typeName, $this->typeSortedContainer)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Category type "%s" must type of "%s"',
                    $typeName,
                    CategoryTypes::class
                ),
                1683633304209
            );
        }

        return $this->typeSortedContainer[$typeName];
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    public function __call(string $name, array $arguments): array
    {
        return $this->getCategoriesByTypeName($name);
    }

    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset)) {
            return false;
        }
        $lowerName = GeneralUtility::camelCaseToLowerCaseUnderscored($offset);
        try {
            $enum = new CategoryTypes($lowerName);
            return true;
        } catch (InvalidEnumerationValueException $e) {
            return false;
        }
    }

    public function offsetGet(mixed $offset): array|false
    {
        if (!is_string($offset)) {
            return false;
        }
        return $this->getCategoriesByTypeName($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \InvalidArgumentException(
            'Method should never be called',
            1683214236549
        );
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \InvalidArgumentException(
            'Method should never be called',
            1683214246022
        );
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * @param Category $category
     * @return bool
     */
    public function exist(Category $category): bool
    {
        return in_array($category, $this->container, false);
    }
}
