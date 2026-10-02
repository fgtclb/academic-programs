<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Routing;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * The program list behind the route enhancer of `Configuration/Routes/List.yaml`.
 *
 * The site configuration imports the shipped file the way a site does, so the test reads
 * the file itself and runs it through the `imports` handling of the site configuration: a
 * copy would keep passing after the file was renamed, emptied or made invalid, and what
 * ACE-454 was about is a file that nothing ever read. `Configuration/Yaml/Routes.yaml`, the
 * path the enhancer had before, imports the new file and is tested the same way.
 *
 * Both directions are covered, because an enhancer can be broken in either one on its own:
 * a namespace that does not match the plugin signature breaks generation only, and a route
 * path whose variables can swallow a `/` breaks resolving only.
 *
 * Applied Physics is a Bachelor of Science (1), Molecular Chemistry a Master of Science (2).
 *
 * - `/home` (`/de/home`): the list, sorted by title ascending.
 * - `/preset`: a list with Bachelor of Science preselected by the editor.
 * - `/last-updated`: a list sorted by the last update, descending.
 */
final class ProgramListRouteEnhancerTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    /**
     * The Extbase namespace the enhancer derives from `extension: AcademicPrograms` plus
     * `plugin: ProgramList`: `ExtbasePluginEnhancer::__construct()` builds
     * `'tx_' . strtolower($extension . '_' . $plugin)`. It has to be the plugin signature
     * `ext_localconf.php` registers, otherwise generation never enters the enhancer and
     * silently falls back to a query string.
     */
    private const PLUGIN_NAMESPACE = 'tx_academicprograms_programlist';

    private const FORM_CLASS = 'academic-programs-filtersorting';

    private const ROUTES = 'EXT:academic_programs/Configuration/Routes/List.yaml';

    private const FORMER_ROUTES = 'EXT:academic_programs/Configuration/Yaml/Routes.yaml';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            // What every new installation gets, so a filter behind the route that needed a
            // cHash and has none would be a 404 here.
            'FE' => [
                'cacheHash' => [
                    'enforceValidation' => true,
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProgramListRouteEnhancer/programListPages.csv');
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
                    'EXT:academic_programs/Tests/Functional/Routing/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeSite(self::ROUTES);
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{0: int, 1: array<string, mixed>, 2: string, 3: list<string>, 4: array{0: string, 1: string}}>
     */
    public static function combinations(): \Generator
    {
        yield 'filter and sorting' => [
            0, ['filterCollection' => ['categories' => '2'], 'sortingField' => 'title', 'sortingDirection' => 'desc'],
            '/home/filter/master-of-science-2/title/desc',
            ['Molecular Chemistry'],
            ['title', 'desc'],
        ];
        yield 'only a filter' => [
            0, ['filterCollection' => ['categories' => '1']],
            '/home/filter/bachelor-of-science-1',
            ['Applied Physics'],
            ['title', 'asc'],
        ];
        yield 'only a sorting' => [
            0, ['sortingField' => 'lastUpdated', 'sortingDirection' => 'desc'],
            '/home/last-updated/desc',
            ['Applied Physics', 'Molecular Chemistry'],
            ['lastUpdated', 'desc'],
        ];
        yield 'German: filter and sorting' => [
            1, ['filterCollection' => ['categories' => '2'], 'sortingField' => 'sorting', 'sortingDirection' => 'desc'],
            '/de/home/filter/master-of-science-2/sortierung/absteigend',
            ['Molecular Chemistry'],
            ['sorting', 'desc'],
        ];
        yield 'German: only a filter' => [
            1, ['filterCollection' => ['categories' => '1']],
            '/de/home/filter/bachelor-of-science-1',
            ['Applied Physics'],
            ['title', 'asc'],
        ];
        yield 'German: only a sorting' => [
            1, ['sortingField' => 'title', 'sortingDirection' => 'desc'],
            '/de/home/titel/absteigend',
            ['Applied Physics', 'Molecular Chemistry'],
            ['title', 'desc'],
        ];
    }

    /**
     * @param array<string, mixed> $demand
     * @param list<string> $programs
     * @param array{0: string, 1: string} $sorting
     */
    #[DataProvider('combinations')]
    #[Test]
    public function everyCombinationGeneratesAPathThatResolves(int $languageId, array $demand, string $path, array $programs, array $sorting): void
    {
        $uri = $this->generateUri($languageId, 2, $demand);

        $this->assertSame('https://www.acme.com' . $path, $uri);

        $content = $this->renderFrontendPage($uri);
        $this->assertPrograms($programs, $content);
        $this->assertSelectedSorting($sorting, $content);
    }

    /**
     * A site that imports the former path gets the same routes, under the same enhancer
     * key, so the `limitToPages` it set for that key still applies.
     */
    #[Test]
    public function theFormerPathImportsTheSameRoutes(): void
    {
        $this->writeSite(self::FORMER_ROUTES);

        $uri = $this->generateUri(0, 2, ['filterCollection' => ['categories' => '2'], 'sortingField' => 'lastUpdated', 'sortingDirection' => 'desc']);

        $this->assertSame('https://www.acme.com/home/filter/master-of-science-2/last-updated/desc', $uri);
        $content = $this->renderFrontendPage($uri);
        $this->assertPrograms(['Molecular Chemistry'], $content);
        $this->assertSelectedSorting(['lastUpdated', 'desc'], $content);
    }

    /**
     * The enhancer declares no `defaults`, so the sorting the plugin uses anyway is written
     * into the path like every other one. With a default it would be left out, and the
     * redirect after a form submission would end on the bare page, where the content
     * element's presets apply again.
     */
    #[Test]
    public function defaultSortingValuesStayInTheGeneratedPath(): void
    {
        $this->assertSame('https://www.acme.com/home/title/asc', $this->generateUri(0, 2, ['sortingField' => 'title', 'sortingDirection' => 'asc']));
        $this->assertSame('https://www.acme.com/home/sorting/asc', $this->generateUri(0, 2, ['sortingField' => 'sorting', 'sortingDirection' => 'asc']));
    }

    /**
     * The enhanced route must not take over the plain page URL. If it did - through
     * `defaults` - the default values would reach the controller as a demand and overrule
     * the sorting configured in the content element, which is the sorting a visitor sees
     * before they ever touch the form.
     */
    #[Test]
    public function plainPageUriKeepsTheSortingConfiguredInThePlugin(): void
    {
        $this->assertSelectedSorting(['lastUpdated', 'desc'], $this->renderFrontendPage('https://www.acme.com/last-updated'));
    }

    /**
     * A link to the list that carries neither a filter nor a sorting - the form's own
     * action URL, a hand written link - does not enter the enhancer and keeps its plugin
     * arguments in the query string, with a cache hash. It still shows the list as the
     * content element presets it.
     */
    #[Test]
    public function aListLinkWithoutFilterAndSortingKeepsItsQueryString(): void
    {
        $uri = (string)$this->get(SiteFinder::class)
            ->getSiteByIdentifier('acme')
            ->getRouter()
            ->generateUri(2, [self::PLUGIN_NAMESPACE => ['action' => 'list', 'controller' => 'Program']]);

        $this->assertStringStartsWith('https://www.acme.com/home?', $uri);
        $this->assertStringContainsString('cHash=', $uri);
        $this->assertStringContainsString('academic-programs-list', $this->renderFrontendPage($uri));
    }

    /**
     * Both sorting segments are required: a path with the sorting field alone is no list URL.
     */
    #[Test]
    public function aPathWithTheSortingFieldAloneDoesNotResolve(): void
    {
        $this->assertSame(404, $this->requestFrontendPage('https://www.acme.com/home/last-updated')->getStatusCode());
    }

    /**
     * The sorting values follow the language of the site, and a value of another language
     * is not a second address of the same list. This is also what a sorting path of a German
     * site, published before the values were translated, answers now.
     */
    #[Test]
    public function aSortingValueOfAnotherLanguageIsNotFound(): void
    {
        $this->assertSame(404, $this->requestFrontendPage('https://www.acme.com/de/home/title/asc')->getStatusCode());
        $this->assertSame(404, $this->requestFrontendPage('https://www.acme.com/home/titel/aufsteigend')->getStatusCode());
    }

    #[Test]
    public function aSortingSubmissionRedirectsToThePath(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::PLUGIN_NAMESPACE => ['demand' => ['sortingField' => 'lastUpdated', 'sortingDirection' => 'desc']],
        ]);

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('https://www.acme.com/home/last-updated/desc', $response->getHeaderLine('Location'));
        $this->assertSelectedSorting(['lastUpdated', 'desc'], $this->renderFrontendPage($response->getHeaderLine('Location')));
    }

    /**
     * The filter is part of the path too. It needs no cache hash: the demand is excluded
     * from it, and the filter is a dynamic route argument.
     */
    #[Test]
    public function aFilterSubmissionRedirectsToThePath(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/preset', self::FORM_CLASS, [
            self::PLUGIN_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2']]],
        ]);

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('https://www.acme.com/preset/filter/master-of-science-2/title/asc', $response->getHeaderLine('Location'));
        $this->assertPrograms(['Molecular Chemistry'], $this->renderFrontendPage($response->getHeaderLine('Location')));
    }

    /**
     * The content element preselects Bachelor of Science. Setting the degree back to "all"
     * with the default sorting has to reach a URL that is not the bare page, or the preset
     * applies again - which is what `defaults` in the enhancer did.
     */
    #[Test]
    public function clearingAPreselectedDegreeStaysClearedBehindTheEnhancer(): void
    {
        $this->assertPrograms(['Applied Physics'], $this->renderFrontendPage('https://www.acme.com/preset'));

        $response = $this->submitFrontendForm('https://www.acme.com/preset', self::FORM_CLASS, [
            self::PLUGIN_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '']]],
        ]);

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('https://www.acme.com/preset/title/asc', $response->getHeaderLine('Location'));
        $this->assertPrograms(['Applied Physics', 'Molecular Chemistry'], $this->renderFrontendPage($response->getHeaderLine('Location')));
    }

    #[Test]
    public function withoutTheImportAListLinkKeepsItsQueryArguments(): void
    {
        $this->writeSite(null);

        $uri = $this->generateUri(0, 2, ['sortingField' => 'lastUpdated', 'sortingDirection' => 'desc']);

        $this->assertStringStartsWith('https://www.acme.com/home?', $uri);
        $this->assertStringContainsString(rawurlencode(self::PLUGIN_NAMESPACE . '[demand][sortingField]') . '=lastUpdated', $uri);
    }

    private function writeSite(?string $routes): void
    {
        $routing = [];
        if ($routes !== null) {
            $routing = [
                'imports' => [
                    ['resource' => $routes],
                ],
                'routeEnhancers' => [
                    'AcademicPrograms' => ['limitToPages' => [2, 3, 4]],
                ],
            ];
        }
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: $routing,
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                // Falls back to English, so the content element and the programs, which are
                // not translated, render on the German page as well.
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/', fallbackIdentifiers: ['EN']),
            ],
        );
    }

    /**
     * @param array<string, mixed> $demand
     */
    private function generateUri(int $languageId, int $pageId, array $demand): string
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme');

        return (string)$site->getRouter()->generateUri($pageId, [
            '_language' => $site->getLanguageById($languageId),
            self::PLUGIN_NAMESPACE => [
                'action' => 'list',
                'controller' => 'Program',
                'demand' => $demand,
            ],
        ]);
    }

    /**
     * The programs the list shows, and none of the others. The order is not compared: the
     * fixture has no values that would order "last updated".
     *
     * @param list<string> $expected
     */
    private function assertPrograms(array $expected, string $content): void
    {
        // Sanity: the plugin really rendered, the assertions are not on an error page.
        $this->assertStringContainsString('academic-programs-list', $content);
        $shown = [];
        foreach (['Applied Physics', 'Molecular Chemistry'] as $program) {
            if (str_contains($content, $program)) {
                $shown[] = $program;
            }
        }

        $this->assertSame($expected, $shown);
    }

    /**
     * The sorting selects are rendered from the demand object the controller received, so a
     * selected option is the observable end of the arguments the router resolved.
     *
     * @param array{0: string, 1: string} $sorting
     */
    private function assertSelectedSorting(array $sorting, string $content): void
    {
        $this->assertStringContainsString(sprintf('<option value="%s" selected="selected">', $sorting[0]), $content);
        $this->assertStringContainsString(sprintf('<option value="%s" selected="selected">', $sorting[1]), $content);
        $this->assertStringContainsString('academic-programs-list', $content);
    }
}
