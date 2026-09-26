<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ContentElementHeaderAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders both plugins of this extension in the frontend: `academicprograms_programlist`
 * and `academicprograms_programdetails`. They share one page tree, one site and one
 * TypoScript setup, which is why they share one test class.
 *
 * Programs are pages of doktype 20 mapped onto the `pages` table, so the fixtures are
 * page records carrying the program columns. The list plugin reads its configuration from
 * the FlexForm of the content element plus its `pages`/`recursive` fields, while the
 * details plugin takes no configuration at all and resolves its program from the page the
 * content element sits on (`DetailsController::showAction()`).
 *
 * The header of a content element renders once: by default the content element layout
 * renders it and the plugins do not. A site whose layout renders no header switches
 * `renderContentElementHeader` on, and the templates then render the
 * `EXT:fluid_styled_content` `Header/All` partial themselves. The switched on cases guard
 * the `record` view variable as well: on TYPO3 v14 the partial renders the header through
 * it, and fails without it.
 */
final class AcademicProgramsPluginTest extends AbstractAcademicProgramsTestCase
{
    use ContentElementHeaderAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const HEADER = 'Our study programs';
    private const SUBHEADER = 'Bachelor and master';
    private const RENDER_HEADER_CONSTANTS = 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/RenderContentElementHeader.typoscript';
    private const HEADER_PARTIAL_OVERRIDE_SETUP = 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/HeaderPartialOverride.typoscript';
    private const LAYOUT_WITHOUT_HEADER_SETUP = 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/LayoutWithoutHeader.typoscript';

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

