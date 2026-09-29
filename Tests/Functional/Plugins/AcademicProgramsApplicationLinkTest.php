<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\ResponsiveImageAssertionTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The default list item does not render the application link, and an override of it can:
 * the Extbase model of the list carries the link and its label, and the partial of the
 * program page needs nothing else. The override renders it for four programs: one with a
 * label, one without a link, one without a label and one linking a hidden page.
 */
final class AcademicProgramsApplicationLinkTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use ResponsiveImageAssertionTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsApplicationLink/records.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $constants
     */
    private function setUpSite(array $constants = []): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/constants.typoscript',
                    ...$constants,
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

    #[Test]
    public function listItemOverrideRendersTheApplicationLinkOfEachProgram(): void
    {
        $this->setUpSite([
            'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/ApplicationLinkItemOverride.typoscript',
        ]);

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/home'));

        $withLink = $this->elementMatching($xpath, '//*[@data-title="Applied Physics"]');
        $link = $this->elementMatching($xpath, './/a', $withLink);
        $this->assertSame('/apply', $link->getAttribute('href'));
        $this->assertSame('Apply online', trim($link->textContent));

        $withoutLabel = $this->elementMatching($xpath, '//*[@data-title="Nuclear Physics"]');
        $link = $this->elementMatching($xpath, './/a', $withoutLabel);
        $this->assertSame('/apply', $link->getAttribute('href'));
        $this->assertSame('Apply now', trim($link->textContent));

        $withoutLink = $this->elementMatching($xpath, '//*[@data-title="Molecular Chemistry"]');
        $this->assertSame(0, $this->countNodesMatching($xpath, './/a', $withoutLink));

        $hiddenTarget = $this->elementMatching($xpath, '//*[@data-title="Zoology"]');
        $this->assertSame(0, $this->countNodesMatching($xpath, './/a', $hiddenTarget));
        $this->assertStringNotContainsString('Apply online', (string)$hiddenTarget->textContent);
    }

    #[Test]
    public function defaultListItemRendersNoApplicationLink(): void
    {
        $this->setUpSite();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Apply online', $content);
        $this->assertStringNotContainsString('href="/apply"', $content);
    }
}
