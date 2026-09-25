<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The filter types of the program list: the category filters its form offers, and in which
 * order, as the editor chose them in the element or the integrator for the whole site.
 *
 * Categories: Bachelor of Science (1) and Master of Science (2) are degrees, Full-time (3)
 * is a program type, Berlin (5) and Potsdam (6) are locations, and the tuition fee (7) is
 * a category of the type `costs` assigned to no program. Applied Physics is a full-time
 * bachelor in Berlin, Molecular Chemistry a master in Potsdam, Regional Teaching has no
 * category. No category of the type `teaching_language` exists.
 *
 * The element as imported was saved before the field existed: its FlexForm has no filter
 * types at all.
 */
final class AcademicProgramsListFilterTypesTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const PLUGIN_NAMESPACE = 'tx_academicprograms_programlist';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsListFilterTypes/records.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpSite(bool $withSiteWideFilterTypes = false, bool $withFilterPartialOverride = false): void
    {
        $constants = [
            'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
            'EXT:academic_programs/Configuration/TypoScript/constants.typoscript',
        ];
        if ($withSiteWideFilterTypes) {
            $constants[] = 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/SiteWideFilterTypes.typoscript';
        }
        if ($withFilterPartialOverride) {
            $constants[] = 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/FilterPartialOverride.typoscript';
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
     * The same site configured through the aggregate site set and a site setting instead of
     * a constant. The page object comes from a "sys_template" record, which is included after
     * the sets.
     */
    private function setUpSiteSetSite(string $categoryTypes): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => "@import 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript'",
            ],
        );
        $this->writeSiteConfiguration(
            // The site identifier is part of several caches the test instance keeps for the
            // whole class, so the site of the set needs another one than the site above.
            identifier: 'acme-site-set',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', 'fgtclb/academic-programs'],
                    'settings' => ['plugin.tx_academicprograms.filter.categoryTypes' => $categoryTypes],
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }

    /**
     * Stores the filter types field the way FormEngine saves it: the chosen identifiers as a
     * comma-separated list in the order of the selection, and an empty string for none.
     */
    private function setElementFilterTypes(string $categoryTypes): void
    {
        $flexForm = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
            . '<field index="settings.hideFilter"><value index="vDEF">0</value></field>'
            . '<field index="settings.hideSorting"><value index="vDEF">0</value></field>'
            . '<field index="settings.sorting"><value index="vDEF">title asc</value></field>'
            . '<field index="settings.categories"><value index="vDEF">0</value></field>'
            . '<field index="settings.filter.categoryTypes"><value index="vDEF">' . htmlspecialchars($categoryTypes) . '</value></field>'
            . '<field index="settings.showHiddenRecords"><value index="vDEF">0</value></field>'
            . '</language></sheet></data></T3FlexForms>';
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['pi_flexform' => $flexForm], ['uid' => 1]);
    }

    /**
     * The category filter selects of the form, by their `id`, which is the type identifier,
     * in document order. The sorting selects of the same form are not category filters and
     * are left out by their name.
     *
     * @return list<string>
     */
    private function renderedFilterTypes(string $content): array
    {
        $document = new \DOMDocument();
        $this->assertTrue(@$document->loadHTML($content));
        $selects = (new \DOMXPath($document))->query(
            '//form[contains(@class, "academic-programs-filtersorting")]//select[contains(@name, "[demand][filterCollection]")]'
        );
        $this->assertInstanceOf(\DOMNodeList::class, $selects);

        $identifiers = [];
        foreach ($selects as $select) {
            $this->assertInstanceOf(\DOMElement::class, $select);
            $identifiers[] = $select->getAttribute('id');
        }

        return $identifiers;
    }

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    /**
     * Today's list, and the regression pin for every element that does not use the field:
     * a filter for each type of the group with a category, in the type order of the group.
     * `costs` is offered although its one category is on no listed program: the list offers
     * that category as a disabled option.
     *
     * @return \Generator<string, array{0: string|null}>
     */
    public static function elementWithoutFilterTypesDataProvider(): \Generator
    {
        yield 'saved before the field existed' => [null];
        yield 'saved with the field empty' => [''];
    }

    #[DataProvider('elementWithoutFilterTypesDataProvider')]
    #[Test]
    public function withoutFilterTypesEveryTypeWithCategoriesIsOfferedInTypeOrder(?string $categoryTypes): void
    {
        if ($categoryTypes !== null) {
            $this->setElementFilterTypes($categoryTypes);
        }
        $this->setUpSite();

        $this->assertSame(['costs', 'degree', 'location', 'program_type'], $this->renderedFilterTypes($this->renderHomePage()));
    }

    #[Test]
    public function theChosenTypesAreOfferedInTheChosenOrder(): void
    {
        $this->setElementFilterTypes('location,degree');
        $this->setUpSite();

        $this->assertSame(['location', 'degree'], $this->renderedFilterTypes($this->renderHomePage()));
    }

    #[Test]
    public function theElementChoiceOverridesTheSiteWideFilterTypes(): void
    {
        $this->setElementFilterTypes('location,degree');
        $this->setUpSite(withSiteWideFilterTypes: true);

        $this->assertSame(['location', 'degree'], $this->renderedFilterTypes($this->renderHomePage()));
    }

    #[DataProvider('elementWithoutFilterTypesDataProvider')]
    #[Test]
    public function anElementWithoutFilterTypesOffersTheSiteWideOnes(?string $categoryTypes): void
    {
        if ($categoryTypes !== null) {
            $this->setElementFilterTypes($categoryTypes);
        }
        $this->setUpSite(withSiteWideFilterTypes: true);

        $this->assertSame(['degree'], $this->renderedFilterTypes($this->renderHomePage()));
    }

    #[DataProvider('elementWithoutFilterTypesDataProvider')]
    #[Test]
    public function anElementWithoutFilterTypesOffersTheOnesOfTheSiteSetting(?string $categoryTypes): void
    {
        if ($categoryTypes !== null) {
            $this->setElementFilterTypes($categoryTypes);
        }
        $this->setUpSiteSetSite('program_type,degree');

        $this->assertSame(['program_type', 'degree'], $this->renderedFilterTypes($this->renderHomePage()));
    }

    /**
     * `teaching_language` is a type of the group without any category, `internship` no type
     * of the group at all - the shape of a type a project removed after the element was
     * saved.
     */
    #[Test]
    public function aChosenTypeWithoutCategoriesOrRegistrationIsLeftOut(): void
    {
        $this->setElementFilterTypes('teaching_language,internship,degree');
        $this->setUpSite();

        $content = $this->renderHomePage();
        $this->assertSame(['degree'], $this->renderedFilterTypes($content));
        $this->assertStringContainsString('Applied Physics', $content);
    }

    /**
     * Choosing a type changes which filters are offered, not what a filter offers: a type
     * with a category is offered as without the field, even when its category is on no
     * listed program.
     */
    #[Test]
    public function aChosenTypeWhoseCategoriesAreOnNoListedProgramIsOffered(): void
    {
        $this->setElementFilterTypes('degree,costs');
        $this->setUpSite();

        $content = $this->renderHomePage();
        $this->assertSame(['degree', 'costs'], $this->renderedFilterTypes($content));
        $this->assertStringContainsString('<option value="7" class="level-0" disabled="disabled">Tuition fee</option>', $content);
    }

    /**
     * A template that renders the filter partial without `filterTypes` - an override that
     * passes its own arguments, or a controller subclass that overrides `listAction()` - gets
     * the filters it got before the filter types existed rather than none.
     */
    #[Test]
    public function withoutTheResolvedFilterTypesThePartialOffersEveryTypeWithCategories(): void
    {
        $this->setElementFilterTypes('location,degree');
        $this->setUpSite(withFilterPartialOverride: true);

        $this->assertSame(['costs', 'degree', 'location', 'program_type'], $this->renderedFilterTypes($this->renderHomePage()));
    }

    /**
     * A link carries the filter as a uid list without its type, so a filter of a type the
     * form no longer offers keeps working, and so does every link made before the element
     * was changed.
     */
    #[Test]
    public function aSubmittedFilterOfATypeThatIsNotOfferedStillApplies(): void
    {
        $this->setElementFilterTypes('degree');
        $this->setUpSite();

        $response = $this->requestFrontendPage($this->frontendPostRequest(
            'https://www.acme.com/home',
            [self::PLUGIN_NAMESPACE => ['demand' => ['filterCollection' => ['location' => '5']]]],
        ));
        $content = $this->renderFrontendPage($this->assertSeeOtherWithCacheHash($response));

        $this->assertSame(['degree'], $this->renderedFilterTypes($content));
        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Regional Teaching', $content);
    }
}
