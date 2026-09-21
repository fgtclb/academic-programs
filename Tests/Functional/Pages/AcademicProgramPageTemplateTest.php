<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Pages;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\ResponsiveImageAssertionTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        copy(__DIR__ . '/Fixtures/AcademicProgramPageTemplateTest/Files/landscape.jpg', $folder . '/landscape.jpg');
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
                    'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/PartialRootPathProbe.typoscript',
                    // The page template renders "styles.content.getContent", which only this component
                    // assigns - it is opt-in since the configuration was cut per component.
                    'EXT:academic_programs/Configuration/TypoScript/ContentLoad/setup.typoscript',
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

    /**
     * The same site, rendered by a PAGEVIEW page object instead of a FLUIDTEMPLATE one.
     */
    private function setUpPageViewTestCase(): void
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
                    'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackagePageView.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                    // The page template renders "styles.content.getContent", which only this component
                    // assigns - it is opt-in since the configuration was cut per component.
                    'EXT:academic_programs/Configuration/TypoScript/ContentLoad/setup.typoscript',
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

    /**
     * The page media goes through the shared image partial of academic_base with the
     * `detail` preset: three sources, and a fallback capped at 1200 pixels - which leaves
     * the 800 pixel wide fixture at its own width, because `maxWidth` never enlarges.
     */
    #[Test]
    public function programPageShowsItsMediaAsAResponsivePicture(): void
    {
        $this->setUpTestCase();

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/applied-physics'));
        $detail = $this->elementMatching($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-programs-detail ')]");
        $this->assertRendersResponsivePicture(
            $xpath,
            $detail,
            3,
            800,
            'img-fluid',
            'The laboratory of the Applied Physics programme',
        );
    }

    /**
     * The extension adds its partial root paths to `page.10`, which belongs to the site
     * package. The fixture site package holds a path of its own at the key `0` and one of
     * the project at `1`, the shape `bk2k/bootstrap-package` has, and both have to survive
     * that - a key the extension picked twice would silently replace one of them.
     */
    #[Test]
    public function pageObjectKeepsThePartialRootPathsOfTheSitePackage(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/applied-physics');

        $this->assertStringContainsString(
            '<div id="theme-partial-path">EXT:academic_programs/Tests/Functional/Pages/Fixtures/ThemePartials/</div>',
            $content,
        );
        $this->assertStringContainsString(
            '<div id="project-partial-path">EXT:academic_programs/Tests/Functional/Pages/Fixtures/ProjectPartials/</div>',
            $content,
        );
    }

    /**
     * The other shape of a site package page object. PAGEVIEW reads no `partialRootPaths`
     * at all - it derives them from `paths` by appending `Partials/` - so the registration
     * that serves a FLUIDTEMPLATE integration is invisible to it, and the page template
     * would die on the `Academic/Image` it renders. This is why the extension lists the
     * academic_base path under `paths` as well.
     */
    #[Test]
    public function programPageShowsItsMediaOnAPageViewPageObject(): void
    {
        $this->setUpPageViewTestCase();

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/applied-physics'));
        $detail = $this->elementMatching($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-programs-detail ')]");
        $this->assertRendersResponsivePicture(
            $xpath,
            $detail,
            3,
            800,
            'img-fluid',
            'The laboratory of the Applied Physics programme',
        );
    }
}
