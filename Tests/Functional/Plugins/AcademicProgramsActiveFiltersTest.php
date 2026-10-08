<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ActiveFiltersAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The active filter tags, the reset link and the result count of the program list, switched
 * on by `filter.showActiveFilters`, `filter.showReset` and `filter.showResultCount`.
 *
 * Categories: Bachelor of Science (1) and Master of Science (2) are degrees, English (3) and
 * German (4) teaching languages. Applied Physics and Data Science are bachelor programs
 * taught in English, Molecular Chemistry is a master program taught in German.
 *
 * - `/home`: the list.
 * - `/bachelor`: a list with Bachelor of Science preselected by the editor.
 * - `/filter-hidden`: a list whose filter is hidden.
 */
final class AcademicProgramsActiveFiltersTest extends AbstractAcademicProgramsTestCase
{
    use ActiveFiltersAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    /**
     * The element the tags, the reset link and the result count are rendered into, in front
     * of the results of the list.
     */
    private const ACTIVE_FILTERS = '//*[@data-academic-programs-list-content]';
    private const ACTIVE_FILTER_PARTS = self::ACTIVE_FILTERS . '/*[self::ul[contains(concat(" ", normalize-space(@class), " "), " ace-active-filters ")] or self::a]';
    private const RESULT_COUNT = '//*[@data-academic-programs-list-content]/p[1]';
    private const LIST_NAMESPACE = 'tx_academicprograms_programlist';
    private const ALL_ON = "plugin.tx_academicprograms.filter.showActiveFilters = 1\n"
        . "plugin.tx_academicprograms.filter.showReset = 1\n"
        . "plugin.tx_academicprograms.filter.showResultCount = 1\n";

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsActiveFilters/programListPages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function everyActiveFilterIsATagThatRemovesOnlyThatFilter(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/home', '1,3'));

        $tags = $this->activeFilterTags($content, self::ACTIVE_FILTERS);
        $this->assertSame(['Bachelor of Science', 'English'], array_keys($tags));
        $this->assertSame(
            ['filterCollection' => ['categories' => '3'], 'sortingDirection' => 'desc', 'sortingField' => 'title'],
            $this->activeFiltersDemand($tags['Bachelor of Science']['href'], self::LIST_NAMESPACE),
        );
        $this->assertSame(
            ['filterCollection' => ['categories' => '1'], 'sortingDirection' => 'desc', 'sortingField' => 'title'],
            $this->activeFiltersDemand($tags['English']['href'], self::LIST_NAMESPACE),
        );
        $this->assertSame('Remove filter: English', $tags['English']['label']);
        $this->assertSame('/home', $this->activeFiltersResetLink($content, self::ACTIVE_FILTERS));
        $this->assertSame('2 programs found', $this->activeFiltersResultCount($content, self::RESULT_COUNT));
    }

    #[Test]
    public function oneMatchingProgramIsCountedInTheSingular(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/home', '2'));

        $tags = $this->activeFilterTags($content, self::ACTIVE_FILTERS);
        $this->assertSame(['Master of Science'], array_keys($tags));
        $this->assertSame(
            ['sortingDirection' => 'desc', 'sortingField' => 'title'],
            $this->activeFiltersDemand($tags['Master of Science']['href'], self::LIST_NAMESPACE),
        );
        $this->assertSame('1 program found', $this->activeFiltersResultCount($content, self::RESULT_COUNT));
    }

    /**
     * The preselection is a tag. The reset link would lead to the page shown, so it is
     * offered only once the visitor selected something, and then leads back to it.
     */
    #[Test]
    public function thePreselectionIsATagAndTheResetLinkReturnsToIt(): void
    {
        $this->setUpSite(self::ALL_ON);

        $preset = $this->renderFrontendPage('https://www.acme.com/bachelor');
        $this->assertSame(['Bachelor of Science'], array_keys($this->activeFilterTags($preset, self::ACTIVE_FILTERS)));
        $this->assertNull($this->activeFiltersResetLink($preset, self::ACTIVE_FILTERS));
        $this->assertSame('2 programs found', $this->activeFiltersResultCount($preset, self::RESULT_COUNT));

        $withoutPreselection = $this->renderFrontendPage('https://www.acme.com' . $this->activeFilterTags($preset, self::ACTIVE_FILTERS)['Bachelor of Science']['href']);
        $this->assertSame([], $this->activeFilterTags($withoutPreselection, self::ACTIVE_FILTERS));
        $this->assertSame('/bachelor', $this->activeFiltersResetLink($withoutPreselection, self::ACTIVE_FILTERS));
        $this->assertSame('3 programs found', $this->activeFiltersResultCount($withoutPreselection, self::RESULT_COUNT));

        $selected = $this->renderFrontendPage($this->listUrl('/bachelor', '4'));
        $this->assertSame(['German'], array_keys($this->activeFilterTags($selected, self::ACTIVE_FILTERS)));
        $this->assertSame('/bachelor', $this->activeFiltersResetLink($selected, self::ACTIVE_FILTERS));
    }

    #[Test]
    public function aHiddenFilterShowsNoTagsButTheCount(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/filter-hidden', '1,3'));

        $this->assertSame(0, $this->activeFiltersQuery($content, self::ACTIVE_FILTER_PARTS)->length);
        $this->assertSame('2 programs found', $this->activeFiltersResultCount($content, self::RESULT_COUNT));
    }

    /**
     * The settings are off by default: a site that sets none of them renders the list as
     * before, whatever the visitor filtered.
     */
    #[Test]
    public function nothingIsRenderedWithoutTheSettings(): void
    {
        $this->setUpSite();

        $content = $this->renderFrontendPage($this->listUrl('/home', '1,3'));

        $this->assertSame(0, $this->activeFiltersQuery($content, self::ACTIVE_FILTER_PARTS)->length);
        $this->assertSame(0, $this->activeFiltersQuery($content, self::RESULT_COUNT)->length);
    }

    #[Test]
    public function theSettingsAreSiteSettings(): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => "@import 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript'",
            ],
        );
        $this->writeSiteConfiguration(
            identifier: 'active-filters',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', 'fgtclb/academic-programs'],
                    'settings' => [
                        'plugin.tx_academicprograms.filter.showActiveFilters' => true,
                        'plugin.tx_academicprograms.filter.showReset' => true,
                        'plugin.tx_academicprograms.filter.showResultCount' => true,
                    ],
                ],
            ),
            languages: [$this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/')],
        );

        $content = $this->renderFrontendPage($this->listUrl('/home', '1,3'));

        $this->assertSame(['Bachelor of Science', 'English'], array_keys($this->activeFilterTags($content, self::ACTIVE_FILTERS)));
        $this->assertSame('/home', $this->activeFiltersResetLink($content, self::ACTIVE_FILTERS));
        $this->assertSame('2 programs found', $this->activeFiltersResultCount($content, self::RESULT_COUNT));
    }

    /**
     * The site as a static template configures it, with `$constants` added after the
     * constants of the extension.
     */
    private function setUpSite(string $constants = ''): void
    {
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
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['constants' => $template['constants'] . "\n" . $constants], ['uid' => $template['uid']]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * A list URL as a filter submission redirects to, sorted by title descending. The demand
     * is excluded from the cHash, so it needs none.
     */
    private function listUrl(string $path, string $categories): string
    {
        $demand = ['sortingField' => 'title', 'sortingDirection' => 'desc'];
        if ($categories !== '') {
            $demand['filterCollection'] = ['categories' => $categories];
        }

        return 'https://www.acme.com' . $path . '?' . http_build_query([self::LIST_NAMESPACE => ['demand' => $demand]]);
    }
}