    /**
     * @param list<string> $additionalConstantFiles
     * @param list<string> $additionalSetupFiles
     */
    private function setUpTestCase(string $dataSet, array $additionalConstantFiles = [], array $additionalSetupFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsPlugin/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/constants.typoscript',
                    ...$additionalConstantFiles,
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ...$additionalSetupFiles,
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

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    private function assertRenderedInOrder(string $content, string $first, string $second): void
    {
        $positionOfFirst = strpos($content, $first);
        $positionOfSecond = strpos($content, $second);
        $this->assertIsInt($positionOfFirst, sprintf('"%s" is not rendered at all.', $first));
        $this->assertIsInt($positionOfSecond, sprintf('"%s" is not rendered at all.', $second));
        $this->assertLessThan(
            $positionOfSecond,
            $positionOfFirst,
            sprintf('"%s" is expected to be rendered before "%s".', $first, $second),
        );
    }

    #[Test]
    public function programListPluginRendersAllVisiblePrograms(): void
    {
        $this->setUpTestCase('programListPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-programs-list', $content);
        $this->assertStringContainsString('academic-programs-itemlist', $content);
        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringContainsString('Molecular Chemistry', $content);
        // Programs are collected across the whole site, not only below the current page.
        $this->assertStringContainsString('Regional Teaching', $content);
        $this->assertStringContainsString('Quantum Optics', $content);
        $this->assertStringNotContainsString('Archived Studies', $content);
    }

    #[Test]
    public function programListPluginLinksEachProgramToItsPage(): void
    {
        $this->setUpTestCase('programListPage');

        $this->assertMatchesRegularExpression(
            '#<h2 class="card-title">\s*<a href="/applied-physics">Applied Physics</a>\s*</h2>#',
            $this->renderHomePage(),
        );
    }

    #[Test]
    public function programListPluginRendersTheDegreeOfEachProgram(): void
    {
        $this->setUpTestCase('programListPage');

        $content = $this->renderHomePage();
        // The item partial renders the `degree` category type only, which is what makes
        // the assignment of a single type observable in the list.
        $this->assertStringContainsString('Bachelor of Science', $content);
        $this->assertStringContainsString('Master of Science', $content);
        // Assigned to a program, but of a type the item partial does not render.
        $this->assertStringNotContainsString('<b>Type of program:</b>', $content);
    }

    #[Test]
    public function programListPluginRendersTheFilterAndSortingForm(): void
    {
        $this->setUpTestCase('programListPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-programs-filtersorting', $content);
        $this->assertStringContainsString('Sorting field', $content);
        $this->assertStringContainsString('Sorting direction', $content);
        // The filter selects are built from the category types applicable to the listed
        // programs, so both types assigned in the fixture become a filter.
        $this->assertStringContainsString('id="degree"', $content);
        $this->assertStringContainsString('id="program_type"', $content);
        // Both selects are written by `CategoryTypes\ViewHelpers\Form\AbstractSelectViewHelper`,
        // which neither `ViewHelpers\Form\SortingSelectViewHelper` nor
        // `CategoryTypes\ViewHelpers\Form\FilterSelectViewHelper` overrides any more - this is
        // what covers the sorting one, the class has no test of its own.
        $this->assertStringContainsString('<option value="title" selected="selected">Title</option>', $content);
        $this->assertStringContainsString('<option value="asc" selected="selected">Ascending</option>', $content);
        $this->assertStringContainsString('<option value="1" class="level-0">Bachelor of Science</option>', $content);
    }

    #[Test]
    public function programListPluginHidesTheFilterAndSortingFormWhenConfigured(): void
    {
        $this->setUpTestCase('programListPage_hideFilterAndSorting');

        $content = $this->renderHomePage();
        $this->assertStringNotContainsString('academic-programs-filtersorting', $content);
        // The list itself is unaffected by hiding the form.
        $this->assertStringContainsString('Applied Physics', $content);
    }

    #[Test]
    public function programListPluginRendersHiddenProgramsWhenConfigured(): void
    {
        $this->setUpTestCase('programListPage_showHiddenRecords');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringContainsString('Archived Studies', $content);
    }

    #[Test]
    public function programListPluginSortsProgramsAsConfigured(): void
    {
        $this->setUpTestCase('programListPage');

        $this->assertRenderedInOrder($this->renderHomePage(), 'Applied Physics', 'Quantum Optics');
    }

    #[Test]
    public function programListPluginSortsProgramsDescendingWhenConfigured(): void
    {
        $this->setUpTestCase('programListPage_sortingTitleDescending');

        $this->assertRenderedInOrder($this->renderHomePage(), 'Quantum Optics', 'Applied Physics');
    }

    /**
     * The visitor picks the field and the direction independently, and the two selects
     * offer both directions for every field. Until ACE-625 `sorting desc` was not a known
     * option: `ProgramDemand` discarded the posted pair, kept `sorting asc`, and rendered
     * the direction select back as "ascending" - the choice vanished without a message.
     */
    #[Test]
    public function programListPluginReversesTheManualOrderWhenDemanded(): void
    {
        $this->setUpTestCase('programListPage_sortingManualOrder');

        $content = $this->renderHomePageWithDemand([
            'sortingField' => 'sorting',
            'sortingDirection' => 'desc',
        ]);

        // The fixture's `sorting` values are deliberately unrelated to uid and to title
        // order, so neither the configured default nor an accidental ordering produces
        // this list.
        $this->assertRenderedInOrder($content, 'Molecular Chemistry', 'Quantum Optics');
        $this->assertRenderedInOrder($content, 'Quantum Optics', 'Applied Physics');
        $this->assertRenderedInOrder($content, 'Applied Physics', 'Regional Teaching');
        // The select renders the demanded direction back instead of snapping to ascending.
        $this->assertStringContainsString('<option value="desc" selected="selected">Descending</option>', $content);
    }

    #[Test]
    public function programListPluginSortsProgramsByReversedManualOrderWhenConfigured(): void
    {
        $this->setUpTestCase('programListPage_sortingManualDescending');

        $content = $this->renderHomePage();

        $this->assertRenderedInOrder($content, 'Molecular Chemistry', 'Quantum Optics');
        $this->assertRenderedInOrder($content, 'Quantum Optics', 'Applied Physics');
        $this->assertRenderedInOrder($content, 'Applied Physics', 'Regional Teaching');
    }

    /**
     * The sorting and filter form submits by POST, and the plugin answers with a redirect
     * to the list carrying the demand as GET arguments. This follows it the way a browser
     * does, so the list rendered is the one a visitor ends up on.
     *
     * @param array<string, string> $demand
     */
    private function renderHomePageWithDemand(array $demand): string
    {
        $response = $this->requestFrontendPage($this->frontendPostRequest(
            'https://www.acme.com/home',
            ['tx_academicprograms_programlist' => ['demand' => $demand]],
        ));

        return $this->renderFrontendPage($this->assertSeeOtherWithCacheHash($response));
    }

    #[Test]
    public function programListPluginRestrictsProgramsToTheSelectedPages(): void
    {
        $this->setUpTestCase('programListPage_pageRestriction');

        $content = $this->renderHomePage();
        // The `pages` field of the content element restricts by storage page, so only the
        // program directly below the selected page is left.
        $this->assertStringContainsString('Regional Teaching', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        // One level deeper, and therefore out of reach without a recursive depth.
        $this->assertStringNotContainsString('Quantum Optics', $content);
    }

    #[Test]
    public function programListPluginIncludesProgramsOfSubPagesWhenRecursive(): void
    {
        $this->setUpTestCase('programListPage_pageRestrictionRecursive');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Regional Teaching', $content);
        // A recursive depth of one adds the sub pages of the selected page, which is where
        // this program is stored.
        $this->assertStringContainsString('Quantum Optics', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
    }

    #[Test]
    public function programListPluginRestrictsProgramsToTheSelectedCategories(): void
    {
        $this->setUpTestCase('programListPage_categoryRestriction');

        $content = $this->renderHomePage();
        // The category selected on the content element is resolved through the relations of
        // its `pi_flexform` field, so only the program carrying it is listed.
        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Regional Teaching', $content);
    }

    #[Test]
    public function programListPluginRendersNoProgramsFoundLabelWithoutPrograms(): void
    {
        $this->setUpTestCase('programListPage_noPrograms');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-programs-list', $content);
        $this->assertStringContainsString('No programs found.', $content);
    }

    #[Test]
    public function programDetailsPluginRendersCategoriesOfItsProgramPage(): void
    {
        $this->setUpTestCase('programDetailsPage');

        // The details plugin sits on the program page, so that page is the one to request.
        $content = $this->renderFrontendPage('https://www.acme.com/applied-physics');
        $this->assertStringContainsString('academic-programs-detail-categories', $content);
        $this->assertStringContainsString('Degree', $content);
        $this->assertStringContainsString('Bachelor of Science', $content);
        $this->assertStringContainsString('Type of program', $content);
        $this->assertStringContainsString('Full-time', $content);
        // Assigned to another program only.
        $this->assertStringNotContainsString('Master of Science', $content);
    }

    private function setContentElementHeader(int $uid, int $headerLayout): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                ['header' => self::HEADER, 'subheader' => self::SUBHEADER, 'header_layout' => $headerLayout],
                ['uid' => $uid],
            );
    }

    /**
     * Every view with the header layouts "Default", 2 and "Hidden", and the number of times
     * the header and the subheader have to render: "Default" is the layout the header
     * partial resolves through a setting, and the one a plugin rendering it without that
     * setting leaves an empty `<header>` for. A view names the data set, the page, the
     * content element and the class of the element the template wraps its output in.
     *
     * @return \Generator<string, array{string, string, int, string, int, int}>
     */
    public static function viewsAndHeaderLayouts(): \Generator
    {
        $views = [
            'program list' => ['programListPage', 'https://www.acme.com/home', 1, 'academic-programs-list'],
            // The details element sits on the program page, so that page is the one to request.
            'program details' => ['programDetailsPage', 'https://www.acme.com/applied-physics', 1, 'academic-programs-detail-categories'],
        ];
        $headerLayouts = [
            'header layout "Default"' => [0, 1],
            'header layout 2' => [2, 1],
            'header layout "Hidden"' => [100, 0],
        ];
        foreach ($views as $view => [$dataSet, $url, $contentElement, $wrapperClass]) {
            foreach ($headerLayouts as $name => [$headerLayout, $expectedHeadings]) {
                yield $view . ', ' . $name => [$dataSet, $url, $contentElement, $wrapperClass, $headerLayout, $expectedHeadings];
            }
        }
    }

    #[Test]
    #[DataProvider('viewsAndHeaderLayouts')]
    public function aPluginLeavesTheContentElementHeaderToTheLayout(
        string $dataSet,
        string $url,
        int $contentElement,
        string $wrapperClass,
        int $headerLayout,
        int $expectedHeadings,
    ): void {
        $this->setUpTestCase($dataSet);
        $this->setContentElementHeader($contentElement, $headerLayout);

        $content = $this->renderFrontendPage($url);
        $wrapper = sprintf(
            '//*[@id = "c%d"]//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]',
            $contentElement,
            $wrapperClass,
        );
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame(0, $this->countHeadingsReading($content, self::HEADER, $wrapper));
        $this->assertSame(0, $this->countHeadingsReading($content, self::SUBHEADER, $wrapper));
    }

    #[Test]
    #[DataProvider('viewsAndHeaderLayouts')]
    public function aPluginRendersTheContentElementHeaderWhenSwitchedOn(
        string $dataSet,
        string $url,
        int $contentElement,
        string $wrapperClass,
        int $headerLayout,
        int $expectedHeadings,
    ): void {
        $this->setUpTestCase($dataSet, [self::RENDER_HEADER_CONSTANTS], [self::LAYOUT_WITHOUT_HEADER_SETUP]);
        $this->setContentElementHeader($contentElement, $headerLayout);

        $content = $this->renderFrontendPage($url);
        $frame = sprintf('//*[@id = "c%d"]', $contentElement);
        // The fixture layout renders no header, so a heading inside the frame of the element
        // comes from the template. The first two assertions prove the fixture layout and the
        // template of the view rendered.
        $this->assertSame(1, $this->countContentElementHeaderNodes($content, $frame . '[contains(concat(" ", normalize-space(@class), " "), " frame-without-header ")]'));
        $this->assertSame(1, $this->countContentElementHeaderNodes(
            $content,
            sprintf('%s//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $frame, $wrapperClass),
        ));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER, $frame));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER, $frame));
    }

    /**
     * A site package that renders the header its own way registers its own `Header/All`
     * above the paths of the extension, and that one renders instead of the partial of
     * EXT:fluid_styled_content, whose path sorts below every other one.
     */
    #[Test]
    public function aHeaderPartialOfTheSitePackageWinsOverTheShippedOne(): void
    {
        $this->setUpTestCase('programListPage', [self::RENDER_HEADER_CONSTANTS], [self::LAYOUT_WITHOUT_HEADER_SETUP, self::HEADER_PARTIAL_OVERRIDE_SETUP]);
        $this->setContentElementHeader(1, 2);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertSame(0, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame(1, $this->countContentElementHeaderNodes(
            $content,
            '//*[@id = "c1"]//p[@class = "site-package-header"][normalize-space() = "' . self::HEADER . '"]',
        ));
    }
}
