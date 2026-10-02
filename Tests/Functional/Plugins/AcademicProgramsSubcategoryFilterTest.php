<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\CategoryFilterFormAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The option "Include subcategories" of the program list and the program finder: a selected
 * category matches the programs that carry one of its subcategories, and the filter offers a
 * category as soon as a program carries one of its subcategories.
 *
 * The finder is on "/home" (page 2), the list on "/programs" (page 3), and both take their
 * programs from "/study" (page 4). Degrees: Bachelor (1) with Bachelor of Science (3) below
 * it and Bachelor of Science with Honours (4) below that, Master (2) with Master of Science
 * (8) below it. Locations: Berlin (6) and Potsdam (7). Engineering (9) is a topic no program
 * carries, so the finder has its second select. Applied Physics carries Bachelor of Science
 * and Berlin, Materials Science Bachelor of Science with Honours and Potsdam, Business
 * Studies Bachelor itself and Potsdam, Molecular Chemistry Master of Science and Berlin. No
 * program carries Master itself.
 *
 * Both elements as imported were saved before the option existed. Most tests store the
 * option explicitly, one renders the list as imported.
 */
final class AcademicProgramsSubcategoryFilterTest extends AbstractAcademicProgramsTestCase
{
    use CategoryFilterFormAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicprograms_programlist';
    private const LIST_FORM_CLASS = 'academic-programs-filtersorting';
    private const FINDER_FORM_CLASS = 'academic-programs-finder';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsSubcategoryFilter/records.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpSite(bool $hideDisabledOptions = false): void
    {
        $constants = [
            'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
            'EXT:academic_programs/Configuration/TypoScript/constants.typoscript',
        ];
        if ($hideDisabledOptions) {
            $constants[] = 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/HideDisabledOptions.typoscript';
        }
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => $constants,
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

    /**
     * Stores the list element the way FormEngine saves it, with the option and the categories
     * the editor restricted it to.
     */
    private function setListSettings(bool $includeSubcategories, int ...$categoryUids): void
    {
        $flexForm = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
            . '<field index="settings.hideFilter"><value index="vDEF">0</value></field>'
            . '<field index="settings.filter.includeSubcategories"><value index="vDEF">' . (int)$includeSubcategories . '</value></field>'
            . '<field index="settings.hideSorting"><value index="vDEF">0</value></field>'
            . '<field index="settings.sorting"><value index="vDEF">title asc</value></field>'
            . '<field index="settings.categories"><value index="vDEF">' . count($categoryUids) . '</value></field>'
            . '<field index="settings.showHiddenRecords"><value index="vDEF">0</value></field>'
            . '</language></sheet></data></T3FlexForms>';
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['pi_flexform' => $flexForm], ['uid' => 2]);
        foreach (array_values($categoryUids) as $sorting => $categoryUid) {
            $this->getConnectionPool()->getConnectionForTable('sys_category_record_mm')->insert('sys_category_record_mm', [
                'uid_local' => $categoryUid,
                'uid_foreign' => 2,
                'tablenames' => 'tt_content',
                'fieldname' => 'pi_flexform',
                'sorting' => 0,
                'sorting_foreign' => $sorting + 1,
            ]);
        }
    }

