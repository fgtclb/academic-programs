<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Facts;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * The text facts of a program follow the rich text editor of their field, in the three
 * places that show the facts: the program page, the program details content element and
 * the program card.
 *
 * The fixture `tests/programs-plain-text-facts` is a site package that switches the
 * editor in its `Configuration/TCA/Overrides/pages.php`, once in each of the three ways a
 * project does it: off for the job profile on the program page type only, off for the
 * performance scope on every page type, and off for the prerequisites on every page type
 * but on again for the program page type.
 *
 * "Applied Physics" stores markup in all three fields. Page 10 is rendered as a program
 * page, page 12 carries the details content element, and "/programs" lists both with their
 * cards. A TYPO3 v14 test instance orders the packages by their keys, so the first test
 * asserts that the fixture loads after academic_programs.
 */
final class ProgramFactsPlainTextTest extends AbstractAcademicProgramsTestCase
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

    private const FACTS = 'jobProfile,performanceScope,prerequisites,degree,creditPoints';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/programs-plain-text-facts');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/programPlainTextFacts.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The program page is rendered by the site package, the details element and the list
     * by the page object of the plugin tests, which renders the content of the page and
     * nothing else. Every place lists the five facts.
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
            array_search('test_programs_plain_text_facts', $packageKeys, true),
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
    public function aFieldWithoutEditorForTheProgramPageTypeRendersEscapedWithItsLineBreaks(string $url, bool $programPage): void
    {
        $this->setUpSite($programPage);

        $item = $this->factItem($this->renderPlace($url), 'jobProfile');

        $this->assertStringContainsString('<span>', $item);
        $this->assertStringContainsString('Research &amp; teaching<br />', $item);
        $this->assertStringContainsString('&lt;b&gt;Industry&lt;/b&gt;', $item);
        $this->assertStringNotContainsString('<b>Industry</b>', $item);
        $this->assertStringNotContainsString('ce-bodytext', $item);
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function aFieldWithoutEditorOnEveryPageTypeRendersEscapedWithItsLineBreaks(string $url, bool $programPage): void
    {
        $this->setUpSite($programPage);

        $item = $this->factItem($this->renderPlace($url), 'performanceScope');

        $this->assertStringContainsString('Lectures &amp; labs<br />', $item);
        $this->assertStringContainsString('&lt;i&gt;Thesis&lt;/i&gt;', $item);
        $this->assertStringNotContainsString('<i>Thesis</i>', $item);
        $this->assertStringNotContainsString('ce-bodytext', $item);
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function aFieldWithEditorOnAgainForTheProgramPageTypeRendersItsHtml(string $url, bool $programPage): void
    {
        $this->setUpSite($programPage);

        $item = $this->factItem($this->renderPlace($url), 'prerequisites');

        $this->assertStringContainsString('<span class="ce-bodytext">', $item);
        $this->assertStringContainsString('<p>Good <strong>maths</strong></p>', $item);
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function categoryTypeAndCreditPointsFactsCarryNoBodyTextClass(string $url, bool $programPage): void
    {
        $this->setUpSite($programPage);

        $content = $this->renderPlace($url);

        foreach (['degree' => 'Bachelor of Science', 'creditPoints' => '180'] as $identifier => $value) {
            $item = $this->factItem($content, $identifier);
            $this->assertStringContainsString('<span>', $item);
            $this->assertStringContainsString($value, $item);
            $this->assertStringNotContainsString('ce-bodytext', $item);
        }
        $this->assertStringNotContainsString('class=""', $content);
    }

    /**
     * The rendered page, or the card of "Applied Physics" for the list.
     */
    private function renderPlace(string $url): string
    {
        $content = $this->renderFrontendPage($url);
        if ($url !== self::LIST_PAGE) {
            return $content;
        }
        foreach (explode('academic-programs-item ', $content) as $card) {
            if (str_contains($card, '>Applied Physics</a>')) {
                return $card;
            }
        }
        $this->fail('The list renders no card of "Applied Physics".');
    }

    /**
     * The list item of the fact with the given identifier, from its opening tag to its end.
     */
    private function factItem(string $content, string $identifier): string
    {
        $pattern = '#<li class="academic-programs-facts__item academic-programs-facts__item--' . preg_quote($identifier, '#') . '[ "].*?</li>#s';
        $this->assertSame(1, preg_match($pattern, $content, $matches), sprintf('No fact "%s" is rendered.', $identifier));
        return $matches[0];
    }
}
