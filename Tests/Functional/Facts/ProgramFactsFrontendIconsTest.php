<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Facts;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * The icons of the facts come from the frontend icon registry, in the three places that
 * show the facts: the program page, the program details content element and the card.
 *
 * The fixture `tests/programs-frontend-icons` is a site package. In its
 * `Configuration/FrontendIcons.php` it replaces the credit points icon and the icon of
 * the shipped degree, in its `Configuration/Icons.php` it replaces the credit points
 * icon once more with another drawing, which the frontend must not show. It adds the
 * type `accreditation` with one drawing for the backend (`icon`) and another one for
 * the frontend (`frontendIcon`). Each drawing is a rectangle of its own. The standard
 * period is a shipped type nobody replaces.
 *
 * "Applied Physics" carries all four facts. Page 10 is rendered as a program page, page
 * 12 carries the details content element, and "/programs" lists both with their cards.
 * A TYPO3 v14 test instance orders the packages by their keys, so the first test asserts
 * that the fixture loads after academic_programs.
 */
final class ProgramFactsFrontendIconsTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const PROGRAM_PAGE = 'https://www.acme.com/applied-physics';
    private const DETAILS_PAGE = 'https://www.acme.com/applied-physics-details';
    private const LIST_PAGE = 'https://www.acme.com/programs';

    private const SITE_PACKAGE = 'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackage.typoscript';
    private const PLUGIN_RENDERING = 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript';

    private const FACTS = 'creditPoints,degree,standard_period,accreditation';

    /**
     * The rectangles of the fixture, as the serialisers of both core versions write them.
     */
    private const SITE_CREDIT_POINTS = 'x="1" y="1" width="14" height="2"';
    private const SITE_BACKEND = 'x="1" y="13" width="14" height="2"';
    private const SITE_DEGREE = 'x="1" y="1" width="2" height="14"';
    private const TYPE_BACKEND = 'x="13" y="1" width="2" height="14"';
    private const TYPE_FRONTEND = 'x="4" y="4" width="8" height="8"';

    /**
     * Parts of the shipped `Degree.svg` and `StandardPeriod.svg`.
     */
    private const SHIPPED_DEGREE = 'x1="22.29" y1="28.02"';
    private const SHIPPED_STANDARD_PERIOD = 'd="M58.05,23.65c10,10-2,18-2,18h-43';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/programs-frontend-icons');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/programFactIcons.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The program page is rendered by the site package, the details element and the list
     * by the page object of the plugin tests, which renders the content of the page and
     * nothing else. Every place lists the four facts.
     */
    private function setUpSite(bool $programPage): void
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
                    ...($programPage ? [self::SITE_PACKAGE] : []),
                    'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                    ...($programPage ? [] : [self::PLUGIN_RENDERING]),
                ],
            ],
        );
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update(
            'sys_template',
            ['constants' => $template['constants'] . LF . 'plugin.tx_academicprograms.facts.fields = ' . self::FACTS
                . LF . 'plugin.tx_academicprograms.card.fields = ' . self::FACTS],
            ['uid' => $template['uid']],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    #[Test]
    public function theSitePackageLoadsAfterTheExtension(): void
    {
        $packageKeys = array_keys($this->get(PackageManager::class)->getActivePackages());

        $this->assertGreaterThan(
            array_search('academic_programs', $packageKeys, true),
            array_search('test_programs_frontend_icons', $packageKeys, true),
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: bool}>
     */
    public static function placeDataProvider(): \Generator
    {
        yield 'program page' => [self::PROGRAM_PAGE, true];
        yield 'details content element' => [self::DETAILS_PAGE, false];
        yield 'program card' => [self::LIST_PAGE, false];
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function theCreditPointsFactShowsTheFrontendReplacementOfTheSitePackage(string $url, bool $programPage): void
    {
        $this->setUpSite($programPage);

        $icons = $this->renderedIconMarkups($this->renderFrontendPage($url), 'tx-academicprograms-info-credit-points');

        $this->assertNotSame([], $icons);
        foreach ($icons as $markup) {
            $this->assertStringContainsString(self::SITE_CREDIT_POINTS, $markup);
            $this->assertStringNotContainsString(self::SITE_BACKEND, $markup);
        }
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function theDegreeFactShowsTheFrontendReplacementOfTheSitePackage(string $url, bool $programPage): void
    {
        $this->setUpSite($programPage);

        $icons = $this->renderedIconMarkups($this->renderFrontendPage($url), 'category_types.programs.degree');

        $this->assertNotSame([], $icons);
        foreach ($icons as $markup) {
            $this->assertStringContainsString(self::SITE_DEGREE, $markup);
            $this->assertStringNotContainsString(self::SHIPPED_DEGREE, $markup);
        }
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function aTypeWithAFrontendIconShowsItInTheFacts(string $url, bool $programPage): void
    {
        $this->setUpSite($programPage);

        $icons = $this->renderedIconMarkups($this->renderFrontendPage($url), 'category_types.programs.accreditation');

        $this->assertNotSame([], $icons);
        foreach ($icons as $markup) {
            $this->assertStringContainsString(self::TYPE_FRONTEND, $markup);
            $this->assertStringNotContainsString(self::TYPE_BACKEND, $markup);
        }
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function aShippedTypeNobodyReplacesShowsTheShippedIcon(string $url, bool $programPage): void
    {
        $this->setUpSite($programPage);

        $icons = $this->renderedIconMarkups($this->renderFrontendPage($url), 'category_types.programs.standard_period');

        $this->assertNotSame([], $icons);
        foreach ($icons as $markup) {
            $this->assertStringContainsString(self::SHIPPED_STANDARD_PERIOD, $markup);
        }
    }

    /**
     * The backend keeps the drawings the frontend does not show: the declared `icon` of
     * the new type, the shipped degree, and the credit points icon of the backend file of
     * the site package. The extension no longer registers that identifier there, so this
     * is the one of the fixture.
     */
    #[Test]
    public function theBackendRegistryKeepsTheDeclaredIcons(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);

        $this->assertSame(
            'EXT:test_programs_frontend_icons/Resources/Public/Icons/TypeBackend.svg',
            $iconRegistry->getIconConfigurationByIdentifier('category_types.programs.accreditation')['options']['source'] ?? null,
        );
        $this->assertSame(
            'EXT:academic_programs/Resources/Public/Icons/CategoryTypes/Degree.svg',
            $iconRegistry->getIconConfigurationByIdentifier('category_types.programs.degree')['options']['source'] ?? null,
        );
        $this->assertSame(
            'EXT:test_programs_frontend_icons/Resources/Public/Icons/SiteBackend.svg',
            $iconRegistry->getIconConfigurationByIdentifier('tx-academicprograms-info-credit-points')['options']['source'] ?? null,
        );
    }

    /**
     * The inner markup of every rendered icon with the identifier, in page order.
     *
     * @return list<string>
     */
    private function renderedIconMarkups(string $content, string $identifier): array
    {
        preg_match_all(
            '@data-identifier="' . preg_quote($identifier, '@') . '" aria-hidden="true">\s*<span class="icon-markup">(.*?)</span>@s',
            $content,
            $matches,
        );

        return $matches[1];
    }
}
