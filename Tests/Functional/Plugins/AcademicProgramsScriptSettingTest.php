<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\Set\SetRegistry;

/**
 * The switch `plugin.tx_academicprograms.assets.js`, which decides whether the program
 * list and the program finder load their scripts: the list that updates itself in place,
 * and the finder that narrows its options in the browser.
 *
 * It exists twice under one name, as a site setting of the aggregate set and as a
 * TypoScript constant for a site configured through `sys_template` records, so both
 * delivery mechanisms are covered here. The finder is on `/home`, the list it submits to
 * on `/programs`.
 */
final class AcademicProgramsScriptSettingTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const AGGREGATE_SET = 'fgtclb/academic-programs';
    private const SETTING = 'plugin.tx_academicprograms.assets.js';

    /**
     * The bare specifiers of the two modules. Each reaches the page in the statement that
     * imports it, and nowhere when no module was registered.
     */
    private const FINDER_MODULE = '@fgtclb/academic-programs/frontend/program-finder.js';
    private const LIST_MODULE = '@fgtclb/academic-programs/frontend/program-list.js';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsFinder/records.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function theAggregateSetDeclaresTheSettingSwitchedOn(): void
    {
        $definitions = [];
        foreach ($this->get(SetRegistry::class)->getSet(self::AGGREGATE_SET)?->settingsDefinitions ?? [] as $definition) {
            $definitions[$definition->key] = $definition;
        }

        $this->assertArrayHasKey(self::SETTING, $definitions);
        $this->assertSame('bool', $definitions[self::SETTING]->type);
        $this->assertTrue($definitions[self::SETTING]->default);
    }

    #[Test]
    #[DataProvider('deliveryMechanisms')]
    public function theListAndTheFinderLoadTheirScriptsWhenTheSiteConfiguresNothing(bool $siteSet): void
    {
        $siteSet ? $this->setUpSiteSetSite() : $this->setUpStaticTemplateSite();

        $this->assertStringContainsString(self::FINDER_MODULE, $this->renderFrontendPage('https://www.acme.com/home'));
        $this->assertStringContainsString(self::LIST_MODULE, $this->renderFrontendPage('https://www.acme.com/programs'));
    }

    public static function deliveryMechanisms(): \Generator
    {
        yield 'site set' => [true];
        yield 'static template' => [false];
    }

    #[Test]
    public function theSiteSettingSwitchesTheScriptsOff(): void
    {
        $this->setUpSiteSetSite([self::SETTING => false]);

        $this->assertElementsWithoutScripts();
    }

    #[Test]
    public function theTypoScriptConstantSwitchesTheScriptsOff(): void
    {
        $this->setUpStaticTemplateSite(['EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/NoScript.typoscript']);

        $this->assertElementsWithoutScripts();
    }

    /**
     * No module on either page - the list template and the three partials of its form each
     * register the list module, and none of them may - and both forms unchanged, with the
     * button a visitor without a script submits them with.
     */
    private function assertElementsWithoutScripts(): void
    {
        $finder = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertStringNotContainsString(self::FINDER_MODULE, $finder);
        $this->assertStringNotContainsString(self::LIST_MODULE, $finder);
        $this->assertStringContainsString('data-academic-programs-finder-programs', $finder);
        $this->assertMatchesRegularExpression('/<button[^>]*type="submit"/', $finder);

        $list = $this->renderFrontendPage('https://www.acme.com/programs');
        $this->assertStringNotContainsString(self::LIST_MODULE, $list);
        $this->assertStringNotContainsString(self::FINDER_MODULE, $list);
        $this->assertStringContainsString('data-academic-programs-list-form', $list);
        $this->assertStringContainsString('data-academic-programs-list-submit', $list);
    }

    /**
     * @param list<string> $additionalConstants Read after the shipped constants, which is
     *        where an integrator overrides one.
     */
    private function setUpStaticTemplateSite(array $additionalConstants = []): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/constants.typoscript',
                    ...$additionalConstants,
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

    /**
     * @param array<string, bool> $settings
     */
    private function setUpSiteSetSite(array $settings = []): void
    {
        // The page object only. "clear = 0" keeps what the sets contribute.
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => '@import \'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript\'',
            ],
        );
        $this->writeSiteConfiguration(
            // The site identifier is part of several caches the test instance keeps for
            // the whole class, so differently configured sites need different ones.
            identifier: 'acme-' . substr(md5(json_encode($settings, JSON_THROW_ON_ERROR)), 0, 10),
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', self::AGGREGATE_SET],
                    'settings' => $settings,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }
}