    private function setFinderSettings(bool $includeSubcategories, string $preselectedCategories = ''): void
    {
        $flexForm = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
            . '<field index="settings.listPid"><value index="vDEF">3</value></field>'
            . '<field index="settings.preselectedCategories"><value index="vDEF">' . $preselectedCategories . '</value></field>'
            . '<field index="settings.filter.includeSubcategories"><value index="vDEF">' . (int)$includeSubcategories . '</value></field>'
            . '</language></sheet></data></T3FlexForms>';
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['pi_flexform' => $flexForm], ['uid' => 1]);
    }

    /**
     * Submits the filter of the list and follows the redirect to the filtered list.
     *
     * @param array<string, string> $filterCollection
     */
    private function renderFilteredList(array $filterCollection): string
    {
        $response = $this->requestFrontendPage($this->frontendPostRequest(
            'https://www.acme.com/programs',
            [self::LIST_NAMESPACE => ['demand' => ['filterCollection' => $filterCollection]]],
        ));

        return $this->renderFrontendPage($this->assertSeeOtherWithCacheHash($response));
    }

    /**
     * The titles of the listed programs, in the order of the list.
     *
     * @return list<string>
     */
    private function listedPrograms(string $content): array
    {
        $titles = [];
        foreach (['Applied Physics', 'Business Studies', 'Materials Science', 'Molecular Chemistry'] as $title) {
            // A program title appears once per list entry: in its link.
            if (preg_match('#<a [^>]*href="/study/[a-z-]+"[^>]*>\s*' . preg_quote($title, '#') . '\s*</a>#', $content) === 1) {
                $titles[] = $title;
            }
        }

        return $titles;
    }

    /**
     * @return \Generator<string, array{0: bool, 1: list<string>}>
     */
    public static function degreeOptionsOfTheListDataProvider(): \Generator
    {
        yield 'option off, as before' => [false, ['All', 'Bachelor', 'Master (disabled)', 'Bachelor of Science', 'Bachelor of Science with Honours', 'Master of Science']];
        yield 'option on' => [true, ['All', 'Bachelor', 'Master', 'Bachelor of Science', 'Bachelor of Science with Honours', 'Master of Science']];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('degreeOptionsOfTheListDataProvider')]
    #[Test]
    public function theListOffersAParentWhoseSubcategoryIsCarriedWithTheOptionOnly(bool $includeSubcategories, array $expected): void
    {
        $this->setListSettings($includeSubcategories);
        $this->setUpSite();

        $options = $this->categoryFilterOptions($this->renderFrontendPage('https://www.acme.com/programs'), self::LIST_FORM_CLASS, 'degree');

        $this->assertSame($expected, $this->withoutAllOptionLabel($options));
    }

    /**
     * @return \Generator<string, array{0: bool, 1: bool}>
     */
    public static function hiddenOptionsDataProvider(): \Generator
    {
        yield 'option off, as before' => [false, false];
        yield 'option on' => [true, true];
    }

    /**
     * The list element as imported, saved before the field existed, offers the parent as it
     * did before.
     */
    #[Test]
    public function aListSavedBeforeTheOptionExistedOffersTheParentDisabled(): void
    {
        $this->setUpSite();

        $options = $this->categoryFilterOptions($this->renderFrontendPage('https://www.acme.com/programs'), self::LIST_FORM_CLASS, 'degree');

        $this->assertContains('Master (disabled)', $options);
    }

    #[DataProvider('hiddenOptionsDataProvider')]
    #[Test]
    public function theListKeepsAParentWhoseSubcategoryIsCarriedWhenOptionsWithoutResultsAreHidden(bool $includeSubcategories, bool $offered): void
    {
        $this->setListSettings($includeSubcategories);
        $this->setUpSite(hideDisabledOptions: true);

        $options = $this->categoryFilterOptions($this->renderFrontendPage('https://www.acme.com/programs'), self::LIST_FORM_CLASS, 'degree');

        $this->assertSame($offered, in_array('Master', $options, true));
        $this->assertNotContains('Master (disabled)', $options);
    }

    #[Test]
    public function withoutTheOptionAVisitorFilteringByTheParentFindsItsOwnProgramsOnly(): void
    {
        $this->setListSettings(false);
        $this->setUpSite();

        $this->assertSame(['Business Studies'], $this->listedPrograms($this->renderFilteredList(['degree' => '1'])));
    }

    #[Test]
    public function withTheOptionAVisitorFilteringByTheParentFindsTheWholeSubtree(): void
    {
        $this->setListSettings(true);
        $this->setUpSite();

        $content = $this->renderFilteredList(['degree' => '1']);

        $this->assertSame(['Applied Physics', 'Business Studies', 'Materials Science'], $this->listedPrograms($content));
        $this->assertStringContainsString('<option value="1" class="level-0" selected="selected">Bachelor</option>', $content);
    }

    #[Test]
    public function withTheOptionEverySelectionStillHasToMatch(): void
    {
        $this->setListSettings(true);
        $this->setUpSite();

        $this->assertSame(['Applied Physics'], $this->listedPrograms($this->renderFilteredList(['degree' => '1', 'location' => '6'])));
    }

    /**
     * @return \Generator<string, array{0: bool, 1: list<string>}>
     */
    public static function preselectedParentDataProvider(): \Generator
    {
        yield 'option off, as before' => [false, []];
        yield 'option on' => [true, ['Molecular Chemistry']];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('preselectedParentDataProvider')]
    #[Test]
    public function aParentTheEditorRestrictedTheListToFollowsTheOption(bool $includeSubcategories, array $expected): void
    {
        $this->setListSettings($includeSubcategories, 2);
        $this->setUpSite();

        $this->assertSame($expected, $this->listedPrograms($this->renderFrontendPage('https://www.acme.com/programs')));
    }

    /**
     * @return \Generator<string, array{0: bool, 1: string}>
     */
    public static function finderOptionDataProvider(): \Generator
    {
        yield 'option off, as before' => [false, 'disabled'];
        yield 'option on' => [true, 'enabled'];
    }

    #[DataProvider('finderOptionDataProvider')]
    #[Test]
    public function theFinderOffersAParentWhoseSubcategoryIsCarriedWithTheOptionOnly(bool $includeSubcategories, string $state): void
    {
        $this->setFinderSettings($includeSubcategories);
        $this->setUpSite();

        $options = $this->categoryFilterOptions($this->renderFrontendPage('https://www.acme.com/home'), self::FINDER_FORM_CLASS, 'degree');

        $this->assertContains('Master' . ($state === 'disabled' ? ' (disabled)' : ''), $options);
    }

    /**
     * @return \Generator<string, array{0: bool, 1: list<list<int>>}>
     */
    public static function finderProgramCategoriesDataProvider(): \Generator
    {
        yield 'option off, each program with its own categories' => [
            false,
            [[3], [4], [1], [8]],
        ];
        yield 'option on, each program with the ancestors of its categories as well' => [
            true,
            [[1, 3], [1, 3, 4], [1], [2, 8]],
        ];
    }

    /**
     * The programs the finder hands to the browser for narrowing its options, Applied Physics,
     * Materials Science, Business Studies and Molecular Chemistry in that order. With the
     * option on, a program carries every ancestor of its categories as well, so the browser keeps
     * "Master" selectable for Molecular Chemistry, whose Master of Science is below it, the
     * way the server offers it. Engineering is offered and carried by no program, the
     * locations are not offered.
     *
     * @param list<list<int>> $expected
     */
    #[DataProvider('finderProgramCategoriesDataProvider')]
    #[Test]
    public function theFinderHandsOverTheAncestorsOfACarriedCategoryWithTheOptionOnly(bool $includeSubcategories, array $expected): void
    {
        $this->setFinderSettings($includeSubcategories);
        $this->setUpSite();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(1, preg_match('#<form[^>]*\sdata-academic-programs-finder-programs="([^"]*)"#', $content, $matches), 'The finder form carries no programs.');
        $this->assertSame($expected, json_decode(htmlspecialchars_decode($matches[1]), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @return \Generator<string, array{0: bool, 1: bool}>
     */
    public static function finderPreselectionDataProvider(): \Generator
    {
        yield 'option off, as before' => [false, false];
        yield 'option on' => [true, true];
    }

    /**
     * A preselected parent no program carries itself is selected when the finder offers it,
     * and nothing is selected while it is a disabled option.
     */
    #[DataProvider('finderPreselectionDataProvider')]
    #[Test]
    public function aPreselectedParentWhoseSubcategoryIsCarriedFollowsTheOption(bool $includeSubcategories, bool $selected): void
    {
        $this->setFinderSettings($includeSubcategories, '2');
        $this->setUpSite();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            $selected ? 1 : 0,
            preg_match('#<option value="2"[^>]*\sselected="selected"[^>]*>Master</option>#', $content),
        );
        $this->assertSame(1, preg_match('#<option value="2"[^>]*>Master</option>#', $content), 'The finder offers no option for Master.');
    }

    /**
     * The whole way a visitor takes with the option on in both elements: the finder offers
     * "Master", and the list on the target page finds the Master of Science program for it.
     */
    #[Test]
    public function submittingTheParentFromTheFinderFindsTheWholeSubtree(): void
    {
        $this->setFinderSettings(true);
        $this->setListSettings(true);
        $this->setUpSite();

        $response = $this->submitFrontendForm(
            'https://www.acme.com/home',
            self::FINDER_FORM_CLASS,
            [self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2']]]],
        );
        $content = $this->renderFrontendPage($this->assertSeeOtherWithCacheHash($response));

        $this->assertSame(['Molecular Chemistry'], $this->listedPrograms($content));
    }

    /**
     * The label of the prepended "all" option depends on the language labels, which are not
     * what is tested here.
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
