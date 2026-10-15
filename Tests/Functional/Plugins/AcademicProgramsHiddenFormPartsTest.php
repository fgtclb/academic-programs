<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * A part of the form the content element hides keeps the value of the content element:
 * a visitor who submits the visible part changes that part only (ACE-916).
 *
 * The form posts to the page, and the list renders in the answer to the post. A post carries
 * the fields of the parts the form shows, a hidden part has no field.
 *
 * Categories: Bachelor of Science (1) and Master of Science (2) are degrees, Engineering (4)
 * and Life sciences (5) topics, Berlin (6) and Potsdam (7) locations. Applied Physics is an
 * engineering bachelor in Berlin, Molecular Chemistry a life sciences master in Potsdam,
 * Mechanical Engineering an engineering master in Berlin. Every element takes its programs
 * from "/study".
 *
 * - `/hidden-filter`: the category filter hidden, Bachelor of Science preset, the sorting
 *   shown.
 * - `/hidden-sorting`: the sorting hidden and preset to title, descending, the filter shown.
 * - `/hidden-filter-no-defaults`: the category filter hidden, no default categories.
 * - `/visible-filter`: the category filter shown, Bachelor of Science preset.
 */
final class AcademicProgramsHiddenFormPartsTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicprograms_programlist';

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
        $content = $this->postDemand('https://www.acme.com/hidden-filter', [
            'sortingField' => 'title',
            'sortingDirection' => 'desc',
        ]);

        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Mechanical Engineering', $content);
    }

    /**
     * Categories a link or a hand-made URL carries for a list whose filter is hidden are
     * added to the default categories: they narrow the list to the engineering bachelors.
     */
    #[Test]
    public function aUrlNarrowsTheCategoriesOfAHiddenFilter(): void
    {
        $content = $this->renderFrontendPage($this->pageUrl('/hidden-filter', 3, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['categories' => '4']]],
        ]));

        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Mechanical Engineering', $content);
    }

    /**
     * A URL never replaces a default category of a hidden filter: no program is a bachelor
     * and a master at once.
     */
    #[Test]
    public function aUrlDoesNotReplaceTheCategoriesOfAHiddenFilter(): void
    {
        $content = $this->renderFrontendPage($this->pageUrl('/hidden-filter', 3, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['categories' => '2']]],
        ]));

        $this->assertStringContainsString('academic-programs-list', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Mechanical Engineering', $content);
    }

    /**
     * A list that hides its filter without default categories is filtered by the
     * categories of a link, as before.
     */
    #[Test]
    public function aUrlFiltersAHiddenFilterWithoutDefaultCategories(): void
    {
        $content = $this->renderFrontendPage($this->pageUrl('/hidden-filter-no-defaults', 7, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['categories' => '2']]],
        ]));

        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringContainsString('Mechanical Engineering', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
    }

    /**
     * A list that shows its filter works as before: the selection of the visitor replaces
     * the default categories.
     */
    #[Test]
    public function aSelectionInAVisibleFilterReplacesTheDefaultCategories(): void
    {
        $content = $this->postDemand('https://www.acme.com/visible-filter', [
            'sortingField' => 'title',
            'sortingDirection' => 'asc',
            'filterCollection' => ['degree' => '2', 'topic' => '', 'location' => ''],
        ]);

        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringContainsString('Mechanical Engineering', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
    }

    /**
     * The form of a list whose sorting is hidden submits no sorting.
     */
    #[Test]
    public function filteringAListWhoseSortingIsHiddenKeepsTheDefaultSorting(): void
    {
        $content = $this->postDemand('https://www.acme.com/hidden-sorting', [
            'filterCollection' => ['degree' => '2', 'topic' => '', 'location' => ''],
        ]);

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
        $content = $this->postDemand('https://www.acme.com/hidden-sorting', [
            'filterCollection' => ['degree' => '2', 'topic' => '', 'location' => ''],
            'sortingField' => 'title',
            'sortingDirection' => 'asc',
        ]);

        $this->assertStringNotContainsString('Applied Physics', $content);
        $this->assertRenderedInOrder($content, 'Molecular Chemistry', 'Mechanical Engineering');
    }

    /**
     * The page as the answer to a post of the filter form renders it.
     *
     * @param array<string, mixed> $demand
     */
    private function postDemand(string $url, array $demand): string
    {
        $parsedBody = [self::LIST_NAMESPACE => ['demand' => $demand]];
        $body = new Stream('php://temp', 'rw');
        $body->write(http_build_query($parsedBody));
        $body->rewind();

        return $this->renderFrontendPage(
            (new InternalRequest($url))
                ->withMethod('POST')
                ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($body)
                ->withParsedBody($parsedBody),
        );
    }

    /**
     * The URL of a page with plugin arguments and the cache hash a link carries: without
     * one, the page answers `404`.
     *
     * @param array<string, mixed> $arguments
     */
    private function pageUrl(string $path, int $pageId, array $arguments): string
    {
        $query = HttpUtility::buildQueryString($arguments);
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)
            ->generateForParameters('id=' . $pageId . '&' . $query);

        return 'https://www.acme.com' . $path . '?' . $query . '&cHash=' . $cacheHash;
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
