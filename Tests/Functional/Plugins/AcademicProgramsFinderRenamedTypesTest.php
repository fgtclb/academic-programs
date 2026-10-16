<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\CategoryFilterFormAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The program finder of an installation that names the degree and the topic differently:
 * the fixture extension removes both shipped types and adds a qualification and a subject.
 *
 * The finder is on "/home" (page 2) and takes its programs from "/study" (page 4).
 * Categories: Bachelor of Science (1) and Master of Science (2) are qualifications,
 * Engineering (3) and Life sciences (4) subjects, Berlin (5) a location. The finder as
 * imported stores its target page and nothing else.
 */
final class AcademicProgramsFinderRenamedTypesTest extends AbstractAcademicProgramsTestCase
{
    use CategoryFilterFormAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const FINDER_FORM = '//div[contains(concat(" ", normalize-space(@class), " "), " academic-programs-finder ")]/form';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/programs-renamed-category-types';
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsFinderRenamedTypes/records.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param string $constantsFixture A constants fixture below `Fixtures/TypoScript/Constants/`, empty for none.
     */
    private function setUpSite(string $constantsFixture = ''): void
    {
        $constants = [
            'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
            'EXT:academic_programs/Configuration/TypoScript/constants.typoscript',
        ];
        if ($constantsFixture !== '') {
            $constants[] = 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/' . $constantsFixture . '.typoscript';
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
     * Neither the degree nor the topic is a type of the group any more, so the default of
     * the finder offers nothing. The finder then offers every type with categories, in the
     * type order of the group, as the program list does without filter types: the shipped
     * location first, then the subject and the qualification in the order the fixture
     * extension declares them, which is not the alphabetical one.
     */
    #[Test]
    public function withoutTheDegreeAndTheTopicEveryTypeWithCategoriesIsOffered(): void
    {
        $this->setUpSite();

        $filters = $this->renderedCategoryFilters($this->renderFrontendPage('https://www.acme.com/home'), self::FINDER_FORM);

        $this->assertSame(['location', 'subject', 'qualification'], $filters['visible']);
        $this->assertSame([], $filters['more']);
    }

    /**
     * Filter types the site names are kept as they are: of location and degree only the
     * location exists, and the finder offers it alone rather than every type.
     */
    #[Test]
    public function siteWideFilterTypesAreNotReplacedByEveryType(): void
    {
        $this->setUpSite('SiteWideFilterTypesLocationDegree');

        $filters = $this->renderedCategoryFilters($this->renderFrontendPage('https://www.acme.com/home'), self::FINDER_FORM);

        $this->assertSame(['location'], $filters['visible']);
    }

    /**
     * Site-wide filter types none of which has a category are not replaced by every type
     * either: the degree does not exist, and the finder renders no form, like a list
     * whose filter types have no category renders no filter.
     */
    #[Test]
    public function siteWideFilterTypesWithoutCategoriesRenderNoForm(): void
    {
        $this->setUpSite('SiteWideFilterTypes');

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString('Home (EN)', $content);
        $this->assertStringNotContainsString('academic-programs-finder', $content);
    }
}
