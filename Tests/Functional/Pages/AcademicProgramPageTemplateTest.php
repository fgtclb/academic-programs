<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Pages;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders a page of the page type this extension registers, on a site package that
 * derives the Fluid template name from the backend layout.
 *
 * That derivation is what the shipped page template has to survive, and it does not
 * survive it by itself: "case = uppercamelcase" lowercases the whole string before
 * camel casing it, so the registered layout "pagets__AcademicProgram" arrives as "Academicprogram"
 * and Fluid finds no such file. The extension therefore sets "page.10.templateName"
 * inside its own page type condition, and this test is what keeps it set.
 *
 * Remove those two lines from "Configuration/TypoScript/Page/AcademicPrograms.typoscript" and the page
 * renders the site package's fallback template instead - which is what the second
 * assertion is for.
 */
final class AcademicProgramPageTemplateTest extends AbstractAcademicProgramsTestCase
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
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpTestCase(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramPageTemplateTest/page.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    // The site package first, the extension after it - see the fixture.
                    'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackage.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    #[Test]
    public function pageTemplateIsResolvedOnASitePackageDerivingTheNameFromTheBackendLayout(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/applied-physics');

        $this->assertStringContainsString('academic-programs-detail', $content);
        $this->assertStringNotContainsString('site-package-default-template', $content);
    }
}
