<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * A category type a project adds to the group `programs` has no label in the language file
 * of this extension. The frontend names it by the title it is registered with instead,
 * translated into the language of the page, and a label the site sets still wins.
 *
 * The fixture extension `test_programs_titled_category_type` registers `teaching_form`
 * with the title "Form of teaching", "Lehrform" in German, and retitles the shipped
 * `degree` "Qualification". Applied Physics (page 10) is a Bachelor of Science taught in
 * evening classes, page 12 shows it with the details element, "/programs" lists it with its
 * filter and "/finder" offers `teaching_form` and `degree`. Page 20 and "/de/studiengaenge"
 * are the German program page and list.
 */
final class CategoryTypeTitleTest extends AbstractAcademicProgramsTestCase
{
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
    private const TRANSLATED_LIST_PAGE = 'https://www.acme.com/de/studiengaenge';
    private const FINDER_PAGE = 'https://www.acme.com/finder';

    private const SITE_PACKAGE = 'EXT:academic_programs/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackage.typoscript';
    private const PLUGIN_RENDERING = 'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript';

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/programs-titled-category-type';
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/categoryTypeTitle.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * Without a site package, the page object of the plugin tests renders the content of
     * the page and nothing else.
     */
    private function setUpSite(bool $programPage = false, string $constants = '', string $setup = ''): void
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
        $template = $connection->select(['uid', 'constants', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update(
            'sys_template',
            ['constants' => $template['constants'] . LF . $constants, 'config' => $template['config'] . LF . $setup],
            ['uid' => $template['uid']],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
        ]);
    }

    #[Test]
    public function theProgramPageNamesTheFactByTheRegisteredTitle(): void
    {
        $this->setUpSite(programPage: true);

        $this->assertSame('Form of teaching', $this->factLabel($this->renderFrontendPage(self::PROGRAM_PAGE), 'teaching_form'));
    }

    #[Test]
    public function theGermanProgramPageNamesTheFactByTheGermanTitle(): void
    {
        $this->setUpSite(programPage: true);

        $this->assertSame('Lehrform', $this->factLabel($this->renderFrontendPage(self::TRANSLATED_PROGRAM_PAGE), 'teaching_form'));
    }

    #[Test]
    public function theDetailsElementNamesTheFactByTheRegisteredTitle(): void
    {
        $this->setUpSite();

        $this->assertSame('Form of teaching', $this->factLabel($this->renderFrontendPage(self::DETAILS_PAGE), 'teaching_form'));
    }

    #[Test]
    public function theProgramCardNamesTheFactByTheRegisteredTitle(): void
    {
        $this->setUpSite(constants: 'plugin.tx_academicprograms.card.fields = degree,teaching_form');

        $this->assertSame('Form of teaching', $this->factLabel($this->renderFrontendPage(self::LIST_PAGE), 'teaching_form'));
    }

    #[Test]
    public function theListFilterNamesTheSelectByTheRegisteredTitle(): void
    {
        $this->setUpSite();

        $this->assertSame('Form of teaching', $this->selectLabel($this->renderFrontendPage(self::LIST_PAGE), 'teaching_form'));
    }

    #[Test]
    public function theGermanListFilterNamesTheSelectByTheGermanTitle(): void
    {
        $this->setUpSite();

        $this->assertSame('Lehrform', $this->selectLabel($this->renderFrontendPage(self::TRANSLATED_LIST_PAGE), 'teaching_form'));
    }

    #[Test]
    public function theFinderNamesTheSelectByTheRegisteredTitle(): void
    {
        $this->setUpSite();

        $this->assertSame('Form of teaching', $this->selectLabel($this->renderFrontendPage(self::FINDER_PAGE), 'academic-programs-finder-teaching_form-3'));
    }

    /**
     * The fixture retitles the shipped degree "Qualification". Its label in the language
     * file of the extension still names it.
     */
    #[Test]
    public function aShippedTypeKeepsTheLabelOfTheExtension(): void
    {
        $this->setUpSite();

        $this->assertSame('Degree', $this->selectLabel($this->renderFrontendPage(self::LIST_PAGE), 'degree'));
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function siteLabelDataProvider(): \Generator
    {
        yield 'label of the extension' => ['plugin.tx_academicprograms', self::LIST_PAGE];
        yield 'label of the list plugin' => ['plugin.tx_academicprograms_programlist', self::LIST_PAGE];
        yield 'label of the finder plugin' => ['plugin.tx_academicprograms_programfinder', self::FINDER_PAGE];
    }

    #[DataProvider('siteLabelDataProvider')]
    #[Test]
    public function aLabelOfTheSiteWinsOverTheRegisteredTitle(string $path, string $url): void
    {
        $this->setUpSite(setup: $path . '._LOCAL_LANG.default.sys_category\.programs\.teaching_form = Mode of study');

        $content = $this->renderFrontendPage($url);

        $this->assertSame('Mode of study', $this->selectLabel($content, $url === self::FINDER_PAGE ? 'academic-programs-finder-teaching_form-3' : 'teaching_form'));
    }

    #[Test]
    public function aLabelOfTheSiteWinsOnTheProgramPage(): void
    {
        $this->setUpSite(programPage: true, setup: 'plugin.tx_academicprograms._LOCAL_LANG.default.sys_category\.programs\.teaching_form = Mode of study');

        $this->assertSame('Mode of study', $this->factLabel($this->renderFrontendPage(self::PROGRAM_PAGE), 'teaching_form'));
    }

    private function factLabel(string $content, string $identifier): string
    {
        $pattern = '#<li class="academic-programs-facts__item academic-programs-facts__item--' . preg_quote($identifier, '#') . '[ "].*?<b>(.*?)</b>#s';
        if (preg_match($pattern, $content, $matches) !== 1) {
            $this->fail(sprintf('No fact "%s" is rendered.', $identifier));
        }
        return rtrim(trim((string)preg_replace('/\s+/', ' ', strip_tags($matches[1]))), ':');
    }

    private function selectLabel(string $content, string $selectId): string
    {
        $pattern = '#<label for="' . preg_quote($selectId, '#') . '"[^>]*>(.*?)</label>#s';
        if (preg_match($pattern, $content, $matches) !== 1) {
            $this->fail(sprintf('No select "%s" is labelled.', $selectId));
        }
        return trim((string)preg_replace('/\s+/', ' ', strip_tags($matches[1])));
    }
}
