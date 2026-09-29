<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Facts;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\CategoryFilterFormAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The setting "plugin.tx_academicprograms.facts.mostSpecificOnly": a category is left out of
 * the facts of a program when the program carries a descendant of it of the same type.
 *
 * Degrees: Bachelor (1) with Bachelor of Science (2) below it, and Master (5). Locations:
 * Campus A (3) and Campus B (4), neither below the other. Applied Physics carries Bachelor,
 * Bachelor of Science and both campuses, Chemistry Bachelor alone, History Master and Campus
 * A. Page 10 renders Applied Physics as a program page, page 12 is the same program again
 * with the details content element and a page object that renders its content only, and
 * "/programs" lists all four programs with their filter.
 *
 * In German, page 20 translates Applied Physics, and the two degrees are translated with
 * the parent the core copies into a translation: the uid of the default language parent.
 */
final class ProgramFactsMostSpecificCategoryTest extends AbstractAcademicProgramsTestCase
{
    use CategoryFilterFormAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const PROGRAM_PAGE = 'https://www.acme.com/applied-physics';
    private const TRANSLATED_PROGRAM_PAGE = 'https://www.acme.com/de/angewandte-physik';
    private const DETAILS_PAGE = 'https://www.acme.com/applied-physics-details';
    private const LIST_PAGE = 'https://www.acme.com/programs';

    private const LIST_NAMESPACE = 'tx_academicprograms_programlist';
    private const LIST_FORM_CLASS = 'academic-programs-filtersorting';

    private const PAGES_FIXTURES = 'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/';
    private const PLUGIN_RENDERING = 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript';

