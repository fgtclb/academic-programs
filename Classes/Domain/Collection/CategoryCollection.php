<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Domain\Collection;

use ArrayAccess;
use Countable;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Model\EducationalCategory;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
use Iterator;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @implements Iterator<int, EducationalCategory>
 */
class CategoryCollection implements Countable, Iterator, ArrayAccess
{
    /**
     * @var EducationalCategory[]
     */
    protected array $container = [];

    /**
     * @var array<string, EducationalCategory[]>
     */
    protected array $typeSortedContainer;

    public function __construct()
    {
        $typeNames = Category::getConstants();
        ksort($typeNames);

        $this->typeSortedContainer = array_map(function () {
            return [];
        }, array_flip(array_values($typeNames)));
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

    /**
     * @param string $typeName
     */
    public function getAttributesByTypeName(string $typeName): array
    {
        $typeName = GeneralUtility::camelCaseToLowerCaseUnderscored($typeName);
        if (!array_key_exists($typeName, $this->typeSortedContainer)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Category type "%s" must type of "%s"',
                    $typeName,
                    Category::class
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
        return $this->getAttributesByTypeName($name);
    }

    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset)) {
            return false;
        }
        $lowerName = GeneralUtility::camelCaseToLowerCaseUnderscored($offset);
        try {
            $enum = new Category($lowerName);
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
        return $this->getAttributesByTypeName($offset);
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

    public function __toString(): string
    {
        return self::class;
    }

    public function exist(EducationalCategory $category): bool
    {
        return in_array($category, $this->container, false);
    }
}
