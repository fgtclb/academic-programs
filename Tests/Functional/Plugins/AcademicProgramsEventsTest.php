<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * What an installed extension can do to the program list and the program finder, through
 * `ModifyProgramDemandEvent` and `ModifyProgramListEvent`.
 *
 * `EXT:test_program_events` ships one listener per event. Each stays inert until a plugin
 * setting asks for something, so each test below includes the TypoScript file of the
 * behaviour it is about. That both plugins render unchanged while *no* extension listens is
 * what every other plugin test of this extension asserts. None of them loads the fixture.
 *
 * The records are those of `AcademicProgramsFinderTest`: the finder on `/home` and the list
 * on `/programs` both take the programs of the storage folder "Study", which are Applied
 * Physics (Bachelor of Science, Engineering), Molecular Chemistry (Master of Science, Life
 * sciences) and Mechanical Engineering (Master of Science, Engineering). The Diploma is on
 * no program. Doctoral Studies, with the Doctorate, sits on the root page, outside the
 * storage of both elements. The list is sorted by title.
 */
final class AcademicProgramsEventsTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicprograms_programlist';

    private const FINDER_FORM_CLASS = 'academic-programs-finder';

    private const FIXTURE_TYPOSCRIPT = 'EXT:test_program_events/Configuration/TypoScript/';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-program-events');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsFinder/records.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $listenerTypoScriptFiles The behaviour the fixture listeners are asked for,
     *                                              as file names below the fixture's TypoScript folder.
     */
    private function setUpSite(array $listenerTypoScriptFiles = []): void
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
                    'EXT:academic_programs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_programs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ...array_map(
                        static fn(string $file): string => self::FIXTURE_TYPOSCRIPT . $file,
                        $listenerTypoScriptFiles,
                    ),
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    private function renderListPage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/programs');
    }

    private function renderFinderPage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    /**
     * The options of one select of the finder as `value => state`, where the state is
     * `selected`, `disabled` or `enabled`.
     *
     * @return array<string, string>
     */
    private function finderOptions(string $content, string $type): array
    {
        $document = new \DOMDocument();
        $this->assertTrue(@$document->loadHTML('<?xml encoding="UTF-8">' . $content));
        $xpath = new \DOMXPath($document);
        $query = sprintf(
            '//form[contains(concat(" ", normalize-space(@class), " "), " %s ")]//select[@name="%s[demand][filterCollection][%s]"]/option',
            self::FINDER_FORM_CLASS,
            self::LIST_NAMESPACE,
            $type,
        );
        $options = [];
        foreach ($xpath->query($query) ?: [] as $option) {
            $this->assertInstanceOf(\DOMElement::class, $option);
            $options[$option->getAttribute('value')] = match (true) {
                $option->hasAttribute('selected') => 'selected',
                $option->hasAttribute('disabled') => 'disabled',
                default => 'enabled',
            };
        }
        $this->assertNotSame([], $options, sprintf('The finder renders no select for "%s".', $type));

        return $options;
    }

    private function assertRenderedInOrder(string $content, string ...$strings): void
    {
        $offset = 0;
        foreach ($strings as $string) {
            $position = strpos($content, $string, $offset);
            $this->assertIsInt($position, sprintf('"%s" is not rendered after offset %d.', $string, $offset));
            $offset = $position + strlen($string);
        }
    }

    /**
     * The demand a listener hands back is the one that is queried: the element filters by no
     * category, so every program of the storage folder would be listed without the listener.
     */
    #[Test]
    public function aDemandListenerNarrowsTheList(): void
    {
        $this->setUpSite(['CategoryFilter.typoscript']);

        $content = $this->renderListPage();
        $this->assertStringContainsString('Mechanical Engineering', $content);
        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
    }

    /**
     * A demand the listener built itself replaces the one of the element entirely: it names
     * the root page as storage, where only Doctoral Studies sits.
     */
    #[Test]
    public function theDemandAListenerHandsBackIsTheOneThatIsQueried(): void
    {
        $this->setUpSite(['FreshDemandOnePage.typoscript']);

        $content = $this->renderListPage();
        $this->assertStringContainsString('Doctoral Studies', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Mechanical Engineering', $content);
    }

    /**
     * The finder dispatches the demand event as well and offers the categories of the
     * programs the changed demand finds: the Bachelor of Science is on Applied Physics alone,
     * which the listener left out, so it is disabled like the Diploma and the Doctorate.
     */
    #[Test]
    public function aDemandListenerNarrowsTheOptionsOfTheFinder(): void
    {
        $this->setUpSite(['CategoryFilter.typoscript']);

        $content = $this->renderFinderPage();
        $this->assertSame(
            ['' => 'enabled', '1' => 'disabled', '2' => 'enabled', '3' => 'disabled', '8' => 'disabled'],
            $this->finderOptions($content, 'degree'),
        );
        $this->assertSame(
            ['' => 'enabled', '4' => 'enabled', '5' => 'enabled'],
            $this->finderOptions($content, 'topic'),
        );
    }

    /**
     * A listener that replaces the result replaces what is rendered, in the order the new
     * result carries: the query reversed.
     */
    #[Test]
    public function aListListenerReplacesTheResult(): void
    {
        $this->setUpSite(['ReverseOrder.typoscript']);

        $this->assertRenderedInOrder(
            $this->renderListPage(),
            'Molecular Chemistry',
            'Mechanical Engineering',
            'Applied Physics',
        );
    }

    /**
     * The categories are computed before the list event and are not recomputed after it, so
     * a listener that wants the filter of the list to differ sets them. The programs are
     * left alone here, which is what tells the two setters apart.
     */
    #[Test]
    public function aListListenerReplacesTheCategoriesOfTheList(): void
    {
        $this->setUpSite(['ReplaceCategories.typoscript']);

        $content = $this->renderListPage();
        $this->assertStringContainsString('>Master of Science</option>', $content);
        $this->assertStringNotContainsString('>Bachelor of Science</option>', $content);
        $this->assertStringNotContainsString('>Engineering</option>', $content);
        $this->assertRenderedInOrder($content, 'Applied Physics', 'Mechanical Engineering', 'Molecular Chemistry');
    }

    /**
     * The finder dispatches the list event as well, and its selects offer the categories the
     * listener hands back.
     */
    #[Test]
    public function aListListenerReplacesTheOptionsOfTheFinder(): void
    {
        $this->setUpSite(['ReplaceCategories.typoscript']);

        $this->assertSame(
            ['' => 'enabled', '2' => 'enabled'],
            $this->finderOptions($this->renderFinderPage(), 'degree'),
        );
    }

    /**
     * The programs the list listener hands back are the ones the finder narrows its options
     * by and counts in the browser: Molecular Chemistry (11), which the listener removes, is
     * not handed over, and its categories are not either.
     */
    #[Test]
    public function aListListenerRemovesAProgramFromTheFinderNarrowing(): void
    {
        $this->setUpSite(['ExcludeProgram.typoscript']);

        $document = new \DOMDocument();
        $this->assertTrue(@$document->loadHTML('<?xml encoding="UTF-8">' . $this->renderFinderPage()));
        $forms = (new \DOMXPath($document))->query('//form[@data-academic-programs-finder-programs]');
        $this->assertInstanceOf(\DOMNodeList::class, $forms);
        $form = $forms->item(0);
        $this->assertInstanceOf(\DOMElement::class, $form);
        $this->assertSame(
            [[1, 4], [2, 4]],
            json_decode($form->getAttribute('data-academic-programs-finder-programs'), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * The list event carries the view, so a listener assigns variables a project template
     * renders. The fixture extension ships such a template and puts it in front of the
     * shipped one. The value proves the action and the queried programs reach the listener
     * as well.
     */
    #[Test]
    public function aListListenerAssignsAViewVariable(): void
    {
        $this->setUpSite(['ListMarker.typoscript']);

        $content = $this->renderListPage();
        $this->assertStringContainsString('<p class="test-program-list-marker">programs-marker|list|3</p>', $content);
        $this->assertStringContainsString('Applied Physics', $content);
    }
}
