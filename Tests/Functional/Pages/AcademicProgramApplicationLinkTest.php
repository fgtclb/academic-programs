<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Pages;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\ResponsiveImageAssertionTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The application link of a program page: the fields "application_link" and
 * "application_link_label", rendered by the partial "Program/Page/CallToAction".
 *
 * The partial resolves the link before it renders anything, so a target that cannot be
 * linked leaves no bare label behind - the same rule the header applies to the link back
 * to the list. The fixture has one program page per case: a label, no label, a label
 * without a link, a hidden target page and an external URL, plus a German translation of
 * the program page without a label, and a link carrying a target and a title from the
 * link browser with a label that holds markup.
 */
final class AcademicProgramApplicationLinkTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use ResponsiveImageAssertionTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const APPLICATION_LINK = "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-programs-application ')]";

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramApplicationLinkTest/pages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalSetup TypoScript files included after the extension.
     */
    private function setUpSite(array $additionalSetup = []): void
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
                    'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackage.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                    ...$additionalSetup,
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
        ]);
    }

    private function applicationLinkOf(string $url): \DOMElement
    {
        $xpath = $this->parseRenderedPage($this->renderFrontendPage($url));
        $container = $this->elementMatching($xpath, self::APPLICATION_LINK);

        return $this->elementMatching($xpath, './/a', $container);
    }

    #[Test]
    public function programPageLinksTheApplicationWithTheLabelOfTheEditor(): void
    {
        $this->setUpSite();

        $link = $this->applicationLinkOf('https://www.acme.com/applied-physics');

        $this->assertSame('/apply', $link->getAttribute('href'));
        $this->assertSame('Apply online', trim($link->textContent));
    }

    #[Test]
    public function programPageWithoutALabelLinksTheApplicationWithTheDefaultLabel(): void
    {
        $this->setUpSite();

        $link = $this->applicationLinkOf('https://www.acme.com/chemistry');

        $this->assertSame('/apply', $link->getAttribute('href'));
        $this->assertSame('Apply now', trim($link->textContent));
    }

    #[Test]
    public function translatedProgramPageWithoutALabelUsesTheDefaultLabelOfItsLanguage(): void
    {
        $this->setUpSite();

        $link = $this->applicationLinkOf('https://www.acme.com/de/chemie');

        $this->assertSame('/de/bewerbung', $link->getAttribute('href'));
        $this->assertSame('Jetzt bewerben', trim($link->textContent));
    }

    #[Test]
    public function programPageLinksAnExternalApplicationPortal(): void
    {
        $this->setUpSite();

        $link = $this->applicationLinkOf('https://www.acme.com/biology');

        $this->assertSame('https://apply.example.com/biology', $link->getAttribute('href'));
        $this->assertSame('Apply online', trim($link->textContent));
    }

    /**
     * The target and the title an editor picks in the link browser are part of the stored
     * link, which is why the partial renders it through "f:link.typolink" rather than an
     * anchor around the resolved URL. The label is text: markup in it is escaped.
     */
    #[Test]
    public function programPageKeepsTheLinkBrowserOptionsAndEscapesTheLabel(): void
    {
        $this->setUpSite();

        $link = $this->applicationLinkOf('https://www.acme.com/geology');

        $this->assertSame('/apply', $link->getAttribute('href'));
        $this->assertSame('_blank', $link->getAttribute('target'));
        $this->assertSame('Apply here', $link->getAttribute('title'));
        $this->assertStringContainsString('btn btn-primary', $link->getAttribute('class'));
        $this->assertSame('<b>Apply</b> & enrol', trim($link->textContent));
        $this->assertSame(0, $link->getElementsByTagName('b')->length);
    }

    #[Test]
    public function programPageWithoutALinkRendersNoApplicationLinkWhateverTheLabel(): void
    {
        $this->setUpSite();

        $content = $this->renderFrontendPage('https://www.acme.com/mathematics');

        $this->assertStringContainsString('<h1>Mathematics</h1>', $content);
        $this->assertStringNotContainsString('academic-programs-application', $content);
        $this->assertStringNotContainsString('Apply online', $content);
    }

    /**
     * A hidden target page resolves to no URL. Rendered straight through "f:link.typolink",
     * the label would stay on the page as plain text.
     */
    #[Test]
    public function programPageLinkingAHiddenPageRendersNoApplicationLink(): void
    {
        $this->setUpSite();

        $content = $this->renderFrontendPage('https://www.acme.com/history');

        $this->assertStringContainsString('<h1>History</h1>', $content);
        $this->assertStringNotContainsString('academic-programs-application', $content);
        $this->assertStringNotContainsString('Apply online', $content);
    }

    /**
     * The link is a partial of its own next to the header, so a project that replaces the
     * header keeps it.
     */
    #[Test]
    public function projectOverridingTheHeaderKeepsTheApplicationLink(): void
    {
        $this->setUpSite([
            'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/HeaderOverrideAt75.typoscript',
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/applied-physics');

        $this->assertStringContainsString('<div class="project-program-header">Applied Physics</div>', $content);
        $xpath = $this->parseRenderedPage($content);
        $link = $this->elementMatching($xpath, './/a', $this->elementMatching($xpath, self::APPLICATION_LINK));
        $this->assertSame('/apply', $link->getAttribute('href'));
    }
}
