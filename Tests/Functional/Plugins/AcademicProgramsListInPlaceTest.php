<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The markup the module `program-list.js` updates the program list in place with.
 *
 * The module finds every part by a data attribute, so this test asserts the inventory it
 * relies on against the really rendered list: the wrapper with the uid of the content
 * element and the two count sentences, the content region it replaces with the number of
 * programs it shows, the empty status element outside of it, the marked form and selects,
 * and the submit button the module hides. `Tests/JavaScript/program-list.test.ts` drives the module on a copy of
 * this markup, so a template that drops one of them turns this test red rather than
 * leaving the JavaScript suite green on a fixture nobody renders any more.
 *
 * Without JavaScript the form keeps working through its submit button. The submission and
 * its redirect are the ones `AcademicProgramsFilterUrlTest` covers in detail.
 *
 * Categories: Bachelor of Science (1) and Master of Science (2) are degrees, Full-time (3)
 * is a program type. Applied Physics is a full-time bachelor, Molecular Chemistry a master,
 * Regional Teaching has no category. The list on `/home` is content element 1.
 */
final class AcademicProgramsListInPlaceTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const PLUGIN_NAMESPACE = 'tx_academicprograms_programlist';
    private const FORM_CLASS = 'academic-programs-filtersorting';
    private const MODULE = '@fgtclb/academic-programs/frontend/program-list.js';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsFilterUrl/programListPages.csv');
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
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function theListMarksThePartsTheModuleUpdates(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $xpath = $this->xpath($content);

        $list = $this->singleElement($xpath, '//*[@data-academic-programs-list="1"]');
        $this->assertSame('%d program found', $list->getAttribute('data-academic-programs-list-count-one'));
        $this->assertSame('%d programs found', $list->getAttribute('data-academic-programs-list-count-other'));

        $region = $this->singleElement($xpath, './/*[@data-academic-programs-list-content]', $list);
        $this->assertSame('3', $region->getAttribute('data-academic-programs-list-total'));
        $form = $this->singleElement($xpath, './/form[contains(concat(" ", normalize-space(@class), " "), " ' . self::FORM_CLASS . ' ")]', $region);
        $this->assertSame('post', strtolower($form->getAttribute('method')));
        $this->assertTrue($form->hasAttribute('data-academic-programs-list-form'));
        $button = $this->singleElement($xpath, './/*[@data-academic-programs-list-submit]//button', $form);
        $this->assertSame('submit', $button->getAttribute('type'));
        $this->assertSame('Show programs', trim($button->textContent));
        // Sorting field and direction, degree and program type, each marked for an override
        // of the list template that lacks the region.
        $this->assertSame(4, $this->countElements($xpath, './/select', $form));
        $this->assertSame(4, $this->countElements($xpath, './/select[@data-academic-programs-list-select]', $form));
        $this->assertStringContainsString('Molecular Chemistry', $region->textContent);

        // The status element stays where it is when the region is replaced, so that a screen
        // reader hears what is written into it. Empty, so loading the page announces nothing.
        $status = $this->singleElement($xpath, './*[@data-academic-programs-list-status]', $list);
        $this->assertSame('status', $status->getAttribute('role'));
        $this->assertSame('polite', $status->getAttribute('aria-live'));
        $this->assertSame('', $status->textContent);

        $this->assertSame(0, $this->countElements($xpath, '//*[@onchange]'), 'The list still carries an inline event handler.');

        $this->assertSame(1, preg_match('#<script type="importmap"[^>]*>(.*?)</script>#s', $content, $matches), 'The page has no import map.');
        /** @var array{imports?: array<string, string>} $importMap */
        $importMap = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString(
            'academic_programs/Resources/Public/JavaScript/frontend/',
            $importMap['imports']['@fgtclb/academic-programs/frontend/'] ?? '',
        );
        $this->assertStringContainsString(self::MODULE, $content);
    }

    /**
     * The submit button posts the form like the inline handler did, and the page the list
     * redirects to counts the programs it shows. That page is what the module requests.
     */
    #[Test]
    public function theFilteredListCountsTheProgramsItShows(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::PLUGIN_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2']]],
        ]);
        $location = $this->assertSeeOtherWithCacheHash($response);

        $xpath = $this->xpath($this->renderFrontendPage($location));

        $region = $this->singleElement($xpath, '//*[@data-academic-programs-list="1"]//*[@data-academic-programs-list-content]');
        $this->assertSame('1', $region->getAttribute('data-academic-programs-list-total'));
        $this->assertStringContainsString('Molecular Chemistry', $region->textContent);
        $this->assertStringNotContainsString('Applied Physics', $region->textContent);
    }

    /**
     * Each list element carries its own uid, so the module finds every list of the page in
     * the page of the filter URL.
     */
    #[Test]
    public function twoListsOnOnePageAreMarkedApart(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $element = $connection->select(['*'], 'tt_content', ['uid' => 1])->fetchAssociative();
        $this->assertIsArray($element);
        $connection->insert('tt_content', array_merge($element, ['uid' => 4, 'sorting' => 512]));

        $xpath = $this->xpath($this->renderFrontendPage('https://www.acme.com/home'));

        foreach (['1', '4'] as $uid) {
            $list = $this->singleElement($xpath, '//*[@data-academic-programs-list="' . $uid . '"]');
            $this->singleElement($xpath, './/*[@data-academic-programs-list-content]//form', $list);
            $this->singleElement($xpath, './*[@data-academic-programs-list-status]', $list);
        }
    }

    /**
     * A list without a form has nothing to update in place: neither the module nor a
     * button is rendered.
     */
    #[Test]
    public function withoutAFormTheModuleIsNotLoaded(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tt_content')->update(
            'tt_content',
            [
                'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                    . '<field index="settings.hideFilter"><value index="vDEF">1</value></field>'
                    . '<field index="settings.hideSorting"><value index="vDEF">1</value></field>'
                    . '<field index="settings.sorting"><value index="vDEF">title asc</value></field>'
                    . '</language></sheet></data></T3FlexForms>',
            ],
            ['uid' => 1],
        );

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString(self::MODULE, $content);
        $this->assertStringNotContainsString('data-academic-programs-list-submit', $content);
    }

    private function xpath(string $content): \DOMXPath
    {
        $document = new \DOMDocument();
        $this->assertTrue(@$document->loadHTML($content), 'The page is no HTML.');

        return new \DOMXPath($document);
    }

    private function countElements(\DOMXPath $xpath, string $expression, ?\DOMNode $context = null): int
    {
        $nodes = $xpath->query($expression, $context);
        $this->assertNotFalse($nodes);

        return $nodes->length;
    }

    private function singleElement(\DOMXPath $xpath, string $expression, ?\DOMNode $context = null): \DOMElement
    {
        $nodes = $xpath->query($expression, $context);
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length, 'Expected exactly one element for ' . $expression);
        $element = $nodes->item(0);
        $this->assertInstanceOf(\DOMElement::class, $element);

        return $element;
    }
}
