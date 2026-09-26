<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Label overrides through `_LOCAL_LANG` reach every kind of translation this extension
 * renders, on every supported core version: a label comes from `locallang.xlf`, an override
 * of the extension (`plugin.tx_academicprograms`) replaces it, and an override of the plugin
 * (`plugin.tx_academicprograms_programlist`) replaces both.
 *
 * TYPO3 v12 and v13 build the TypoScript path from the extension name a translation passes,
 * only lowercased, so a name with underscores read `plugin.tx_academic_programs` instead.
 *
 * TYPO3 v12 and v13 also keep the labels of a language file, overrides included,
 * for the rest of the request: once one translation has read the right path, the ones after
 * it show its overrides whatever name they pass. A case therefore proves its own call only
 * as the first translation of the file: the plugins hide the category filter, whose partial
 * translates correctly since ACE-739, and the sorting option is rendered from a partial of
 * this test that holds the select alone, so the options are read with the default name of
 * the view helper. The cases after the first one are held by the check of the extension
 * names of all translations.
 *
 * The list is on `/home`, and on `/empty` restricted to a folder without programs. Applied
 * Physics is a Bachelor of Science with 180 credit points, rendered by the page template of
 * its page type.
 */
final class AcademicProgramsLabelOverrideTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsLabelOverride/pages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<non-empty-string> $setupFiles
     */
    private function setUpSite(array $setupFiles, string $setup): void
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
                    ...$setupFiles,
                ],
            ],
        );
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['config' => $template['config'] . LF . $setup], ['uid' => $template['uid']]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    private function renderPluginPage(string $url, string $setup, ?string $partialRootPath = null): string
    {
        if ($partialRootPath !== null) {
            $setup .= LF . 'plugin.tx_academicprograms.view.partialRootPaths.100 = ' . $partialRootPath;
        }
        $this->setUpSite(
            [
                'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
            ],
            $setup,
        );

        return $this->normalizeWhitespace($this->renderFrontendPage($url));
    }

    /**
     * The program page, rendered by the page template of the page type - outside of any
     * plugin, so only the extension path applies.
     */
    private function renderProgramPage(string $setup): string
    {
        $this->setUpSite(
            [
                'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackage.typoscript',
                'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
            ],
            $setup,
        );

        return $this->normalizeWhitespace($this->renderFrontendPage('https://www.acme.com/applied-physics'));
    }

    private function normalizeWhitespace(string $content): string
    {
        return (string)preg_replace('/\s+/', ' ', $content);
    }

    /**
     * @param array<string, string> $overrides TypoScript path => label
     */
    private function localLang(string $key, array $overrides): string
    {
        $setup = '';
        foreach ($overrides as $path => $label) {
            $setup .= $path . '._LOCAL_LANG.default.' . $key . ' = ' . $label . LF;
        }
        return $setup;
    }

    /**
     * Every kind of translation the list renders: a label of a partial, one whose call spans
     * several lines, and the options of the sorting select, which its view helper translates
     * in PHP with a full `LLL:` reference.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: string, 3: string, 4?: string}>
     */
    public static function pluginTranslationDataProvider(): \Generator
    {
        yield 'label of the sorting partial' => [
            'https://www.acme.com/home', 'sorting.field.label', 'Sorting field',
            '<label for="sortingField" class="form-label"> %s </label>',
        ];
        yield 'sorting option, translated by the view helper' => [
            'https://www.acme.com/home', 'sorting.field.title', 'Title',
            '<option value="title" selected="selected">%s</option>',
            'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/AcademicProgramsLabelOverride/SortingSelectOnly/',
        ];
        yield 'degree of the program card, call over several lines' => [
            'https://www.acme.com/home', 'sys_category.programs.degree', 'Degree',
            '<b> %s: </b> <span> Bachelor of Science </span>',
        ];
        yield 'message of an empty list' => [
            'https://www.acme.com/empty', 'list.noProgramsFound', 'No programs found.',
            '<span>%s</span>',
        ];
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function thePluginRendersTheLabelOfTheLanguageFile(string $url, string $key, string $label, string $markup, ?string $partialRootPath = null): void
    {
        $this->assertStringContainsString(sprintf($markup, $label), $this->renderPluginPage($url, '', $partialRootPath));
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function thePluginRendersTheLabelOfTheExtension(string $url, string $key, string $label, string $markup, ?string $partialRootPath = null): void
    {
        $content = $this->renderPluginPage($url, $this->localLang($key, [
            'plugin.tx_academicprograms' => 'Extension label',
        ]), $partialRootPath);

        $this->assertStringContainsString(sprintf($markup, 'Extension label'), $content);
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function thePluginRendersTheLabelOfThePlugin(string $url, string $key, string $label, string $markup, ?string $partialRootPath = null): void
    {
        $content = $this->renderPluginPage($url, $this->localLang($key, [
            'plugin.tx_academicprograms_programlist' => 'Plugin label',
        ]), $partialRootPath);

        $this->assertStringContainsString(sprintf($markup, 'Plugin label'), $content);
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function theLabelOfThePluginWinsOverTheOneOfTheExtension(string $url, string $key, string $label, string $markup, ?string $partialRootPath = null): void
    {
        $content = $this->renderPluginPage($url, $this->localLang($key, [
            'plugin.tx_academicprograms' => 'Extension label',
            'plugin.tx_academicprograms_programlist' => 'Plugin label',
        ]), $partialRootPath);

        $this->assertStringContainsString(sprintf($markup, 'Plugin label'), $content);
        $this->assertStringNotContainsString('Extension label', $content);
    }

    /**
     * The page template translates the category types of the program in a partial, and the
     * program fields it shows, both with a key built from a variable.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function pageTranslationDataProvider(): \Generator
    {
        yield 'category type, key from a variable' => [
            'sys_category.programs.degree', 'Degree', '<b>%s:</b> <span> Bachelor of Science </span>',
        ];
        yield 'program field, key from a variable' => ['program.creditPoints', 'Credit points', '<b>%s</b> <span>180</span>'];
    }

    #[DataProvider('pageTranslationDataProvider')]
    #[Test]
    public function thePageTemplateRendersTheLabelOfTheLanguageFile(string $key, string $label, string $markup): void
    {
        $this->assertStringContainsString(sprintf($markup, $label), $this->renderProgramPage(''));
    }

    #[DataProvider('pageTranslationDataProvider')]
    #[Test]
    public function thePageTemplateRendersTheLabelOfTheExtension(string $key, string $label, string $markup): void
    {
        $content = $this->renderProgramPage($this->localLang($key, [
            'plugin.tx_academicprograms' => 'Extension label',
        ]));

        $this->assertStringContainsString(sprintf($markup, 'Extension label'), $content);
    }
}
