<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Enumeration;

final class SortingOptions
{
    public const __default = self::SORT_BY_TITLE_ASC;

    public const SORT_BY_TITLE_ASC = 'title asc';

    public const SORT_BY_TITLE_DESC = 'title desc';

    public const SORT_BY_LASTUPDATED_ASC = 'lastUpdated asc';

    public const SORT_BY_LASTUPDATED_DESC = 'lastUpdated desc';

    public const SORT_BY_SORTING_ASC = 'sorting asc';

    /**
     * Returns all sorting option constants (excluding the `__default` alias),
     * keyed by constant name. Replaces the removed
     * TYPO3\CMS\Core\Type\Enumeration::getConstants().
     *
     * @return array<string, string>
     */
    public static function getConstants(): array
    {
        $constants = [];
        foreach ((new \ReflectionClass(self::class))->getReflectionConstants() as $constant) {
            $value = $constant->getValue();
            if ($constant->getName() === '__default' || !is_string($value)) {
                continue;
            }
            $constants[$constant->getName()] = $value;
        }
        return $constants;
    }
}
