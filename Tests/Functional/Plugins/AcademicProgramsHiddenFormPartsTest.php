<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * A part of the form the content element hides keeps the value of the content element,
 * and the list a program finder opens keeps the default categories of the types the finder
 * does not offer (ACE-916).
 *
 * The forms are submitted from the page that rendered them, with the fields they hold, and
 * the redirect is followed.
 *
 * Categories: Bachelor of Science (1) and Master of Science (2) are degrees, Engineering (4)
 * and Life sciences (5) topics, Berlin (6) and Potsdam (7) locations. Applied Physics is an
 * engineering bachelor in Berlin, Molecular Chemistry a life sciences master in Potsdam,
 * Mechanical Engineering an engineering master in Berlin. Every element takes its programs
 * from "/study".
 *
 * - `/home`: a program finder with its default selects, the degree and the topic. It opens
 *   `/berlin` unless a test points it elsewhere.
 * - `/hidden-filter`: the category filter hidden, Bachelor of Science preset, the sorting
 *   shown.
 * - `/hidden-sorting`: the sorting hidden and preset to title, descending.
 * - `/berlin`: the filter shown, Berlin preset.
 * - `/bachelor`: the filter shown, Bachelor of Science preset.
 * - `/berlin-hidden-filter`: the category filter hidden, Berlin preset.
 * - `/hidden-filter-no-preset`: the category filter hidden, no category preset.
 */
final class AcademicProgramsHiddenFormPartsTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicprograms_programlist';
    private const LIST_FORM = '//form[@name="demand"]';
    private const FINDER_FORM = '//div[contains(concat(" ", normalize-space(@class), " "), " academic-programs-finder ")]/form';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsHiddenFormParts/records.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function sortingAListWhoseFilterIsHiddenKeepsTheDefaultCategories(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/hidden-filter', self::LIST_FORM, [
            self::LIST_NAMESPACE => ['demand' => ['sortingDirection' => 'desc']],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            [
                'filterCollection' => ['categories' => '1'],
                'sortingDirection' => 'desc',
                'sortingField' => 'title',
            ],
            $this->demandArguments($location),
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Mechanical Engineering', $content);
    }

    /**
     * The form of a list whose sorting is hidden submits no sorting. That already kept the
     * sorting of the element since 3.0, and still does.
     */
    #[Test]
    public function filteringAListWhoseSortingIsHiddenKeepsTheDefaultSorting(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/hidden-sorting', self::LIST_FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            [
                'filterCollection' => ['categories' => '2'],
                'sortingDirection' => 'desc',
                'sortingField' => 'title',
            ],
            $this->demandArguments($location),
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringNotContainsString('Applied Physics', $content);
        $this->assertRenderedInOrder($content, 'Molecular Chemistry', 'Mechanical Engineering');
    }

    /**
     * A sorting a request carries for a list whose sorting is hidden, from an override of
     * the form or a hand-made request, does not change it.
     */
    #[Test]
    public function aSubmittedSortingDoesNotChangeAHiddenSorting(): void
    {
        $response = $this->requestFrontendPage($this->frontendPostRequest('https://www.acme.com/hidden-sorting', [
            self::LIST_NAMESPACE => ['demand' => [
                'filterCollection' => ['degree' => '2'],
                'sortingField' => 'title',
                'sortingDirection' => 'asc',
            ]],
        ]));

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            [
                'filterCollection' => ['categories' => '2'],
                'sortingDirection' => 'desc',
                'sortingField' => 'title',
            ],
            $this->demandArguments($location),
        );
    }

    #[Test]
    public function aLinkedSortingDoesNotChangeAHiddenSorting(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/hidden-sorting?tx_academicprograms_programlist%5Bdemand%5D%5BsortingField%5D=title&tx_academicprograms_programlist%5Bdemand%5D%5BsortingDirection%5D=asc',
        );

        $this->assertRenderedInOrder($content, 'Molecular Chemistry', 'Mechanical Engineering');
        $this->assertRenderedInOrder($content, 'Mechanical Engineering', 'Applied Physics');
    }

    /**
     * Without default categories, the categories of a link filter a list whose filter is
     * hidden, as they did before.
     */
    #[Test]
    public function aUrlFiltersAHiddenFilterWithoutDefaultCategories(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/hidden-filter-no-preset?tx_academicprograms_programlist%5Bdemand%5D%5BfilterCollection%5D%5Bcategories%5D=2',
        );

        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringContainsString('Mechanical Engineering', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
    }

    /**
     * Categories a URL carries for a list whose filter is hidden narrow the list, and never
     * replace a default category: no program is a bachelor and a master at once.
     */
    #[Test]
    public function aUrlDoesNotReplaceTheCategoriesOfAHiddenFilter(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/hidden-filter?tx_academicprograms_programlist%5Bdemand%5D%5BfilterCollection%5D%5Bcategories%5D=2',
        );

        $this->assertStringNotContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Mechanical Engineering', $content);
    }

    /**
     * The finder offers the degree and the topic, the list presets a location. The list
     * keeps the location and adds the degree of the finder.
     */
    #[Test]
    public function theFinderKeepsAPresetOfACategoryTypeItDoesNotOffer(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FINDER_FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame('/berlin', parse_url($location, PHP_URL_PATH));
        $this->assertSame(['categories' => '2,6'], $this->demandArguments($location)['filterCollection'] ?? null);

        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Mechanical Engineering', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
    }

    /**
     * A preset of a type the finder offers is replaced by the finder's selection of that
     * type, as a visitor replaces it in the list's own filter.
     */
    #[Test]
    public function theFinderReplacesAPresetOfACategoryTypeItOffers(): void
    {
        $this->setFinderTarget(8);

        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FINDER_FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame('/bachelor', parse_url($location, PHP_URL_PATH));
        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringContainsString('Mechanical Engineering', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
        $this->assertStringContainsString('<option value="2" class="level-0" selected="selected">Master of Science</option>', $content);
    }

    #[Test]
    public function theFinderKeepsEveryPresetOfAListWhoseFilterIsHidden(): void
    {
        $this->setFinderTarget(9);

        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FINDER_FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame('/berlin-hidden-filter', parse_url($location, PHP_URL_PATH));
        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Mechanical Engineering', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
    }

    /**
     * The list's own filter offers the location, and "All" clears its preset as before.
     */
    #[Test]
    public function theListFilterStillClearsAPresetOfATypeItOffers(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/berlin', self::LIST_FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['location' => '']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertArrayNotHasKey('filterCollection', $this->demandArguments($location));
        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringContainsString('Mechanical Engineering', $content);
    }

    /**
     * The filter types of the element offer the degree only, so the default location is no
     * part of the form and stays.
     */
    #[Test]
    public function theListFilterKeepsAPresetOfACategoryTypeItDoesNotOffer(): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                [
                    'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                        . '<field index="settings.hideFilter"><value index="vDEF">0</value></field>'
                        . '<field index="settings.hideSorting"><value index="vDEF">0</value></field>'
                        . '<field index="settings.sorting"><value index="vDEF">title asc</value></field>'
                        . '<field index="settings.categories"><value index="vDEF">1</value></field>'
                        . '<field index="settings.filter.categoryTypes"><value index="vDEF">degree</value></field>'
                        . '<field index="settings.showHiddenRecords"><value index="vDEF">0</value></field>'
                        . '</language></sheet></data></T3FlexForms>',
                ],
                ['uid' => 4],
            );

        $response = $this->submitFrontendForm('https://www.acme.com/berlin', self::LIST_FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(['categories' => '2,6'], $this->demandArguments($location)['filterCollection'] ?? null);
        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Mechanical Engineering', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
    }

    /**
     * Points the finder to the list on page `$listPid`, the way FormEngine stores it.
     */
    private function setFinderTarget(int $listPid): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                [
                    'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                        . '<field index="settings.listPid"><value index="vDEF">' . $listPid . '</value></field>'
                        . '</language></sheet></data></T3FlexForms>',
                ],
                ['uid' => 1],
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function demandArguments(string $location): array
    {
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        $demand = $query[self::LIST_NAMESPACE]['demand'] ?? null;
        $this->assertIsArray($demand, 'The URL carries no demand: ' . $location);

        return $demand;
    }

    private function assertRenderedInOrder(string $content, string $first, string $second): void
    {
        $firstPosition = strpos($content, $first);
        $secondPosition = strpos($content, $second);
        $this->assertNotFalse($firstPosition, $first . ' is not rendered.');
        $this->assertNotFalse($secondPosition, $second . ' is not rendered.');
        $this->assertLessThan($secondPosition, $firstPosition, $first . ' is not rendered before ' . $second . '.');
    }
}
