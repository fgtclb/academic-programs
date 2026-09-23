<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Pages;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\ResponsiveImageAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
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
 *
 * The page renders the content of its main column through "page.10.variables.programContent",
 * which the extension defines inside the same page type condition. None of the setups
 * here defines "styles.content.getContent": the page template used to render that global
 * object, which only an opt-in set defined, and every program page of a site without it
 * died with an exception of "f:cObject".
 */
final class AcademicProgramPageTemplateTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use ResponsiveImageAssertionTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    /**
     * The two main column elements of the program page, in their manual order - which is
     * the reverse of their uid order.
     */
    private const FIRST_ELEMENT = 'The programme covers optics and photonics.';
    private const SECOND_ELEMENT = 'Admission requires a bachelor degree.';

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

    /**
     * @param list<string> $additionalSetup TypoScript files included after the extension.
     */
    private function setUpTestCase(array $additionalSetup = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramPageTemplateTest/page.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramPageTemplateTest/content.csv');
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
                    ...$additionalSetup,
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
            ),
        ]);
    }

    /**
     * A site configured through site sets alone, the way a v13 or v14 site is set up.
     *
     * The site package comes from a "sys_template" record, because the sets of a site are
     * included before its records: the extension refines "page.10" first, and the page
     * object of the site package keeps what the extension assigned. "clear" stays "0" -
     * the flag "setUpFrontendRootPage()" writes discards everything the sets contributed.
     *
     * @param list<string> $sets
     */
    private function setUpSiteSetTestCase(array $sets): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramPageTemplateTest/page.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramPageTemplateTest/content.csv');
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => '@import \'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackageAfterSets.typoscript\'',
            ],
        );
        $this->writeSiteConfiguration(
            // The page TSconfig of a site is cached under its identifier for the whole class.
            identifier: 'acme-' . substr(md5(implode(',', $sets)), 0, 10),
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: ['dependencies' => ['typo3/fluid-styled-content', ...$sets]],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }

    /**
     * The same site, rendered by a PAGEVIEW page object instead of a FLUIDTEMPLATE one.
     */
    private function setUpPageViewTestCase(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramPageTemplateTest/page.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramPageTemplateTest/content.csv');
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

    #[Test]
    public function programPageRendersTheContentOfItsMainColumnInTheManualOrder(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/applied-physics');

        $this->assertMainColumnInManualOrder($content);
    }

    /**
     * The last four elements of the main column are two pairs, each sharing a `sorting`
     * value: the first pair is written in ascending uid order, the second in descending.
     *
     * One pair alone is a guard on some database versions, because PostgreSQL returns the
     * tied rows of this query in an order that depends on its version. Measured without the
     * `uid` tiebreaker, on v13 and v14 alike: PostgreSQL 10, which CI runs, returns them in
     * the reverse of their write order, so the ascending pair fails; PostgreSQL 16 returns
     * them in write order, so the descending pair fails; SQLite passes both - uid is its
     * rowid, it cannot fail. A green default run is no evidence for this test,
     * `-d postgres` is.
     */
    #[Test]
    public function mainColumnElementsSharingASortingValueFollowUidOrder(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/applied-physics');

        $positions = [];
        foreach ([
            self::SECOND_ELEMENT,
            'A tie, the lower uid.',
            'A tie, the higher uid.',
            'A second tie, the lower uid.',
            'A second tie, the higher uid.',
        ] as $text) {
            $position = strpos($content, $text);
            $this->assertIsInt($position, sprintf('"%s" is missing.', $text));
            $positions[$text] = $position;
        }
        $sorted = $positions;
        asort($sorted);
        $this->assertSame(
            array_keys($positions),
            array_keys($sorted),
            'The last elements of the main column are not in sorting order with uid order for ties.',
        );
    }

    /**
     * The content element in another column, and the hidden one, stay out of the page.
     */
    #[Test]
    public function programPageRendersNoContentOfOtherColumnsAndNoHiddenContent(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/applied-physics');

        $this->assertStringContainsString(self::FIRST_ELEMENT, $content);
        $this->assertStringNotContainsString('A note in the side column.', $content);
        $this->assertStringNotContainsString('A hidden draft.', $content);
    }

    #[Test]
    public function programPageRendersTheContentOfItsMainColumnOnAPageViewPageObject(): void
    {
        $this->setUpPageViewTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/applied-physics');

        $this->assertMainColumnInManualOrder($content);
        $this->assertStringNotContainsString('A note in the side column.', $content);
    }

    #[Test]
    public function translatedProgramPageRendersTheTranslatedContent(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/de/angewandte-physik');

        $first = strpos($content, 'Das Studium umfasst Optik und Photonik.');
        $second = strpos($content, 'Die Zulassung setzt einen Bachelor voraus.');
        $this->assertIsInt($first, 'The first translated content element is missing.');
        $this->assertIsInt($second, 'The second translated content element is missing.');
        $this->assertLessThan($second, $first, 'The translated content elements are not in their manual order.');
        $this->assertStringNotContainsString(self::FIRST_ELEMENT, $content);
        $this->assertStringNotContainsString(self::SECOND_ELEMENT, $content);
    }

    /**
     * The content is a variable of the program page object, so an integrator adjusts it
     * for program pages alone - here to render the side column instead. The root page has
     * a side column element too, and it stays out of the root page; that half is a guard,
     * the adjustment sits inside the page type condition as the documentation shows it.
     */
    #[Test]
    public function integratorAdjustsTheProgramPageContent(): void
    {
        $this->setUpTestCase([
            'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/ProgramContentFromSideColumn.typoscript',
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/applied-physics');

        $this->assertStringContainsString('A note in the side column.', $content);
        $this->assertStringNotContainsString(self::FIRST_ELEMENT, $content);

        $rootPage = $this->renderFrontendPage('https://www.acme.com/');
        $this->assertStringContainsString('site-package-default-template', $rootPage);
        $this->assertStringNotContainsString('A note in the side column of the root page.', $rootPage);
    }

    /**
     * The fallback template of the fixture site package renders the variable as well, so
     * a variable defined for every page would put the main column of the root page here.
     */
    #[Test]
    public function programPageContentIsNotDefinedForOtherPageTypes(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/');

        $this->assertStringContainsString('site-package-default-template', $content);
        $this->assertStringNotContainsString('Welcome to the root page.', $content);
    }

    /**
     * @return \Generator<string, array{0: list<string>}>
     */
    public static function siteSetDataProvider(): \Generator
    {
        yield 'program details set alone' => [['fgtclb/academic-programs-program-details']];
        yield 'program list set alone' => [['fgtclb/academic-programs-program-list']];
        yield 'aggregate set' => [['fgtclb/academic-programs']];
    }

    /**
     * @param list<string> $sets
     */
    #[Test]
    #[DataProvider('siteSetDataProvider')]
    public function programPageRendersTheContentOfItsMainColumnOnASiteSetSite(array $sets): void
    {
        $this->setUpSiteSetTestCase($sets);

        $content = $this->renderFrontendPage('https://www.acme.com/applied-physics');

        $this->assertStringContainsString('<h1>Applied Physics</h1>', $content);
        $this->assertMainColumnInManualOrder($content);
        $this->assertStringNotContainsString('A note in the side column.', $content);
    }

    private function assertMainColumnInManualOrder(string $content): void
    {
        $first = strpos($content, self::FIRST_ELEMENT);
        $second = strpos($content, self::SECOND_ELEMENT);
        $this->assertIsInt($first, 'The first content element of the main column is missing.');
        $this->assertIsInt($second, 'The second content element of the main column is missing.');
        $this->assertLessThan($second, $first, 'The content elements of the main column are not in their manual order.');
    }
}
