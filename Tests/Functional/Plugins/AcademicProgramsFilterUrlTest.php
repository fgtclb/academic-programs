<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;

/**
 * The filter and sorting form of the program list submits by POST, and the plugin answers
 * that submission with a `303` to a GET URL carrying the selection - so a filtered list can
 * be bookmarked, shared and reloaded.
 *
 * The form is submitted from the page the plugin rendered, with the fields it holds -
 * the referrer and request hash fields Extbase checks included. The site loads no route enhancer;
 * `Routing\ProgramListRouteEnhancerTest` covers the redirect through the shipped one.
 *
 * Categories: Bachelor of Science (1) and Master of Science (2) are degrees, Full-time (3)
 * is a program type, Europe (7) is a partner region and no program category. Applied
 * Physics is a full-time bachelor, Molecular Chemistry a master, Regional Teaching has no
 * category. The list on `/bachelor` preselects Bachelor of Science.
 */
final class AcademicProgramsFilterUrlTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const PLUGIN_NAMESPACE = 'tx_academicprograms_programlist';
    private const FORM_CLASS = 'academic-programs-filtersorting';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            // What every new installation gets: a URL with arguments the cache hash covers
            // and no cHash is a 404, not an uncached page.
            'FE' => [
                'cacheHash' => [
                    'enforceValidation' => true,
                ],
            ],
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        // The testing framework replaces the page cache by a NullBackend. The
                        // database backend is restored so that a filter URL can be served from
                        // the page another filter URL cached.
                        'pages' => [
                            'backend' => Typo3DatabaseBackend::class,
                        ],
                    ],
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsFilterUrl/programListPages.csv');
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
    public function submittingADegreeRedirectsToAUrlCarryingIt(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::PLUGIN_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame('/home', parse_url($location, PHP_URL_PATH));
        $this->assertSame(
            [
                'filterCollection' => ['categories' => '2'],
                'sortingDirection' => 'asc',
                'sortingField' => 'title',
            ],
            $this->demandArguments($location),
        );

        // A reload of this URL is a GET: nothing to re-send, and the same list again.
        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Regional Teaching', $content);
        $this->assertMatchesRegularExpression('#<option value="2"[^>]* selected="selected">Master of Science</option>#', $content);
    }

    /**
     * Only what the demand factory accepted reaches the URL: a category that is no program
     * category is gone, and so are the referrer and request hash fields of the form.
     */
    #[Test]
    public function onlyTheNormalisedSelectionReachesTheUrl(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::PLUGIN_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '1,7']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(['categories' => '1'], $this->demandArguments($location)['filterCollection'] ?? null);
        $this->assertStringNotContainsString('__referrer', urldecode($location));
        $this->assertStringNotContainsString('__trustedProperties', urldecode($location));
    }

    /**
     * The editor's preselection applies to the bare page only. A visitor who sets the
     * degree back to "all" gets a URL without a filter - but with the sorting, because a
     * demand without any argument is what makes the factory apply the preselection again.
     */
    #[Test]
    public function clearingAPreselectedDegreeShowsAllPrograms(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/bachelor');
        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);

        $response = $this->submitFrontendForm('https://www.acme.com/bachelor', self::FORM_CLASS, [
            self::PLUGIN_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(['sortingDirection' => 'asc', 'sortingField' => 'title'], $this->demandArguments($location));

        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringContainsString('Regional Teaching', $content);
    }

    /**
     * A form that posts to the URL it is on - an override without an action, one built
     * with `addQueryString`, a script posting to `location.href` - sends its fields to a
     * URL that carries a demand already, and Extbase merges the two. The redirect is built
     * from the body alone, so a degree the visitor cleared does not come back from the URL.
     */
    #[Test]
    public function aSubmissionToAFilteredUrlReplacesItsSelection(): void
    {
        $response = $this->requestFrontendPage($this->frontendPostRequest($this->filteredListUrl('2'), [
            self::PLUGIN_NAMESPACE => ['demand' => ['sortingField' => 'title', 'sortingDirection' => 'asc', 'filterCollection' => ['degree' => '', 'program_type' => '']]],
        ]));

        $this->assertSame(
            ['sortingDirection' => 'asc', 'sortingField' => 'title'],
            $this->demandArguments($this->assertSeeOtherWithCacheHash($response)),
        );
    }

    /**
     * Another plugin's form posting to a filtered URL carries no demand of this plugin in
     * its body. The list renders the filter the URL carries and does not redirect.
     */
    #[Test]
    public function anotherPluginsPostToAFilteredUrlIsNotRedirected(): void
    {
        $content = $this->renderFrontendPage(
            $this->frontendPostRequest($this->filteredListUrl('2'), ['tx_someother_plugin' => ['field' => 'value']]),
        );

        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
    }

    /**
     * The actions are not cacheable, so the demand is kept out of the cache hash: two
     * selections share one hash, and with it one page cache entry. A hash per selection
     * would let anybody fill the page cache by submitting combinations.
     */
    #[Test]
    public function theDemandIsNotPartOfTheCacheHash(): void
    {
        parse_str((string)parse_url($this->filteredListUrl('2'), PHP_URL_QUERY), $first);
        parse_str((string)parse_url($this->filteredListUrl('1'), PHP_URL_QUERY), $second);

        $this->assertNotSame($first, $second);
        $this->assertSame($first['cHash'], $second['cHash']);
    }

    /**
     * The demand needs no cache hash, also with `enforceValidation` on: a link that
     * carries nothing but the demand - built by hand, or by a template - renders the
     * filtered list. Were the demand part of the hash, it would be a 404.
     */
    #[Test]
    public function aDemandWithoutCacheHashRendersTheFilteredList(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home?tx_academicprograms_programlist%5Bdemand%5D%5BfilterCollection%5D%5Bcategories%5D=2');

        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
    }

    /**
     * Sharing one cached page is safe only because the list renders outside of it, on
     * every request. Rendered one after the other from the real page cache, each filter
     * URL shows its own selection: the first one adds one page cache entry, the second
     * one is served from it.
     */
    #[Test]
    public function filterUrlsSharingOneCachedPageShowTheirOwnSelection(): void
    {
        $first = $this->filteredListUrl('2');
        $second = $this->filteredListUrl('1');

        // Taking the form from the page and posting it cached pages already - the POST
        // caches the page around the plugin under the very identifier of the filter URLs,
        // before the plugin redirects. Start from an empty page cache instead.
        $this->getConnectionPool()->getConnectionForTable('cache_pages')->truncate('cache_pages');
        $this->getConnectionPool()->getConnectionForTable('cache_pages_tags')->truncate('cache_pages_tags');

        $content = $this->renderFrontendPage($first);
        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
        $this->assertSame(1, $this->countCachedPages());

        $content = $this->renderFrontendPage($second);
        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertSame(1, $this->countCachedPages());
    }

    private function countCachedPages(): int
    {
        return $this->getConnectionPool()->getConnectionForTable('cache_pages')->count('*', 'cache_pages', []);
    }

    /**
     * A POST that carries no demand of this plugin is somebody else's form - another
     * plugin on the same page. The list renders as usual and leaves the request alone.
     */
    #[Test]
    public function aPostWithoutADemandIsNotRedirected(): void
    {
        $content = $this->renderFrontendPage(
            $this->frontendPostRequest('https://www.acme.com/home', ['tx_someother_plugin' => ['field' => 'value']]),
        );

        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringContainsString('Molecular Chemistry', $content);
    }

    private function filteredListUrl(string $uid): string
    {
        return $this->assertSeeOtherWithCacheHash($this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::PLUGIN_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => $uid]]],
        ]));
    }

    /**
     * The demand arguments of a redirect target. They arrive in alphabetical order,
     * because the page router sorts the query arguments by key (`PageArguments`).
     *
     * @return array<string, mixed>
     */
    private function demandArguments(string $location): array
    {
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        $demand = $query[self::PLUGIN_NAMESPACE]['demand'] ?? null;
        $this->assertIsArray($demand, 'The redirect target carries no demand: ' . $location);

        return $demand;
    }
}