    private const BOTH_DEGREES = 'Bachelor, Bachelor of Science';
    private const CHILD_DEGREE = 'Bachelor of Science';
    private const BOTH_LOCATIONS = 'Campus A, Campus B';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/mostSpecificCategory.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * A site configured through a TypoScript record, with the setting assigned as a constant
     * when it is on. Without a site package, the page object of the plugin tests renders the
     * content of the page and nothing else.
     */
    private function setUpSite(bool $mostSpecificOnly, ?string $sitePackage = null): void
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
                    ...($sitePackage === null ? [self::PLUGIN_RENDERING] : []),
                ],
            ],
        );
        if ($mostSpecificOnly) {
            $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
            $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => 1])->fetchAssociative();
            $this->assertIsArray($template);
            $connection->update(
                'sys_template',
                ['constants' => $template['constants'] . "\nplugin.tx_academicprograms.facts.mostSpecificOnly = 1"],
                ['uid' => $template['uid']],
            );
        }
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
        ]);
    }

    /**
     * A site configured through the aggregate site set and its settings. The page object
     * comes from a "sys_template" record, which is included after the sets.
     */
    private function setUpSiteSetSite(string $pageObject): void
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
            identifier: 'acme-' . substr(md5($pageObject), 0, 10),
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', 'fgtclb/academic-programs'],
                    'settings' => ['plugin.tx_academicprograms.facts.mostSpecificOnly' => true],
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
    public function programPageShowsEveryAssignedDegreeByDefault(string $sitePackage): void
    {
        $this->setUpSite(false, $sitePackage);

        $content = $this->renderFrontendPage(self::PROGRAM_PAGE);

        $this->assertSame(self::BOTH_DEGREES, $this->factValue($content, 'degree'));
        $this->assertSame(self::BOTH_LOCATIONS, $this->factValue($content, 'location'));
    }

    #[Test]
    #[DataProvider('programPageObjectDataProvider')]
    public function programPageShowsTheMostSpecificDegreeWithTheSettingOn(string $sitePackage): void
    {
        $this->setUpSite(true, $sitePackage);

        $content = $this->renderFrontendPage(self::PROGRAM_PAGE);

        $this->assertSame(self::CHILD_DEGREE, $this->factValue($content, 'degree'));
        // Neither campus is below the other, so both stay.
        $this->assertSame(self::BOTH_LOCATIONS, $this->factValue($content, 'location'));
    }

    /**
     * The collection holds the overlaid categories under their default language uid, and a
     * translated category names the default language parent, so the walk finds it in
     * another language as well.
     *
     * @return \Generator<string, array{0: bool, 1: string}>
     */
    public static function translatedProgramPageDataProvider(): \Generator
    {
        yield 'setting off' => [false, 'Bakkalaureus, Bakkalaureus der Naturwissenschaften'];
        yield 'setting on' => [true, 'Bakkalaureus der Naturwissenschaften'];
    }

    #[Test]
    #[DataProvider('translatedProgramPageDataProvider')]
    public function translatedProgramPageComparesTheTranslatedDegrees(bool $mostSpecificOnly, string $expected): void
    {
        $this->setUpSite($mostSpecificOnly, self::PAGES_FIXTURES . 'SitePackage.typoscript');

        $content = $this->renderFrontendPage(self::TRANSLATED_PROGRAM_PAGE);

        $this->assertSame($expected, $this->factValue($content, 'degree'));
    }

    #[Test]
    public function detailsElementShowsEveryAssignedDegreeByDefault(): void
    {
        $this->setUpSite(false);

        $content = $this->renderFrontendPage(self::DETAILS_PAGE);

        $this->assertSame(self::BOTH_DEGREES, $this->factValue($content, 'degree'));
    }

    #[Test]
    public function detailsElementShowsTheMostSpecificDegreeWithTheSettingOn(): void
    {
        $this->setUpSite(true);

        $content = $this->renderFrontendPage(self::DETAILS_PAGE);

        $this->assertSame(self::CHILD_DEGREE, $this->factValue($content, 'degree'));
        $this->assertSame(self::BOTH_LOCATIONS, $this->factValue($content, 'location'));
    }

    #[Test]
    public function programCardShowsEveryAssignedDegreeByDefault(): void
    {
        $this->setUpSite(false);

        $card = $this->programCard($this->renderFrontendPage(self::LIST_PAGE), 'Applied Physics');

        $this->assertSame(self::BOTH_DEGREES, $this->factValue($card, 'degree'));
    }

    /**
     * Chemistry carries the parent alone, which stays: there is no descendant to show
     * instead.
     */
    #[Test]
    public function programCardShowsTheMostSpecificDegreeWithTheSettingOn(): void
    {
        $this->setUpSite(true);

        $content = $this->renderFrontendPage(self::LIST_PAGE);

        $this->assertSame(self::CHILD_DEGREE, $this->factValue($this->programCard($content, 'Applied Physics'), 'degree'));
        $this->assertSame('Bachelor', $this->factValue($this->programCard($content, 'Chemistry'), 'degree'));
    }

    #[Test]
    public function programPageOnASiteSetSiteReadsTheSiteSetting(): void
    {
        $this->setUpSiteSetSite(self::PAGES_FIXTURES . 'SitePackageAfterSets.typoscript');
        $this->assertSame(self::CHILD_DEGREE, $this->factValue($this->renderFrontendPage(self::PROGRAM_PAGE), 'degree'));
    }

    #[Test]
    public function detailsElementAndCardOnASiteSetSiteReadTheSiteSetting(): void
    {
        $this->setUpSiteSetSite(self::PLUGIN_RENDERING);

        $this->assertSame(self::CHILD_DEGREE, $this->factValue($this->renderFrontendPage(self::DETAILS_PAGE), 'degree'));
        $card = $this->programCard($this->renderFrontendPage(self::LIST_PAGE), 'Applied Physics');
        $this->assertSame(self::CHILD_DEGREE, $this->factValue($card, 'degree'));
    }

    /**
     * @return \Generator<string, array{0: bool}>
     */
    public static function settingDataProvider(): \Generator
    {
        yield 'setting off' => [false];
        yield 'setting on' => [true];
    }

    /**
     * The setting changes what the facts show, not what a program carries: filtering by the
     * parent finds the programs that carry it, whether their card shows it or not, and the
     * filter offers the same degrees.
     */
    #[Test]
    #[DataProvider('settingDataProvider')]
    public function filteringByTheParentListsTheSameProgramsEitherWay(bool $mostSpecificOnly): void
    {
        $this->setUpSite($mostSpecificOnly);

        $this->assertSame(
            ['All', 'Bachelor', 'Bachelor of Science', 'Master'],
            $this->withoutAllOptionLabel($this->categoryFilterOptions($this->renderFrontendPage(self::LIST_PAGE), self::LIST_FORM_CLASS, 'degree')),
        );

        $response = $this->requestFrontendPage($this->frontendPostRequest(
            self::LIST_PAGE,
            [self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '1']]]],
        ));
        $content = $this->renderFrontendPage($this->assertSeeOtherWithCacheHash($response));

        $this->assertSame(['Applied Physics', 'Applied Physics Details', 'Chemistry'], $this->listedPrograms($content));
    }

    /**
     * The text of the fact of the given category type: its category titles, separated by a
     * comma, with the whitespace of the template collapsed.
     */
    private function factValue(string $content, string $identifier): string
    {
        $pattern = '#<li class="academic-programs-facts__item academic-programs-facts__item--' . preg_quote($identifier, '#') . '[ "].*?<span>(.*?)</span>#s';
        if (preg_match($pattern, $content, $matches) !== 1) {
            $this->fail(sprintf('No fact "%s" is rendered.', $identifier));
        }
        return trim((string)preg_replace('/\s+/', ' ', str_replace(' ,', ',', strip_tags($matches[1]))));
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
     * The titles of the listed programs, in alphabetical order.
     *
     * @return list<string>
     */
    private function listedPrograms(string $content): array
    {
        $titles = [];
        foreach (['Applied Physics', 'Applied Physics Details', 'Chemistry', 'History'] as $title) {
            if (preg_match('#>\s*' . preg_quote($title, '#') . '\s*</a>#', $content) === 1) {
                $titles[] = $title;
            }
        }
        return $titles;
    }

    /**
     * The label of the first option names the category type and is not what is asserted.
     *
     * @param list<string> $options
     * @return list<string>
     */
    private function withoutAllOptionLabel(array $options): array
    {
        $options[0] = 'All';
        return $options;
    }
}
