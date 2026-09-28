<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Pages;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * What an installed extension can do to the data of a program page, through
 * `ModifyProgramDataEvent`.
 *
 * `EXT:test_program_events` ships the listener. It stays inert until the TypoScript file of
 * the fixture sets its switches, and then hands back a copy of the data with another
 * subtitle and 240 credit points instead of 180. That a program page renders unchanged
 * while no extension listens is what `AcademicProgramPageTemplateTest` asserts, and it does
 * not load the fixture.
 *
 * Both shapes of page object are covered, because the data processor reads the page record
 * from a different variable for each.
 */
final class AcademicProgramPageDataEventTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const PAGES_FIXTURES = 'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-program-events');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramPageTemplateTest/page.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramPageTemplateTest/content.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function sitePackageDataProvider(): \Generator
    {
        yield 'FLUIDTEMPLATE' => [self::PAGES_FIXTURES . 'SitePackage.typoscript'];
        yield 'PAGEVIEW' => [self::PAGES_FIXTURES . 'SitePackagePageView.typoscript'];
    }

    /**
     * @param list<string> $additionalSetup TypoScript files included after the extension.
     */
    private function setUpSite(string $sitePackage, array $additionalSetup = []): void
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
                    // The site package first, the extension after it, as in the page template test.
                    $sitePackage,
                    'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                    ...$additionalSetup,
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * The text of the one credit points fact of the page.
     */
    private function creditPointsFact(string $content): string
    {
        $document = new \DOMDocument();
        $this->assertTrue(@$document->loadHTML('<?xml encoding="UTF-8">' . $content));
        $items = (new \DOMXPath($document))->query(
            '//li[contains(concat(" ", normalize-space(@class), " "), " academic-programs-facts__item--creditPoints ")]',
        );
        $this->assertInstanceOf(\DOMNodeList::class, $items);
        $this->assertCount(1, $items, 'The page renders no single credit points fact.');

        return trim((string)$items->item(0)?->textContent);
    }

    #[DataProvider('sitePackageDataProvider')]
    #[Test]
    public function aListenerReplacesTheSubtitle(string $sitePackage): void
    {
        $this->setUpSite($sitePackage, ['EXT:test_program_events/Configuration/TypoScript/ProgramData.typoscript']);

        $content = $this->renderFrontendPage('https://www.acme.com/applied-physics');

        $this->assertStringContainsString('<p class="academic-programs-detail__subtitle">Subtitle from a listener</p>', $content);
    }

    /**
     * The facts are built from the data the listener hands back, not from the data the
     * factory built.
     */
    #[DataProvider('sitePackageDataProvider')]
    #[Test]
    public function theFactsShowTheDataAListenerHandsBack(string $sitePackage): void
    {
        $this->setUpSite($sitePackage, ['EXT:test_program_events/Configuration/TypoScript/ProgramData.typoscript']);

        $fact = $this->creditPointsFact($this->renderFrontendPage('https://www.acme.com/applied-physics'));

        $this->assertStringContainsString('240', $fact);
        $this->assertStringNotContainsString('180', $fact);
    }

    /**
     * The listener is loaded but not asked for anything: the page shows the data of its
     * record.
     */
    #[DataProvider('sitePackageDataProvider')]
    #[Test]
    public function anInertListenerLeavesThePageAlone(string $sitePackage): void
    {
        $this->setUpSite($sitePackage);

        $content = $this->renderFrontendPage('https://www.acme.com/applied-physics');

        $this->assertStringNotContainsString('academic-programs-detail__subtitle', $content);
        $this->assertStringContainsString('180', $this->creditPointsFact($content));
    }
}
