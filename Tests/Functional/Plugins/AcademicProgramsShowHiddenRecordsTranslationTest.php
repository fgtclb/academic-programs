<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The plugin option "Show hidden records" of the `academicprograms_programlist` plugin on a translated
 * page (ACE-857).
 *
 * The fixture holds a visible and a hidden program page, each with a German translation
 * that shares the visibility of its default page, and a list plugin translated to German
 * with the option switched on. The query lifts the hidden flag through its own settings,
 * while the language overlay follows the visibility of the context, so the hidden
 * program used to appear with its English title on TYPO3 v13. TYPO3 v14.3.7 overlays
 * with the ignored enable fields of the query settings itself.
 */
final class AcademicProgramsShowHiddenRecordsTranslationTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsShowHiddenRecordsTranslation/programListPage.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    private function setUpSite(string $fallbackType): void
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
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: $fallbackType === 'strict' ? [] : ['EN'],
                fallbackType: $fallbackType,
            ),
        ]);
    }

    private function switchOffShowHiddenRecords(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        foreach ([1, 11] as $uid) {
            $flexForm = (string)$connection->select(['pi_flexform'], 'tt_content', ['uid' => $uid])->fetchOne();
            $connection->update(
                'tt_content',
                [
                    'pi_flexform' => str_replace(
                        '<field index="settings.showHiddenRecords"><value index="vDEF">1</value></field>',
                        '<field index="settings.showHiddenRecords"><value index="vDEF">0</value></field>',
                        $flexForm,
                    ),
                ],
                ['uid' => $uid],
            );
        }
    }

    /**
     * `strict` is what the development seed configures, `fallback` the other mode that
     * overlays records.
     *
     * @return array<string, array{'strict'|'fallback'}>
     */
    public static function fallbackTypes(): array
    {
        return [
            'fallback type "strict"' => ['strict'],
            'fallback type "fallback"' => ['fallback'],
        ];
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function translatedListShowsTheTranslationOfAHiddenProgram(string $fallbackType): void
    {
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertStringContainsString('Angewandte Physik', $german);
        $this->assertStringContainsString('Molekulare Chemie', $german);
        $this->assertStringNotContainsString('Molecular Chemistry', $german);
        $this->assertStringNotContainsString('Applied Physics', $german);
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function defaultLanguageListShowsTheHiddenProgram(string $fallbackType): void
    {
        $this->setUpSite($fallbackType);

        $english = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString('Applied Physics', $english);
        $this->assertStringContainsString('Molecular Chemistry', $english);
        $this->assertStringNotContainsString('Molekulare Chemie', $english);
    }

    /**
     * A hidden program without a translation follows the fallback type like any other
     * record: left out with `strict`, rendered in the default language with `fallback`.
     *
     * @return array<string, array{'strict'|'fallback', bool}>
     */
    public static function fallbackTypesRenderingUntranslatedRecords(): array
    {
        return [
            'fallback type "strict"' => ['strict', false],
            'fallback type "fallback"' => ['fallback', true],
        ];
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypesRenderingUntranslatedRecords')]
    #[Test]
    public function translatedListShowsAnUntranslatedHiddenProgramAsTheFallbackTypeSays(string $fallbackType, bool $rendered): void
    {
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertSame($rendered, str_contains($german, 'Untranslated Biology'));
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function translatedListLeavesTheHiddenProgramOutWithoutTheOption(string $fallbackType): void
    {
        $this->switchOffShowHiddenRecords();
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertStringContainsString('Angewandte Physik', $german);
        $this->assertStringNotContainsString('Molekulare Chemie', $german);
        $this->assertStringNotContainsString('Molecular Chemistry', $german);
    }

    /**
     * A default record and its translation that differ in visibility, with
     * `fallbackType: strict`: each is listed once, with its translation.
     */
    #[Test]
    public function strictTranslatedListShowsRecordsWhoseTranslationDiffersInVisibility(): void
    {
        $this->setUpSite('strict');

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertSame(1, substr_count($german, 'Gemischt Verborgene Uebersetzung'));
        $this->assertSame(1, substr_count($german, 'Gemischt Sichtbare Uebersetzung'));
        $this->assertStringNotContainsString('Mixed Visible Default', $german);
        $this->assertStringNotContainsString('Mixed Hidden Default', $german);
    }

    /**
     * The same with `fallbackType: fallback`.
     */
    #[Test]
    public function fallbackTranslatedListShowsRecordsWhoseTranslationDiffersInVisibility(): void
    {
        $this->setUpSite('fallback');

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertSame(1, substr_count($german, 'Gemischt Verborgene Uebersetzung'));
        $this->assertSame(1, substr_count($german, 'Gemischt Sichtbare Uebersetzung'));
        $this->assertStringNotContainsString('Mixed Visible Default', $german);
        $this->assertStringNotContainsString('Mixed Hidden Default', $german);
    }

    /**
     * The start time of a default record keeps deciding for its translation, which has
     * none of its own, as it does without the option.
     *
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function translatedListLeavesTheTranslationOfAScheduledRecordOut(string $fallbackType): void
    {
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertStringNotContainsString('Geplante Uebersetzung', $german);
        $this->assertStringNotContainsString('Scheduled Default Record', $german);
    }

    /**
     * The end time of a default record keeps deciding for its translation, which has
     * none of its own, as it does without the option.
     *
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function translatedListLeavesTheTranslationOfAnExpiredRecordOut(string $fallbackType): void
    {
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertStringNotContainsString('Abgelaufene Uebersetzung', $german);
        $this->assertStringNotContainsString('Expired Default Record', $german);
    }
}
