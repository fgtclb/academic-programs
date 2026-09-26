<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\CategoryFilterFormAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The category filters of the program list, as the settings `filter.visibleCount` and
 * `filter.hideDisabledOptions` shape them together with `filter.categoryTypes`, which
 * `AcademicProgramsListFilterTypesTest` covers on its own.
 *
 * The records are those of `AcademicProgramsListFilterTypesTest`: Bachelor of Science (1)
 * and Master of Science (2) are degrees, Full-time (3) is a program type, Berlin (5) and
 * Potsdam (6) are locations, and the tuition fee (7) is a category of the type `costs`
 * assigned to no program. No category of the type `teaching_language` exists.
 *
 * The list is on `/home`.
 */
final class AcademicProgramsListFilterTest extends AbstractAcademicProgramsTestCase
{
    use CategoryFilterFormAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicprograms_programlist';
    private const FORM_CLASS = 'academic-programs-filtersorting';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsListFilterTypes/records.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The site as a static template configures it. `$constants` and `$setup` are added after
     * the TypoScript of the extension, the way a site package adds its own.
     */
    private function setUpSite(string $constants = '', string $setup = ''): void
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
        $this->appendToSiteTemplate($constants, $setup);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * The same site configured through the aggregate site set and its site settings. The
     * page object comes from a `sys_template` record, which is included after the sets.
     *
     * @param non-empty-string $identifier
     * @param array<string, mixed> $settings
     */
    private function setUpSiteSetSite(string $identifier, array $settings): void
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
            // The site identifier is part of several caches the test instance keeps for the
            // whole class - the site settings among them - so every site of a set needs an
            // identifier of its own.
            identifier: $identifier,
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', 'fgtclb/academic-programs'],
                    'settings' => $settings,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }

    private function appendToSiteTemplate(string $constants, string $setup): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'constants', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update(
            'sys_template',
            ['constants' => $template['constants'] . LF . $constants, 'config' => $template['config'] . LF . $setup],
            ['uid' => $template['uid']],
        );
    }

    private function filterSettings(string $categoryTypes = '', int $visibleCount = 0, bool $hideDisabledOptions = false): string
    {
        return 'plugin.tx_academicprograms.filter.categoryTypes = ' . $categoryTypes . LF
            . 'plugin.tx_academicprograms.filter.visibleCount = ' . $visibleCount . LF
            . 'plugin.tx_academicprograms.filter.hideDisabledOptions = ' . (int)$hideDisabledOptions . LF;
    }

    /**
     * The list on `/home` after a visitor filtered it: the POST the form sends, answered with
     * a redirect, and the page that redirect leads to.
     *
     * @param array<string, string> $filterCollection
     */
    private function renderFilteredList(array $filterCollection): string
    {
        $response = $this->requestFrontendPage($this->frontendPostRequest(
            'https://www.acme.com/home',
            [self::LIST_NAMESPACE => ['demand' => ['filterCollection' => $filterCollection]]],
        ));

        return $this->renderFrontendPage($this->assertSeeOtherWithCacheHash($response));
    }

    /**
     * The markup of the filters as it was before the settings existed: every type with a
     * category in the type order of the group, one cell each, the generic "All" label, and
     * the tuition fee without a program as a disabled option.
     */
    #[Test]
    public function withoutSettingsTheFiltersRenderAsBefore(): void
    {
        $this->setUpSite();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['costs', 'degree', 'location', 'program_type'], 'more' => [], 'disclosure' => 'none', 'summary' => null],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
        $this->assertSame(self::DEFAULT_FILTER_CELLS, $this->categoryFilterCellMarkup($content, self::FORM_CLASS));
    }

    #[Test]
    public function theConfiguredFilterTypesAreOfferedInTheirOrder(): void
    {
        $this->setUpSite(constants: $this->filterSettings(categoryTypes: 'location,degree'));

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['location', 'degree'], $this->renderedCategoryFilters($content, self::FORM_CLASS)['visible']);
    }

    /**
     * `teaching_language` has no category and is left out before the count applies, so
     * the count is one of the filters that render.
     */
    #[Test]
    public function theFiltersAfterTheVisibleCountAreBehindMoreFilters(): void
    {
        $this->setUpSite(constants: $this->filterSettings(categoryTypes: 'teaching_language,location,degree,program_type', visibleCount: 1));

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['location'], 'more' => ['degree', 'program_type'], 'disclosure' => 'closed', 'summary' => 'More filters'],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
    }

    #[Test]
    public function aVisibleCountCoveringEveryFilterRendersNoDisclosure(): void
    {
        $this->setUpSite(constants: $this->filterSettings(visibleCount: 4));

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['costs', 'degree', 'location', 'program_type'], 'more' => [], 'disclosure' => 'none', 'summary' => null],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
    }

    /**
     * @return \Generator<string, array{0: array<string, string>, 1: string}>
     */
    public static function activeFilterDataProvider(): \Generator
    {
        yield 'a filter behind the disclosure' => [['degree' => '1'], 'open'];
        yield 'a filter shown right away' => [['location' => '5'], 'closed'];
        yield 'a filter behind the disclosure, cleared' => [['degree' => ''], 'closed'];
    }

    /**
     * @param array<string, string> $filterCollection
     */
    #[DataProvider('activeFilterDataProvider')]
    #[Test]
    public function moreFiltersIsOpenWhileOneOfItsFiltersIsActive(array $filterCollection, string $disclosure): void
    {
        $this->setUpSite(constants: $this->filterSettings(categoryTypes: 'location,degree,program_type', visibleCount: 1));

        $content = $this->renderFilteredList($filterCollection);

        $this->assertSame($disclosure, $this->renderedCategoryFilters($content, self::FORM_CLASS)['disclosure']);
    }

    /**
     * The label key has dots of its own. However the TypoScript is written - as a dotted
     * path, with the dots of the key escaped, in a block, or with the shared label and one
     * of its type on the same node - Extbase flattens it to the same key, on every supported
     * TYPO3 version.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function labelTypoScriptFormDataProvider(): \Generator
    {
        yield 'dotted path' => [
            'plugin.tx_academicprograms._LOCAL_LANG.default.sys_category.programs.allOptions.degree = All degrees',
            'All degrees',
            'All options',
        ];
        yield 'escaped dots' => [
            'plugin.tx_academicprograms._LOCAL_LANG.default.sys_category\\.programs\\.allOptions\\.degree = All degrees',
            'All degrees',
            'All options',
        ];
        yield 'block' => [
            "plugin.tx_academicprograms._LOCAL_LANG.default {\n  sys_category.programs.allOptions.degree = All degrees\n}",
            'All degrees',
            'All options',
        ];
        yield 'shared label and one of its type' => [
            "plugin.tx_academicprograms._LOCAL_LANG.default {\n  sys_category.programs.allOptions = Any\n  sys_category.programs.allOptions.degree = All degrees\n}",
            'All degrees',
            'Any',
        ];
    }

    #[DataProvider('labelTypoScriptFormDataProvider')]
    #[Test]
    public function theLabelOverrideIsReadInEveryTypoScriptForm(string $setup, string $degreeLabel, string $locationLabel): void
    {
        $this->setUpSite(setup: $setup);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame($degreeLabel, $this->categoryFilterOptions($content, self::FORM_CLASS, 'degree')[0] ?? null);
        $this->assertSame($locationLabel, $this->categoryFilterOptions($content, self::FORM_CLASS, 'location')[0] ?? null);
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function labelOverrideDataProvider(): \Generator
    {
        yield 'extension' => ['https://www.acme.com/home', 'plugin.tx_academicprograms'];
        yield 'plugin' => ['https://www.acme.com/home', 'plugin.tx_academicprograms_programlist'];
    }

    /**
     * A label of its own for one type, set through `_LOCAL_LANG` of the extension or of the
     * plugin, and none for another: the one reads its own, the other the label every filter
     * read before. Up to TYPO3 v13 the extension path is only read because the partial
     * passes the extension name in UpperCamelCase.
     */
    #[DataProvider('labelOverrideDataProvider')]
    #[Test]
    public function theAllOptionReadsALabelOfItsTypeWhenOneExists(string $url, string $typoScriptPath): void
    {
        $this->setUpSite(setup: $typoScriptPath . '._LOCAL_LANG.default.sys_category.programs.allOptions.degree = All degrees');

        $content = $this->renderFrontendPage($url);

        $this->assertSame(['All degrees', 'Bachelor of Science', 'Master of Science'], $this->categoryFilterOptions($content, self::FORM_CLASS, 'degree'));
        $this->assertSame(['All options', 'Berlin', 'Potsdam'], $this->categoryFilterOptions($content, self::FORM_CLASS, 'location'));
    }

    /**
     * The tuition fee is the one category of its type, so the filter keeps nothing but the
     * "All" option: a filter whose options are all left out still renders, so the form keeps
     * its layout while a visitor narrows the list.
     */
    #[Test]
    public function optionsWithoutProgramsAreLeftOutOnDemand(): void
    {
        $this->setUpSite(constants: $this->filterSettings(hideDisabledOptions: true));

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['All options'], $this->categoryFilterOptions($content, self::FORM_CLASS, 'costs'));
    }

    #[Test]
    public function theSiteSettingsConfigureTheFilters(): void
    {
        $this->setUpSiteSetSite('acme-site-settings', [
            'plugin.tx_academicprograms.filter.categoryTypes' => 'location,costs',
            'plugin.tx_academicprograms.filter.visibleCount' => 1,
            'plugin.tx_academicprograms.filter.hideDisabledOptions' => true,
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['location'], 'more' => ['costs'], 'disclosure' => 'closed', 'summary' => 'More filters'],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
        $this->assertSame(['All options'], $this->categoryFilterOptions($content, self::FORM_CLASS, 'costs'));
    }

    /**
     * The site set without any site setting renders what the static template renders
     * without a constant: the declared defaults are the defaults of the constants - no
     * filter left out, none behind "More filters", no option hidden.
     */
    #[Test]
    public function theSiteSetDefaultsRenderTheFiltersAsBefore(): void
    {
        $this->setUpSiteSetSite('acme-site-set-defaults', []);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['costs', 'degree', 'location', 'program_type'], 'more' => [], 'disclosure' => 'none', 'summary' => null],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
        $this->assertSame(self::DEFAULT_FILTER_CELLS, $this->categoryFilterCellMarkup($content, self::FORM_CLASS));
    }

    /**
     * The cells of the filters before the settings existed.
     */
    private const DEFAULT_FILTER_CELLS = [
        '<div class="col-12 col-md-6 col-lg-4 col-xl-3"><label for="costs" class="form-label"> Costs </label><select onchange="this.form.submit()" id="costs" class="form-select" name="tx_academicprograms_programlist[demand][filterCollection][costs]"><option value="">All options</option><option value="7" class="level-0" disabled>Tuition fee</option></select></div>',
        '<div class="col-12 col-md-6 col-lg-4 col-xl-3"><label for="degree" class="form-label"> Degree </label><select onchange="this.form.submit()" id="degree" class="form-select" name="tx_academicprograms_programlist[demand][filterCollection][degree]"><option value="">All options</option><option value="1" class="level-0">Bachelor of Science</option><option value="2" class="level-0">Master of Science</option></select></div>',
        '<div class="col-12 col-md-6 col-lg-4 col-xl-3"><label for="location" class="form-label"> Location </label><select onchange="this.form.submit()" id="location" class="form-select" name="tx_academicprograms_programlist[demand][filterCollection][location]"><option value="">All options</option><option value="5" class="level-0">Berlin</option><option value="6" class="level-0">Potsdam</option></select></div>',
        '<div class="col-12 col-md-6 col-lg-4 col-xl-3"><label for="program_type" class="form-label"> Type of program </label><select onchange="this.form.submit()" id="program_type" class="form-select" name="tx_academicprograms_programlist[demand][filterCollection][program_type]"><option value="">All options</option><option value="3" class="level-0">Full-time</option></select></div>',
    ];
}
