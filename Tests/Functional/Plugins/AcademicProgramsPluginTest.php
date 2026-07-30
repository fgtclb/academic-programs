<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
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
 */
final class AcademicProgramsPluginTest extends AbstractAcademicProgramsTestCase
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

    private function setUpTestCase(string $dataSet): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsPlugin/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
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

    /**
     * The header of the content element is not part of any template of this extension — it
     * comes from `lib.contentElement`, which is what `PLUGIN_TYPE_CONTENT_ELEMENT` wires up.
     * On TYPO3 v14 that header partial renders through the `record` view variable, so this is
     * the assertion that fails should the plugin ever be registered without it.
     */
    private function setContentElementHeader(string $header): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['header' => $header], ['uid' => 1]);
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
    public function programListPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase('programListPage');
        $this->setContentElementHeader('Our study programs');

        $this->assertStringContainsString('Our study programs', $this->renderHomePage());
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

    #[Test]
    public function programDetailsPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase('programDetailsPage');
        $this->setContentElementHeader('Program at a glance');

        $this->assertStringContainsString(
            'Program at a glance',
            $this->renderFrontendPage('https://www.acme.com/applied-physics'),
        );
    }
}
