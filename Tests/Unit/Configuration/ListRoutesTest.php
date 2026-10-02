<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Unit\Configuration;

use FGTCLB\AcademicPrograms\Enumeration\SortingOptions;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The route files of `Configuration/Routes/List.yaml` against the sorting options of the
 * extension.
 *
 * A `StaticValueMapper` knows only the values it lists: a sorting missing from a map
 * keeps its query arguments instead of a path, in the language it is missing for. The
 * file is read with the plain YAML parser, as `ShippedYamlFilesTest` of `academic_base`
 * does, because value lists and patterns are all this test compares.
 */
final class ListRoutesTest extends UnitTestCase
{
    private const FILE = __DIR__ . '/../../../Configuration/Routes/List.yaml';

    #[Test]
    public function everyLanguageMapsExactlyTheSortingOptions(): void
    {
        $fields = [];
        foreach (SortingOptions::getConstants() as $option) {
            $fields[] = explode(' ', $option)[0];
        }
        $fields = array_values(array_unique($fields));

        foreach ($this->enhancers() as $key => $enhancer) {
            $this->assertSame($this->inEveryLanguage($fields), $this->valuesPerLanguage($enhancer['aspects']['sorting_field']), $key);
            $this->assertSame($this->inEveryLanguage(['asc', 'desc']), $this->valuesPerLanguage($enhancer['aspects']['sorting_direction']), $key);
        }
    }

    /**
     * An aspect makes `AbstractEnhancer::applyRequirements()` give its variable the pattern
     * `.+`, which crosses slashes, so two mapped variables in one path swallow each other.
     * Every variable that is not a static key needs a requirement that stays within one
     * segment.
     */
    #[Test]
    public function everyVariableIsPinnedToOneSegment(): void
    {
        foreach ($this->enhancers() as $key => $enhancer) {
            foreach ($enhancer['routes'] as $route) {
                preg_match_all('/{([^}]+)}/', $route['routePath'], $matches);
                foreach ($matches[1] as $variable) {
                    if (($enhancer['aspects'][$variable]['type'] ?? '') === 'LocaleModifier') {
                        continue;
                    }
                    $this->assertArrayHasKey($variable, $enhancer['requirements'], $key . ': ' . $route['routePath']);
                    $this->assertSame(0, preg_match('#^(?:' . $enhancer['requirements'][$variable] . ')$#', 'a/b'), $key . ': ' . $variable);
                }
            }
        }
    }

    /**
     * Symfony leaves a variable that equals its default out of a generated path, so a
     * default would turn a link with the default sorting into the bare page, where the
     * presets of the content element apply again.
     */
    #[Test]
    public function noEnhancerDeclaresDefaults(): void
    {
        foreach ($this->enhancers() as $key => $enhancer) {
            $this->assertArrayNotHasKey('defaults', $enhancer, $key);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function enhancers(): array
    {
        $configuration = Yaml::parseFile(self::FILE);
        $this->assertIsArray($configuration);
        $this->assertSame(['AcademicPrograms'], array_keys($configuration['routeEnhancers']));

        return $configuration['routeEnhancers'];
    }

    /**
     * The values a mapper resolves to, per language: `default` for the map without a
     * locale, and the locale pattern of each `localeMap` item.
     *
     * @param array<string, mixed> $aspect
     * @return array<string, list<string>>
     */
    private function valuesPerLanguage(array $aspect): array
    {
        $values = [];
        foreach ($this->maps($aspect) as $language => $map) {
            $mapped = array_values(array_unique(array_map(strval(...), $map)));
            sort($mapped);
            $values[$language] = $mapped;
        }
        return $values;
    }

    /**
     * @param array<string, mixed> $aspect
     * @return array<string, array<array-key, mixed>>
     */
    private function maps(array $aspect): array
    {
        $maps = ['default' => $aspect['map']];
        foreach ($aspect['localeMap'] ?? [] as $item) {
            $maps[$item['locale']] = $item['map'];
        }
        return $maps;
    }

    /**
     * @param list<string> $values
     * @return array<string, list<string>>
     */
    private function inEveryLanguage(array $values): array
    {
        sort($values);
        return ['default' => $values, 'de.*' => $values];
    }
}
