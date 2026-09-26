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
 * The program finder: a form of category selects on one page that opens the program list
 * of another page with the selection applied.
 *
 * The finder is on "/home" (page 2), its target list on "/programs" (page 3), and both take
 * their programs from "/study" (page 4). Categories: Bachelor of Science (1), Master of
 * Science (2), Diploma (3) and Doctorate (8) are degrees, Engineering (4) and Life sciences
 * (5) topics, Berlin (6) and Potsdam (7) locations. Applied Physics is an engineering
 * bachelor in Berlin, Molecular Chemistry a life sciences master in Potsdam, Mechanical
 * Engineering an engineering master in Berlin. No program carries the Diploma, and the one
 * with the Doctorate is outside the storage of both elements.
 *
 * The finder as imported is stored the way the projects that registered the element
 * themselves stored it: a FlexForm with the target page and nothing else.
 */
final class AcademicProgramsFinderTest extends AbstractAcademicProgramsTestCase
{
    use CategoryFilterFormAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicprograms_programlist';

    private const FINDER_FORM_CLASS = 'academic-programs-finder';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsFinder/records.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param string $constantsFixture A constants fixture below `Fixtures/TypoScript/Constants/`, empty for none.
     * @param string $setup TypoScript added after the setup of the extension, the way a site package adds its own.
     */
    private function setUpSite(string $constantsFixture = '', string $setup = ''): void
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
        if ($setup !== '') {
            $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
            $template = $connection->select(['uid', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
            $this->assertIsArray($template);
            $connection->update('sys_template', ['config' => $template['config'] . LF . $setup], ['uid' => $template['uid']]);
        }
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * Stores the settings of the finder the way FormEngine saves them.
     *
     * @param array<string, string> $fields
     */
    private function setFinderSettings(array $fields): void
    {
        $xml = '';
        foreach ($fields as $field => $value) {
            $xml .= '<field index="' . $field . '"><value index="vDEF">' . htmlspecialchars($value) . '</value></field>';
        }
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                [
                    'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                        . $xml
                        . '</language></sheet></data></T3FlexForms>',
                ],
                ['uid' => 1],
            );
    }

    /**
     * Stores the sorting of the target list element the way FormEngine saves it.
     */
    private function setListSorting(string $sorting): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                [
                    'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                        . '<field index="settings.hideFilter"><value index="vDEF">0</value></field>'
                        . '<field index="settings.hideSorting"><value index="vDEF">0</value></field>'
                        . '<field index="settings.sorting"><value index="vDEF">' . htmlspecialchars($sorting) . '</value></field>'
                        . '<field index="settings.categories"><value index="vDEF">0</value></field>'
                        . '<field index="settings.showHiddenRecords"><value index="vDEF">0</value></field>'
                        . '</language></sheet></data></T3FlexForms>',
                ],
                ['uid' => 2],
            );
    }

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    private function finderXPath(string $content): \DOMXPath
    {
        $document = new \DOMDocument();
        $this->assertTrue(@$document->loadHTML('<?xml encoding="UTF-8">' . $content));

        return new \DOMXPath($document);
    }

    private function finderForm(\DOMXPath $xpath): \DOMElement
    {
        $forms = $xpath->query('//form[contains(concat(" ", normalize-space(@class), " "), " ' . self::FINDER_FORM_CLASS . ' ")]');
        $this->assertInstanceOf(\DOMNodeList::class, $forms);
        $this->assertCount(1, $forms, 'The page renders no single finder form.');
        $form = $forms->item(0);
        $this->assertInstanceOf(\DOMElement::class, $form);

        return $form;
    }

    /**
     * The category selects of the finder by the type in their name, in document order.
     *
     * @return list<string>
     */
    private function renderedFinderTypes(string $content): array
    {
        $xpath = $this->finderXPath($content);
        $types = [];
        foreach ($xpath->query('.//select', $this->finderForm($xpath)) ?: [] as $select) {
            $this->assertInstanceOf(\DOMElement::class, $select);
            $this->assertSame(1, preg_match(
                '/^' . self::LIST_NAMESPACE . '\[demand]\[filterCollection]\[([a-z_]+)]$/',
                $select->getAttribute('name'),
                $matches,
            ), sprintf('The select "%s" is no category filter of the program list.', $select->getAttribute('name')));
            $types[] = $matches[1];
        }

        return $types;
    }

    /**
     * The options of one select of the finder as `value => state`, where the state is
     * `selected`, `disabled` or `enabled`.
     *
     * @return array<string, string>
     */
    private function finderOptions(string $content, string $type): array
    {
        $xpath = $this->finderXPath($content);
        $options = [];
        $query = sprintf('.//select[@name="%s[demand][filterCollection][%s]"]/option', self::LIST_NAMESPACE, $type);
        foreach ($xpath->query($query, $this->finderForm($xpath)) ?: [] as $option) {
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

    /**
     * The action of the finder form: the target page, the action of the list plugin, and a
     * cache hash that covers them. The fields post into the namespace of the list plugin.
     */
    #[Test]
    public function theFinderPostsToTheListPluginOfItsTargetPage(): void
    {
        $this->setUpSite();

        $form = $this->finderForm($this->finderXPath($this->renderHomePage()));

        $this->assertSame('post', strtolower($form->getAttribute('method')));
        $action = $form->getAttribute('action');
        $this->assertSame('/programs', parse_url($action, PHP_URL_PATH));
        parse_str((string)parse_url($action, PHP_URL_QUERY), $query);
        $this->assertSame(['action' => 'list', 'controller' => 'Program'], $query[self::LIST_NAMESPACE] ?? null);
        $this->assertNotSame('', $query['cHash'] ?? '');
    }

    /**
     * An element that names neither its own nor site-wide filter types offers the degree
     * and then the topic.
     */
    #[Test]
    public function withoutFilterTypesTheDegreeAndTheTopicAreOffered(): void
    {
        $this->setUpSite();

        $this->assertSame(['degree', 'topic'], $this->renderedFinderTypes($this->renderHomePage()));
    }

    /**
     * The site-wide filter types of the program list, in their order, instead of the
     * default of the finder.
     */
    #[Test]
    public function anElementWithoutFilterTypesOffersTheSiteWideOnes(): void
    {
        $this->setFinderSettings(['settings.listPid' => '3', 'settings.filter.categoryTypes' => '']);
        $this->setUpSite('SiteWideFilterTypesLocationDegree');

        $this->assertSame(['location', 'degree'], $this->renderedFinderTypes($this->renderHomePage()));
    }

    /**
     * The field of the element wins over the site-wide filter types.
     */
    #[Test]
    public function theChosenTypesAreOfferedInTheChosenOrder(): void
    {
        $this->setFinderSettings(['settings.listPid' => '3', 'settings.filter.categoryTypes' => 'topic,degree']);
        $this->setUpSite('SiteWideFilterTypesLocationDegree');

        $this->assertSame(['topic', 'degree'], $this->renderedFinderTypes($this->renderHomePage()));
    }

    /**
     * The Diploma is on no program, the Doctorate only on a program outside the storage of
     * the finder. Both are offered, disabled - the way the filter of the list offers them.
     */
    #[Test]
    public function anOptionNoProgramInStorageCarriesIsDisabled(): void
    {
        $this->setUpSite();

        $this->assertSame(
            ['' => 'enabled', '1' => 'enabled', '2' => 'enabled', '3' => 'disabled', '8' => 'disabled'],
            $this->finderOptions($this->renderHomePage(), 'degree'),
        );
    }

    /**
     * With options without results hidden, the finder leaves out the Diploma and the
     * Doctorate, the way the filter of the list does.
     */
    #[Test]
    public function anOptionNoProgramInStorageCarriesIsLeftOutOnDemand(): void
    {
        $this->setUpSite('HideDisabledOptions');

        $this->assertSame(
            ['' => 'enabled', '1' => 'enabled', '2' => 'enabled'],
            $this->finderOptions($this->renderHomePage(), 'degree'),
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function labelOverrideDataProvider(): \Generator
    {
        yield 'extension' => ['plugin.tx_academicprograms'];
        yield 'plugin' => ['plugin.tx_academicprograms_programfinder'];
    }

    /**
     * The "All" option of the degree reads a label of its type, as the filter of the list
     * does; the topic has none and keeps the shared one.
     */
    #[DataProvider('labelOverrideDataProvider')]
    #[Test]
    public function theAllOptionReadsALabelOfItsTypeWhenOneExists(string $typoScriptPath): void
    {
        $this->setUpSite(setup: $typoScriptPath . '._LOCAL_LANG.default.sys_category.programs.allOptions.degree = All degrees');

        $content = $this->renderHomePage();

        $this->assertSame('All degrees', $this->categoryFilterOptions($content, self::FINDER_FORM_CLASS, 'degree')[0] ?? null);
        $this->assertSame('All options', $this->categoryFilterOptions($content, self::FINDER_FORM_CLASS, 'topic')[0] ?? null);
    }

    /**
     * The visible count is a setting of the list: the finder, compact by design, shows each of
     * its selects and has no "More filters".
     */
    #[Test]
    public function theFinderShowsEverySelectWhateverTheVisibleCountOfTheList(): void
    {
        $this->setUpSite('VisibleCountOne');

        $this->assertSame(
            ['visible' => ['degree', 'topic'], 'more' => [], 'disclosure' => 'none', 'summary' => null],
            $this->renderedCategoryFilters($this->renderHomePage(), self::FINDER_FORM_CLASS),
        );
    }

    #[Test]
    public function aPreselectedCategoryIsSelected(): void
    {
        $this->setFinderSettings(['settings.listPid' => '3', 'settings.preselectedCategories' => '2,5']);
        $this->setUpSite();

        $content = $this->renderHomePage();

        $this->assertSame(
            ['' => 'enabled', '1' => 'enabled', '2' => 'selected', '3' => 'disabled', '8' => 'disabled'],
            $this->finderOptions($content, 'degree'),
        );
        $this->assertSame(['' => 'enabled', '4' => 'enabled', '5' => 'selected'], $this->finderOptions($content, 'topic'));
    }

    /**
     * A select holds one value. Of two preselected categories of one type the first one
     * stored wins - the category tree of the field stores them in tree order; a category
     * whose type the finder does not offer, and one no program in storage carries,
     * preselect nothing.
     *
     * The list is written directly, in an order the tree of this fixture would not store
     * (Master sorts below Bachelor): it is the stored order that decides, whatever put it
     * there.
     */
    #[Test]
    public function aPreselectionTheFinderCannotShowIsIgnored(): void
    {
        $this->setFinderSettings(['settings.listPid' => '3', 'settings.preselectedCategories' => '3,6,2,1']);
        $this->setUpSite();

        $content = $this->renderHomePage();

        $this->assertSame(
            ['' => 'enabled', '1' => 'enabled', '2' => 'selected', '3' => 'disabled', '8' => 'disabled'],
            $this->finderOptions($content, 'degree'),
        );
        $this->assertSame(['' => 'enabled', '4' => 'enabled', '5' => 'enabled'], $this->finderOptions($content, 'topic'));
    }

    /**
     * The whole way a visitor takes: choose a degree, submit, and land on the target page,
     * whose list shows the matching programs and the choice in its own filter.
     */
    #[Test]
    public function submittingTheFinderOpensTheListFilteredBySelection(): void
    {
        $this->setUpSite();

        $response = $this->submitFrontendForm(
            'https://www.acme.com/home',
            self::FINDER_FORM_CLASS,
            [self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2']]]],
        );
        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame('/programs', parse_url($location, PHP_URL_PATH));
        $content = $this->renderFrontendPage($location);

        $this->assertStringContainsString('Molecular Chemistry', $content);
        $this->assertStringContainsString('Mechanical Engineering', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
        $this->assertStringContainsString('<option value="2" class="level-0" selected="selected">Master of Science</option>', $content);
    }

    /**
     * The finder submits no sorting, so the list keeps the sorting of its element rather
     * than falling back to the default, title ascending.
     */
    #[Test]
    public function submittingTheFinderKeepsTheSortingOfTheList(): void
    {
        $this->setListSorting('title desc');
        $this->setUpSite();

        $response = $this->submitFrontendForm(
            'https://www.acme.com/home',
            self::FINDER_FORM_CLASS,
            [self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2']]]],
        );
        $location = $this->assertSeeOtherWithCacheHash($response);
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('title', $query[self::LIST_NAMESPACE]['demand']['sortingField'] ?? null);
        $this->assertSame('desc', $query[self::LIST_NAMESPACE]['demand']['sortingDirection'] ?? null);

        $content = $this->renderFrontendPage($location);
        $molecular = strpos($content, 'Molecular Chemistry');
        $mechanical = strpos($content, 'Mechanical Engineering');
        $this->assertIsInt($molecular);
        $this->assertIsInt($mechanical);
        $this->assertLessThan($mechanical, $molecular);
    }

    /**
     * The same way with a preselected category the visitor keeps: the preselection is a
     * selected option, so the browser submits it like any other choice.
     */
    #[Test]
    public function submittingThePreselectionAsItIsFiltersByIt(): void
    {
        $this->setFinderSettings(['settings.listPid' => '3', 'settings.preselectedCategories' => '1']);
        $this->setUpSite();

        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FINDER_FORM_CLASS);
        $content = $this->renderFrontendPage($this->assertSeeOtherWithCacheHash($response));

        $this->assertStringContainsString('Applied Physics', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Mechanical Engineering', $content);
    }

    /**
     * The argument shape the finder relies on, sent without the finder: a category uid per
     * type below `demand[filterCollection]` of the list plugin, one filter per type and all
     * of them applied together.
     */
    #[Test]
    public function theListAcceptsOneCategoryPerTypeInItsFilterCollection(): void
    {
        $this->setUpSite();

        $response = $this->requestFrontendPage($this->frontendPostRequest(
            'https://www.acme.com/programs',
            [self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['degree' => '2', 'topic' => '4', 'location' => '']]]],
        ));
        $content = $this->renderFrontendPage($this->assertSeeOtherWithCacheHash($response));

        $this->assertStringContainsString('Mechanical Engineering', $content);
        $this->assertStringNotContainsString('Molecular Chemistry', $content);
        $this->assertStringNotContainsString('Applied Physics', $content);
    }

    /**
     * Each select is labelled with the title of its type, and the button says what it does.
     */
    #[Test]
    public function everySelectHasALabelAndTheFormAButton(): void
    {
        $this->setUpSite();

        $xpath = $this->finderXPath($this->renderHomePage());
        $form = $this->finderForm($xpath);
        foreach (['degree' => 'Degree', 'topic' => 'Topic'] as $type => $title) {
            $selects = $xpath->query(sprintf('.//select[@name="%s[demand][filterCollection][%s]"]', self::LIST_NAMESPACE, $type), $form);
            $this->assertInstanceOf(\DOMNodeList::class, $selects);
            $select = $selects->item(0);
            $this->assertInstanceOf(\DOMElement::class, $select);
            $this->assertSame('academic-programs-finder-' . $type . '-1', $select->getAttribute('id'));
            $labels = $xpath->query(sprintf('.//label[@for="%s"]', $select->getAttribute('id')), $form);
            $this->assertInstanceOf(\DOMNodeList::class, $labels);
            $this->assertCount(1, $labels);
            $this->assertSame($title, trim((string)$labels->item(0)?->textContent));
        }
        $buttons = $xpath->query('.//button[@type="submit"]', $form);
        $this->assertInstanceOf(\DOMNodeList::class, $buttons);
        $this->assertCount(1, $buttons);
        $this->assertSame('Show programs', trim((string)$buttons->item(0)?->textContent));
    }

    /**
     * FormEngine requires the target page, a record written some other way may lack it.
     * Without a target the finder has nowhere to send the visitor, so it renders no form
     * rather than one that submits to the page it is on.
     */
    #[Test]
    public function withoutATargetPageNoFormIsRendered(): void
    {
        $this->setFinderSettings(['settings.listPid' => '']);
        $this->setUpSite();

        $content = $this->renderHomePage();

        $this->assertStringContainsString('Home (EN)', $content);
        $this->assertStringNotContainsString(self::FINDER_FORM_CLASS, $content);
    }

    /**
     * A target page that cannot be linked - here hidden after the finder was saved - gives
     * no URI. A form would then post to the page it is on, where nothing answers it.
     */
    #[Test]
    public function withATargetPageThatCannotBeLinkedNoFormIsRendered(): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update('pages', ['hidden' => 1], ['uid' => 3]);
        $this->setUpSite();

        $content = $this->renderHomePage();

        $this->assertStringContainsString('Home (EN)', $content);
        $this->assertStringNotContainsString(self::FINDER_FORM_CLASS, $content);
    }

    /**
     * Without a category type that has categories there is nothing to select.
     */
    #[Test]
    public function withoutAnOfferedTypeNoFormIsRendered(): void
    {
        $this->setFinderSettings(['settings.listPid' => '3', 'settings.filter.categoryTypes' => 'teaching_language']);
        $this->setUpSite();

        $content = $this->renderHomePage();

        $this->assertStringContainsString('Home (EN)', $content);
        $this->assertStringNotContainsString(self::FINDER_FORM_CLASS, $content);
    }
}
