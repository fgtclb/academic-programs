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

    /**
     * Every combination `FGTCLB\AcademicPrograms\Enumeration\SortingOptions` offers, split
     * into the two arguments the enhancer maps. There is deliberately no `sorting desc`:
     * the enum does not have it, and `ProgramDemand::setSorting()` silently drops a pair it
     * does not know — so a path carrying it would resolve and then be ignored.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const SORTING_OPTIONS = [
        ['title', 'asc'],
        ['title', 'desc'],
        ['lastUpdated', 'asc'],
        ['lastUpdated', 'desc'],
        ['sorting', 'asc'],
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
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
     * `defaults` in the enhancer makes trailing segments optional for generation, so a
     * value that equals its default is dropped from the end of the path: the pair the
     * plugin uses anyway produces the plain page URL rather than `/title/asc`.
     */
    #[Test]
    public function defaultSortingValuesAreOmittedFromTheEndOfTheGeneratedPath(): void
    {
        $this->setUpTestCase();

        $this->assertSame('https://www.acme.com/home', $this->generateListPluginUri('title', 'asc'));
        $this->assertSame('https://www.acme.com/home/sorting', $this->generateListPluginUri('sorting', 'asc'));
        $this->assertSame('https://www.acme.com/home/last-updated', $this->generateListPluginUri('lastUpdated', 'asc'));
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
     * The enhanced route must not take over the plain page URL. If it did, its `defaults`
     * would reach the controller as a demand and overrule the sorting configured in the
     * FlexForm of the content element — which is the sorting a visitor sees before they
     * ever touch the form.
     */
    #[Test]
    public function plainPageUriKeepsTheSortingConfiguredInThePlugin(): void
    {
        $this->setUpTestCase('programListPage_sortedByLastUpdatedDescending');

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSelectedSorting($content, 'lastUpdated', 'desc');
    }
}
