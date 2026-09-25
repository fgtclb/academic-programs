<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Facts;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The facts of a program in the three places that show them: the program page, the program
 * details content element and the program card of the list.
 *
 * The program "Applied Physics" carries a degree, a standard period, a location and a
 * topic whose title carries an ampersand, 180 credit points and the three text fields.
 * The ampersand shows that a category title is escaped once, the paragraphs of the text
 * fields that their rich text is rendered raw. It exists twice: page 10 is rendered as a
 * program page, page 12 carries the details content element and is rendered by a page
 * object that shows its content only, so each place is asserted on its own output. Page 11
 * is a program with a degree and nothing else.
 *
 * The order of the facts is asserted by their labels and values in the rendered text,
 * not by their markup, so the cases without configuration hold before and after the facts
 * partials replaced "Program/Categories" and the fixed list of the program page. The
 * markup the Breaking changelog publishes is asserted on its own.
 *
 * The order of the category types is the order of the category type registry, which is the
 * order of "Configuration/CategoryTypes.yaml": degree, standard period, location, topic.
 */
final class ProgramFactsTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const PROGRAM_PAGE = 'https://www.acme.com/applied-physics';
    private const PROGRAM_PAGE_WITHOUT_CREDIT_POINTS = 'https://www.acme.com/chemistry';
    private const DETAILS_PAGE = 'https://www.acme.com/applied-physics-details';
    private const LIST_PAGE = 'https://www.acme.com/programs';

    private const PAGES_FIXTURES = 'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/';
    private const CATEGORIES_OVERRIDE = 'EXT:academic_programs/Tests/Functional/Facts/Fixtures/TypoScript/Setup/CategoriesOverride.typoscript';
    private const PLUGIN_RENDERING = 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript';

    private const CREDIT_POINTS_ICON = 'data-identifier="tx-academicprograms-info-credit-points"';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/programFacts.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * A site configured through a TypoScript record: the site package, the extension, then
     * the integrator's setup and constants.
     *
     * @param list<string> $integratorSetup
     * @param array<string, string> $constants Constant paths below "plugin.tx_academicprograms.".
     */
    private function setUpSite(?string $sitePackage, array $integratorSetup = [], array $constants = []): void
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
                    ...($sitePackage !== null ? [$sitePackage] : []),
                    'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                    ...$integratorSetup,
                ],
            ],
        );
        if ($constants !== []) {
            $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
            $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => 1])->fetchAssociative();
            $this->assertIsArray($template);
            $lines = [];
            foreach ($constants as $path => $value) {
                $lines[] = 'plugin.tx_academicprograms.' . $path . ' = ' . $value;
            }
            $connection->update(
                'sys_template',
                ['constants' => $template['constants'] . "\n" . implode("\n", $lines)],
                ['uid' => $template['uid']],
            );
        }
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * A site whose page object renders the content of the page and nothing else, set up
     * after the extension as the plugin tests do, so the program page of the details
     * element renders no facts of its own.
     *
     * @param list<string> $integratorSetup
     * @param array<string, string> $constants
     */
    private function setUpPluginSite(array $integratorSetup = [], array $constants = []): void
    {
        $this->setUpSite(null, [self::PLUGIN_RENDERING, ...$integratorSetup], $constants);
    }

    /**
     * A site configured through the aggregate site set and site settings. The page object
     * comes from a "sys_template" record, which is included after the sets.
     *
     * @param array<string, string> $settings
     */
    private function setUpSiteSetSite(string $pageObject, array $settings): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => '@import \'' . $pageObject . '\'',
            ],
        );
        $this->writeSiteConfiguration(
            // The site identifier is part of several caches the test instance keeps for
            // the whole class, so differently configured sites need different ones.
            identifier: 'acme-' . substr(md5($pageObject . json_encode($settings, JSON_THROW_ON_ERROR)), 0, 10),
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', 'fgtclb/academic-programs'],
                    'settings' => $settings,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function programPageObjectDataProvider(): \Generator
    {
        yield 'FLUIDTEMPLATE' => [self::PAGES_FIXTURES . 'SitePackage.typoscript'];
        yield 'PAGEVIEW' => [self::PAGES_FIXTURES . 'SitePackagePageView.typoscript'];
    }

    #[Test]
    #[DataProvider('programPageObjectDataProvider')]
    public function programPageWithoutConfigurationShowsEveryCategoryTypeThenTheProgramFields(string $sitePackage): void
    {
        $this->setUpSite($sitePackage);

        $content = $this->renderFrontendPage(self::PROGRAM_PAGE);

        $this->assertRenderedInOrder(
            $content,
            'Degree',
            'Bachelor of Science',
            'Standard period',
            '6 semesters',
            'Location',
            'Campus A',
            'Topic',
            'Optics &amp; Photonics',
            'Credit points',
            '180',
            'Job profile',
            'Research and development in industry.',
            'Performance scope',
            'Six semesters of lectures and laboratory work.',
            'Prerequisites',
            'General qualification for university entrance.',
        );
    }

    /**
     * The markup published by the Breaking changelog: one list, an item class with the
     * identifier as modifier, category titles escaped once, rich text raw.
     */
    #[Test]
    #[DataProvider('programPageObjectDataProvider')]
    public function programPageRendersTheFactsMarkup(string $sitePackage): void
    {
        $this->setUpSite($sitePackage);

        $content = $this->renderFrontendPage(self::PROGRAM_PAGE);

        $this->assertSame(1, substr_count($content, '<ul class="academic-programs-facts">'));
        $this->assertStringContainsString('<li class="academic-programs-facts__item academic-programs-facts__item--degree">', $content);
        $this->assertStringContainsString('<li class="academic-programs-facts__item academic-programs-facts__item--creditPoints">', $content);
        $this->assertStringContainsString('Optics &amp; Photonics', $content);
        $this->assertStringNotContainsString('&amp;amp;', $content);
        $this->assertStringContainsString('<p>Research and development in industry.</p>', $content);
    }

    #[Test]
    #[DataProvider('programPageObjectDataProvider')]
    public function programPageShowsTheConfiguredFactsInTheirOrder(string $sitePackage): void
    {
        $this->setUpSite($sitePackage, constants: ['facts.fields' => 'creditPoints,degree']);

        $content = $this->renderFrontendPage(self::PROGRAM_PAGE);

        $this->assertRenderedInOrder($content, 'Credit points', '180', 'Degree', 'Bachelor of Science');
        $this->assertStringNotContainsString('Campus A', $content);
        $this->assertStringNotContainsString('6 semesters', $content);
        $this->assertStringNotContainsString('Research and development in industry.', $content);
    }

    #[Test]
    public function configuredOrderHoldsAgainstTheCategoryTypeOrder(): void
    {
        $this->setUpSite(self::PAGES_FIXTURES . 'SitePackage.typoscript', constants: ['facts.fields' => 'location,degree']);

        $content = $this->renderFrontendPage(self::PROGRAM_PAGE);

        $this->assertRenderedInOrder($content, 'Campus A', 'Bachelor of Science');
        $this->assertStringNotContainsString('Credit points', $content);
    }

    #[Test]
    public function unknownAndRepeatedIdentifiersAreSkipped(): void
    {
        $this->setUpSite(self::PAGES_FIXTURES . 'SitePackage.typoscript', constants: ['facts.fields' => 'nonsense, location ,creditPoints,location,title']);

        $content = $this->renderFrontendPage(self::PROGRAM_PAGE);

        $this->assertRenderedInOrder($content, 'Campus A', 'Credit points');
        $this->assertSame(1, substr_count($content, 'Campus A'));
        $this->assertStringNotContainsString('nonsense', $content);
        $this->assertStringNotContainsString('Bachelor of Science', $content);
    }

    #[Test]
    public function creditPointsFactCarriesItsIcon(): void
    {
        $this->setUpSite(self::PAGES_FIXTURES . 'SitePackage.typoscript', constants: ['facts.fields' => 'creditPoints']);

        $content = $this->renderFrontendPage(self::PROGRAM_PAGE);

        $this->assertStringContainsString(self::CREDIT_POINTS_ICON, $content);
        $this->assertRenderedInOrder($content, self::CREDIT_POINTS_ICON, 'Credit points', '180');
    }

    #[Test]
    public function programWithoutCreditPointsShowsNoCreditPointsFact(): void
    {
        $this->setUpSite(self::PAGES_FIXTURES . 'SitePackage.typoscript', constants: ['facts.fields' => 'creditPoints,degree']);

        $content = $this->renderFrontendPage(self::PROGRAM_PAGE_WITHOUT_CREDIT_POINTS);

        $this->assertStringContainsString('Bachelor of Science', $content);
        $this->assertStringNotContainsString('Credit points', $content);
        $this->assertStringNotContainsString(self::CREDIT_POINTS_ICON, $content);
    }

    /**
     * The processor has a constructor argument, and a class name is resolved through
     * `GeneralUtility::makeInstance()`, which only returns a public service.
     */
    #[Test]
    public function programPageNamingTheProcessorByItsClassNameShowsTheFacts(): void
    {
        $this->setUpSite(
            self::PAGES_FIXTURES . 'SitePackage.typoscript',
            ['EXT:academic_programs/Tests/Functional/Facts/Fixtures/TypoScript/Setup/ProcessorByClassName.typoscript'],
            ['facts.fields' => 'creditPoints'],
        );

        $content = $this->renderFrontendPage(self::PROGRAM_PAGE);

        $this->assertRenderedInOrder($content, 'Credit points', '180');
        $this->assertStringNotContainsString('Bachelor of Science', $content);
    }

    #[Test]
    public function programPageOnASiteSetSiteReadsTheSiteSetting(): void
    {
        $this->setUpSiteSetSite(self::PAGES_FIXTURES . 'SitePackageAfterSets.typoscript', ['plugin.tx_academicprograms.facts.fields' => 'degree']);

        $content = $this->renderFrontendPage(self::PROGRAM_PAGE);

        $this->assertStringContainsString('Bachelor of Science', $content);
        $this->assertStringNotContainsString('Campus A', $content);
        $this->assertStringNotContainsString('Credit points', $content);
    }

    #[Test]
    public function detailsElementWithoutConfigurationShowsEveryCategoryTypeOnly(): void
    {
        $this->setUpPluginSite();

        $content = $this->renderFrontendPage(self::DETAILS_PAGE);

        $this->assertRenderedInOrder($content, 'Degree', 'Bachelor of Science', 'Standard period', '6 semesters', 'Location', 'Campus A', 'Topic', 'Optics &amp; Photonics');
        $this->assertStringNotContainsString('Credit points', $content);
        $this->assertStringNotContainsString('Research and development in industry.', $content);
    }

    #[Test]
    public function detailsElementShowsTheConfiguredFactsInTheirOrder(): void
    {
        $this->setUpPluginSite(constants: ['facts.fields' => 'creditPoints,degree']);

        $content = $this->renderFrontendPage(self::DETAILS_PAGE);

        $this->assertRenderedInOrder($content, self::CREDIT_POINTS_ICON, 'Credit points', '180', 'Degree', 'Bachelor of Science');
        $this->assertStringNotContainsString('Campus A', $content);
    }

    #[Test]
    public function programCardShowsTheDegreeWithoutConfiguration(): void
    {
        $this->setUpPluginSite();

        $content = $this->renderFrontendPage(self::LIST_PAGE);

        foreach (['Applied Physics', 'Chemistry'] as $title) {
            $card = $this->programCard($content, $title);
            $this->assertStringContainsString('<ul class="academic-programs-facts list-group list-group-flush">', $card);
            $this->assertStringContainsString('<li class="academic-programs-facts__item academic-programs-facts__item--degree list-group-item">', $card);
            $this->assertRenderedInOrder($card, 'Degree', 'Bachelor of Science');
            $this->assertStringNotContainsString('6 semesters', $card);
            $this->assertStringNotContainsString('Campus A', $card);
        }
    }

    #[Test]
    public function programCardShowsTheConfiguredFactsInTheirOrder(): void
    {
        $this->setUpPluginSite(constants: ['card.fields' => 'standard_period,degree']);

        $card = $this->programCard($this->renderFrontendPage(self::LIST_PAGE), 'Applied Physics');

        $this->assertRenderedInOrder($card, 'Standard period', '6 semesters', 'Degree', 'Bachelor of Science');
        $this->assertStringNotContainsString('Campus A', $card);
    }

    #[Test]
    public function detailsElementAndCardOnASiteSetSiteReadTheSiteSettings(): void
    {
        $this->setUpSiteSetSite(self::PLUGIN_RENDERING, [
            'plugin.tx_academicprograms.facts.fields' => 'creditPoints',
            'plugin.tx_academicprograms.card.fields' => 'degree,standard_period',
        ]);

        $details = $this->renderFrontendPage(self::DETAILS_PAGE);
        $this->assertStringContainsString('Credit points', $details);
        $this->assertStringNotContainsString('Bachelor of Science', $details);

        $card = $this->programCard($this->renderFrontendPage(self::LIST_PAGE), 'Applied Physics');
        $this->assertRenderedInOrder($card, 'Bachelor of Science', '6 semesters');
    }

    /**
     * A project override of the removed partial "Program/Categories" is rendered by
     * neither place any more.
     */
    #[Test]
    #[DataProvider('programPageObjectDataProvider')]
    public function programPageDoesNotRenderAnOverrideOfTheFormerCategoriesPartial(string $sitePackage): void
    {
        $this->setUpSite($sitePackage, [self::CATEGORIES_OVERRIDE]);

        $content = $this->renderFrontendPage(self::PROGRAM_PAGE);

        $this->assertStringNotContainsString('categories-override-marker', $content);
        $this->assertStringContainsString('Bachelor of Science', $content);
    }

    #[Test]
    public function detailsElementDoesNotRenderAnOverrideOfTheFormerCategoriesPartial(): void
    {
        $this->setUpPluginSite([self::CATEGORIES_OVERRIDE]);

        $content = $this->renderFrontendPage(self::DETAILS_PAGE);

        $this->assertStringNotContainsString('categories-override-marker', $content);
        $this->assertStringContainsString('Bachelor of Science', $content);
    }

    /**
     * The card of the program with the given title, and nothing of the cards around it.
     */
    private function programCard(string $content, string $title): string
    {
        foreach (explode('academic-programs-item ', $content) as $card) {
            if (str_contains($card, '>' . $title . '</a>')) {
                return $card;
            }
        }
        $this->fail(sprintf('The list renders no card of "%s".', $title));
    }

    /**
     * Each string is found after the previous one.
     */
    private function assertRenderedInOrder(string $content, string ...$strings): void
    {
        $offset = 0;
        foreach ($strings as $string) {
            $position = strpos($content, $string, $offset);
            $this->assertIsInt(
                $position,
                sprintf('"%s" is not rendered after offset %d.', $string, $offset),
            );
            $offset = $position + strlen($string);
        }
    }
}
