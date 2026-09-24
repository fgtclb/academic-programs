<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Routing;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Exercises the route enhancer shipped as `Configuration/Yaml/Routes.yaml`.
 *
 * The site configuration written here does not inline a copy of that enhancer, it reads
 * the shipped file itself. That is the point of the test: a copy would keep passing
 * after the file was renamed, emptied or made syntactically invalid, and what ACE-454 is
 * about is a file that nothing ever read.
 *
 * Both directions are covered, because an enhancer can be broken in either one on its
 * own: a namespace that does not match the plugin signature breaks generation only, and
 * a route path whose variables can swallow a `/` breaks resolving only.
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

    /**
     * Every combination `FGTCLB\AcademicPrograms\Enumeration\SortingOptions` offers, split
     * into the two arguments the enhancer maps. `sorting desc` joined them with ACE-625;
     * before that the enum did not have it and `ProgramDemand::setSorting()` dropped the
     * pair, so a path carrying it resolved and was then ignored.
     *
     * The list is spelled out rather than derived from the enum, which keeps a renamed
     * option visible here - but it also means nothing makes this list fail when an option
     * is added. A new option stops being covered silently; it has to be added by hand.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const SORTING_OPTIONS = [
        ['title', 'asc'],
        ['title', 'desc'],
        ['lastUpdated', 'asc'],
        ['lastUpdated', 'desc'],
        ['sorting', 'asc'],
        ['sorting', 'desc'],
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
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
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The list plugin sits on page 2, `/home`, so every enhanced path is `/home/…`.
     */
    private function setUpTestCase(string $dataSet = 'programListPage'): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProgramListRouteEnhancer/' . $dataSet . '.csv');
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
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'routeEnhancers' => $this->loadShippedRouteEnhancers(),
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(
                    identifier: 'EN',
                    base: '/',
                ),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function loadShippedRouteEnhancers(): array
    {
        // Parsed with the plain YAML parser rather than through TYPO3's
        // "YamlFileLoader". That loader adds "imports" resolution and placeholder
        // substitution, neither of which the shipped file uses, and it is not
        // reachable the same way on every core version the 2.x line still has to
        // support - keeping the two branches on one reader is worth more here than
        // the wrapper. The point of this test is that the shipped file itself is
        // loaded, not which reader loads it.
        $configuration = Yaml::parseFile(
            GeneralUtility::getFileAbsFileName('EXT:academic_programs/Configuration/Yaml/Routes.yaml')
        );

        $this->assertIsArray($configuration['routeEnhancers'] ?? null, 'The shipped Routes.yaml declares no routeEnhancers.');
        $this->assertNotSame([], $configuration['routeEnhancers']);

        return $configuration['routeEnhancers'];
    }

    private function generateListPluginUri(string $sortingField, string $sortingDirection): string
    {
        return (string)$this->get(SiteFinder::class)
            ->getSiteByIdentifier('acme')
            ->getRouter()
            ->generateUri(
                2,
                [
                    self::PLUGIN_NAMESPACE => [
                        'demand' => [
                            'sortingField' => $sortingField,
                            'sortingDirection' => $sortingDirection,
                        ],
                    ],
                ],
            );
    }

    /**
     * The sorting selects are rendered from the demand object the controller received, so
     * a selected option is the observable end of the arguments the router resolved.
     */
    private function assertSelectedSorting(string $content, string $sortingField, string $sortingDirection): void
    {
        $this->assertStringContainsString(sprintf('<option value="%s" selected="selected">', $sortingField), $content);
        $this->assertStringContainsString(sprintf('<option value="%s" selected="selected">', $sortingDirection), $content);
        // Sanity: the plugin really rendered, the two assertions above are not on an error page.
        $this->assertStringContainsString('academic-programs-list', $content);
    }

    #[Test]
    public function shippedRoutesYamlDeclaresAnEnhancerForTheListPlugin(): void
    {
        $enhancers = $this->loadShippedRouteEnhancers();

        $this->assertArrayHasKey('AcademicPrograms', $enhancers);
        $this->assertSame('Extbase', $enhancers['AcademicPrograms']['type']);
        $this->assertSame('AcademicPrograms', $enhancers['AcademicPrograms']['extension']);
        $this->assertSame('ProgramList', $enhancers['AcademicPrograms']['plugin']);
        // Without `routes` an Extbase enhancer builds no route variant at all: it is loaded,
        // it is valid, and it does nothing.
        $this->assertNotSame([], $enhancers['AcademicPrograms']['routes'] ?? []);
    }

    #[Test]
    public function sortingArgumentsAreGeneratedIntoThePath(): void
    {
        $this->setUpTestCase();

        $uri = $this->generateListPluginUri('lastUpdated', 'desc');

        $this->assertSame('https://www.acme.com/home/last-updated/desc', $uri);
        // The arguments went into the path, so nothing of them may be left in a query string.
        $this->assertStringNotContainsString('?', $uri);
        $this->assertStringNotContainsString(self::PLUGIN_NAMESPACE, $uri);
    }

    #[Test]
    public function everySortingFieldOfThePluginIsMappedIntoThePath(): void
    {
        $this->setUpTestCase();

        $this->assertSame('https://www.acme.com/home/title/desc', $this->generateListPluginUri('title', 'desc'));
        $this->assertSame('https://www.acme.com/home/last-updated/desc', $this->generateListPluginUri('lastUpdated', 'desc'));
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
        $this->setUpTestCase();

        $this->assertSame('https://www.acme.com/home/title/asc', $this->generateListPluginUri('title', 'asc'));
        $this->assertSame('https://www.acme.com/home/sorting/asc', $this->generateListPluginUri('sorting', 'asc'));
        $this->assertSame('https://www.acme.com/home/last-updated/asc', $this->generateListPluginUri('lastUpdated', 'asc'));
    }

    #[Test]
    public function generatedUriResolvesBackIntoThePluginArguments(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage($this->generateListPluginUri('lastUpdated', 'desc'));

        $this->assertSelectedSorting($content, 'lastUpdated', 'desc');
    }

    #[Test]
    public function everyGeneratedSortingUriResolvesBackIntoItsArguments(): void
    {
        $this->setUpTestCase();

        foreach (self::SORTING_OPTIONS as [$field, $direction]) {
            $this->assertSelectedSorting(
                $this->renderFrontendPage($this->generateListPluginUri($field, $direction)),
                $field,
                $direction,
            );
        }
    }

    /**
     * The enhanced route must not take over the plain page URL. If it did - through
     * `defaults`, which the enhancer used to declare - they would reach the controller as
     * a demand and overrule the sorting configured in the FlexForm of the content element,
     * which is the sorting a visitor sees before they ever touch the form.
     */
    #[Test]
    public function plainPageUriKeepsTheSortingConfiguredInThePlugin(): void
    {
        $this->setUpTestCase('programListPage_sortedByLastUpdatedDescending');

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSelectedSorting($content, 'lastUpdated', 'desc');
    }

    /**
     * Without `defaults` the route needs both segments, so a link to the list that carries
     * no sorting - the form's own action URL, a hand written reset link - does not enter
     * the enhancer and keeps its plugin arguments in the query string, with a cache hash.
     * It still shows the list as the content element presets it.
     */
    #[Test]
    public function aListLinkWithoutSortingKeepsItsQueryString(): void
    {
        $this->setUpTestCase();

        $uri = (string)$this->get(SiteFinder::class)
            ->getSiteByIdentifier('acme')
            ->getRouter()
            ->generateUri(2, [self::PLUGIN_NAMESPACE => ['action' => 'list', 'controller' => 'Program']]);

        $this->assertStringStartsWith('https://www.acme.com/home?', $uri);
        $this->assertStringContainsString('cHash=', $uri);
        $this->assertStringContainsString('academic-programs-list', $this->renderFrontendPage($uri));
    }

    /**
     * Both segments are required: a path with the sorting field alone is no list URL.
     */
    #[Test]
    public function aPathWithTheSortingFieldAloneDoesNotResolve(): void
    {
        $this->setUpTestCase();

        $this->assertSame(404, $this->requestFrontendPage('https://www.acme.com/home/last-updated')->getStatusCode());
    }

    #[Test]
    public function aSortingSubmissionRedirectsToTheEnhancedPath(): void
    {
        $this->setUpTestCase();

        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::PLUGIN_NAMESPACE => ['demand' => ['sortingField' => 'lastUpdated', 'sortingDirection' => 'desc']],
        ]);

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('https://www.acme.com/home/last-updated/desc', $response->getHeaderLine('Location'));
        $this->assertSelectedSorting($this->renderFrontendPage($response->getHeaderLine('Location')), 'lastUpdated', 'desc');
    }

    /**
     * The route maps the sorting only, so a category filter stays in the query string. It
     * needs no cache hash there: the demand is excluded from it, and the path segments are
     * static route arguments.
     */
    #[Test]
    public function aFilterSubmissionKeepsTheFilterInTheQueryString(): void
    {
        $this->setUpTestCase('programListPage_preselectedDegree');

        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::PLUGIN_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2']]],
        ]);

        $this->assertSame(303, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertSame('/home/title/asc', parse_url($location, PHP_URL_PATH));
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame(
            [self::PLUGIN_NAMESPACE => ['demand' => ['filterCollection' => ['categories' => '2']]]],
            $query,
        );
        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
    }

    /**
     * The content element preselects Bachelor of Science. Setting the degree back to "all"
     * with the default sorting has to reach a URL that is not the bare page, or the preset
     * applies again - which is what `defaults` in the enhancer did.
     */
    #[Test]
    public function clearingAPreselectedDegreeStaysClearedBehindTheEnhancer(): void
    {
        $this->setUpTestCase('programListPage_preselectedDegree');
        $this->assertStringNotContainsString('Molecular Chemistry', $this->renderFrontendPage('https://www.acme.com/home'));

        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::PLUGIN_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '']]],
        ]);

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('https://www.acme.com/home/title/asc', $response->getHeaderLine('Location'));
        $content = $this->renderFrontendPage($response->getHeaderLine('Location'));
        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringContainsString('Applied Physics', $content);
    }
}
