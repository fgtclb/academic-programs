<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\SiteSet;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Error\Http\InternalServerErrorException;
use TYPO3\CMS\Core\Site\Set\SetDefinition;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Proves that the site sets of this extension deliver what their `config.yaml` claims.
 *
 * Both keys of a set are strings that the core resolves at runtime, and both fail
 * silently when they are wrong: `SysTemplateTreeBuilder::handleSetInclude()` and
 * `TsConfigTreeBuilder::getSitePageTsConfigTree()` `file_exists()`-guard the files they
 * read and simply continue when one is missing. A typo in `typoscript:` or in `pagets:`
 * therefore produces no error anywhere, only a site that is configured differently than
 * the integrator expects - which is the whole reason this restructuring exists.
 *
 * This extension adds two failure modes the reference implementation does not have. Its
 * content elements share one `plugin.tx_academicprograms` block, so a component
 * folder holds nothing but an `include_static_file.txt` naming the shared folder; that
 * file is comma separated and is read by the very same code path for a set as for a
 * `sys_template` record, so a component set that delivers nothing at all is a plausible
 * outcome of getting it wrong - and an invisible one. And the `styles.content` override
 * this extension assigned unconditionally up to 2.3, and through a set of its own after
 * that, is gone: no set and no static template may deliver it any more.
 *
 * The `sys_template` record the probe is imported from carries `clear = 0` on purpose:
 * the backend button "Create a root TypoScript record" writes `clear = 3`, which discards
 * everything the site sets contributed, and so does
 * `FunctionalTestCase::setUpFrontendRootPage()`.
 */
final class SiteSetDeliveryTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const AGGREGATE_SET = 'fgtclb/academic-programs';
    /**
     * The set that delivered the `styles.content` override up to 2.x. It is removed.
     */
    private const CONTENT_LOAD_SET = 'fgtclb/academic-programs-content-load';

    /**
     * The constant the probe renders, assigned by
     * `Configuration/TypoScript/constants.typoscript` and by nothing else.
     */
    private const SHARED_CONSTANT = '<div id="constant">EXT:academic_programs/Resources/Private/Partials/</div>';

    /**
     * A value the probe copies out of the setup of the shared block, assigned by
     * `Configuration/TypoScript/setup.typoscript`.
     */
    private const SHARED_SETUP = '<div id="setup">EXT:academic_programs/Resources/Private/Templates/</div>';

    /**
     * What the removed "content load" component assigned.
     */
    private const CONTENT_LOAD = '<div id="contentLoad">{#colPos}=0</div>';

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function componentDataProvider(): \Generator
    {
        yield 'program list' => [
            'fgtclb/academic-programs-program-list',
            'academicprograms_programlist',
            'EXT:academic_programs/Configuration/TypoScript/ProgramList/',
            'EXT:academic_programs/Configuration/TSconfig/ProgramList/page.tsconfig',
        ];
        yield 'program details' => [
            'fgtclb/academic-programs-program-details',
            'academicprograms_programdetails',
            'EXT:academic_programs/Configuration/TypoScript/ProgramDetails/',
            'EXT:academic_programs/Configuration/TSconfig/ProgramDetails/page.tsconfig',
        ];
        yield 'program finder' => [
            'fgtclb/academic-programs-program-finder',
            'academicprograms_programfinder',
            'EXT:academic_programs/Configuration/TypoScript/ProgramFinder/',
            'EXT:academic_programs/Configuration/TSconfig/ProgramFinder/page.tsconfig',
        ];
    }

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function siteSetDeliversTheSharedTypoScript(): void
    {
        $this->setUpSite(dependencies: [self::AGGREGATE_SET]);

        $body = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);

        $this->assertStringContainsString(
            self::SHARED_CONSTANT,
            $body,
            'The site set did not deliver "constants.typoscript" of the shared block.',
        );
        $this->assertStringContainsString(
            self::SHARED_SETUP,
            $body,
            'The site set did not deliver "setup.typoscript" of the shared block.',
        );
    }

    /**
     * The point of the layout here: a component folder holds nothing but an
     * `include_static_file.txt` naming the shared folder, and that is what has to arrive
     * when a site depends on a single component. It is the file this extension would
     * lose its whole plugin configuration to, because an `include_static_file.txt` that
     * does not resolve includes nothing and says nothing.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentSetDeliversTheSharedTypoScriptThroughItsIncludeStaticFile(string $set): void
    {
        $this->setUpSite(dependencies: [$set]);

        $body = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);

        $this->assertStringContainsString(
            self::SHARED_CONSTANT,
            $body,
            sprintf('The set "%s" did not deliver the constants of the shared block.', $set),
        );
        $this->assertStringContainsString(
            self::SHARED_SETUP,
            $body,
            sprintf('The set "%s" did not deliver the setup of the shared block.', $set),
        );
    }

    /**
     * The counterpart of the two tests above for an installation without site sets. It
     * also covers `Configuration/TypoScript/Full/include_static_file.txt`, whose entries
     * are comma separated and reach nothing at all when they are written any other way.
     */
    #[Test]
    public function aggregateStaticTemplateDeliversTheSharedTypoScript(): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_programs/Configuration/TypoScript/Full');

        $body = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);

        $this->assertStringContainsString(
            self::SHARED_CONSTANT,
            $body,
            'The aggregate static template did not deliver the constants of the shared block.',
        );
        $this->assertStringContainsString(
            self::SHARED_SETUP,
            $body,
            'The aggregate static template did not deliver the setup of the shared block.',
        );
    }

    /**
     * The value installations stored before the configuration was cut per component. It
     * is the shared block itself, and it has to keep delivering it.
     */
    #[Test]
    public function sharedStaticTemplateStillDeliversTheSharedTypoScript(): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_programs/Configuration/TypoScript/');

        $body = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);

        $this->assertStringContainsString(
            self::SHARED_CONSTANT,
            $body,
            'The static template installations already store did not deliver the constants of the shared block.',
        );
        $this->assertStringContainsString(
            self::SHARED_SETUP,
            $body,
            'The static template installations already store did not deliver the setup of the shared block.',
        );
    }

    /**
     * The `styles.content.getContent` override changed how a site renders pages that have
     * nothing to do with this extension, and the program page type no longer needs it. No
     * delivery mechanism of this extension may assign it.
     *
     * The aggregate set and the aggregate static template assigned it before, so those two
     * rows fail on the unchanged code. The component set and the shared static template
     * never did - they are guards.
     *
     * @return \Generator<string, array{0: list<string>, 1: string}>
     */
    public static function contentLoadDataProvider(): \Generator
    {
        yield 'aggregate set' => [[self::AGGREGATE_SET], ''];
        yield 'component set alone' => [['fgtclb/academic-programs-program-list'], ''];
        yield 'aggregate static template' => [[], 'EXT:academic_programs/Configuration/TypoScript/Full'];
        yield 'shared static template' => [[], 'EXT:academic_programs/Configuration/TypoScript/'];
    }

    /**
     * @param list<string> $dependencies
     */
    #[Test]
    #[DataProvider('contentLoadDataProvider')]
    public function contentLoadOverrideIsNotDelivered(array $dependencies, string $includeStaticFile): void
    {
        $this->setUpSite(dependencies: $dependencies, includeStaticFile: $includeStaticFile);

        $body = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);

        // The probe renders, so the site is delivered: an empty value is the result, not
        // a page that never got that far.
        $this->assertStringContainsString('<div id="contentLoad"></div>', $body);
        $this->assertStringNotContainsString(
            self::CONTENT_LOAD,
            $body,
            'The "styles.content.getContent" override was delivered.',
        );
    }

    /**
     * A site that still names the removed set is not delivered at all: core marks the name
     * as an unavailable set (`SiteConfiguration::determineInvalidSets()`) and `SiteResolver`
     * answers every request of the site through the error controller - HTTP 500 in an
     * installation, the exception here. The Breaking changelog entry and the
     * `unavailable-set` finding of `academic:upgrade:check` both rest on this.
     */
    #[Test]
    public function siteNamingTheRemovedSetIsNotDelivered(): void
    {
        $this->setUpSite(dependencies: [self::CONTENT_LOAD_SET]);

        $this->expectException(InternalServerErrorException::class);
        $this->expectExceptionMessage('depends on unavailable sets: ' . self::CONTENT_LOAD_SET);

        $this->requestFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);
    }

    #[Test]
    public function contentLoadSetIsNotRegistered(): void
    {
        $this->assertFalse(
            $this->setRegistry()->hasSet(self::CONTENT_LOAD_SET),
            sprintf('The removed set "%s" is registered.', self::CONTENT_LOAD_SET),
        );
    }

    /**
     * The other half of the delivery: the content elements are hidden for the whole
     * installation, and naming a set in the site configuration is one of the two ways to
     * bring one back. No page carries a `tsconfig_includes` entry here, so the set is the
     * only thing that can do it.
     */
    #[Test]
    public function siteSetDeliversThePageTsConfigOfEveryComponent(): void
    {
        $this->setUpSite(dependencies: [self::AGGREGATE_SET]);

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);
        $removeItems = $this->removedContentElementTypes($pageTsConfig);
        $wizardElements = $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [];

        foreach (self::componentDataProvider() as $component) {
            $this->assertNotContains(
                $component[1],
                $removeItems,
                sprintf('The aggregate set did not re-enable the content element "%s".', $component[1]),
            );
            $this->assertArrayHasKey(
                $component[1] . '.',
                $wizardElements,
                sprintf('The aggregate set did not deliver the wizard entry of "%s".', $component[1]),
            );
        }
    }

    /**
     * The hide half, asserted on its own. Without it the re-enable assertion above cannot
     * fail: it checks that a content element is absent from `removeItems`, and an empty
     * list satisfies that just as well as a correct one.
     */
    #[Test]
    public function everyContentElementIsHiddenWithoutASiteSet(): void
    {
        $this->setUpSite();

        $removeItems = $this->removedContentElementTypes(BackendUtility::getPagesTSconfig(1));

        foreach (self::componentDataProvider() as $component) {
            $this->assertContains(
                $component[1],
                $removeItems,
                sprintf('The content element "%s" is selectable although no set and no page TSconfig enable it.', $component[1]),
            );
        }
    }

    /**
     * A component set re-enables its own content element and nothing else. Without this
     * the whole per-component split is decoration: one page TSconfig file that re-enabled
     * all of them would pass every other assertion here.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentSetReEnablesItsOwnContentElementOnly(string $set, string $contentElementType): void
    {
        $this->setUpSite(dependencies: [$set]);

        $removeItems = $this->removedContentElementTypes(BackendUtility::getPagesTSconfig(1));

        $this->assertNotContains(
            $contentElementType,
            $removeItems,
            sprintf('The set "%s" did not re-enable "%s".', $set, $contentElementType),
        );
        foreach (self::componentDataProvider() as $component) {
            if ($component[1] === $contentElementType) {
                continue;
            }
            $this->assertContains(
                $component[1],
                $removeItems,
                sprintf('The set "%s" also re-enabled "%s".', $set, $component[1]),
            );
        }
    }

    /**
     * Pins the two strings the tests above depend on, and the files they point at.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentSetPointsAtTheFilesTheStaticRegistrationUses(
        string $set,
        string $contentElementType,
        string $typoScriptPath,
        string $pageTsConfigPath,
    ): void {
        $component = $this->setRegistry()->getSet($set);

        $this->assertNotNull($component, sprintf('The set "%s" is not registered.', $set));
        $this->assertSame($typoScriptPath, $component->typoscript);
        $this->assertSame($pageTsConfigPath, $component->pagets);
        $this->assertDirectoryExists(GeneralUtility::getFileAbsFileName((string)$component->typoscript));
        $this->assertFileExists(GeneralUtility::getFileAbsFileName((string)$component->pagets));
    }

    /**
     * The aggregate carries no payload of its own on purpose: it delivers through the
     * component sets, and a `typoscript:` of its own would parse the same files twice.
     * The name is the one this extension published before the split, and both development
     * instances of this repository depend on it by that exact string. A set that is not
     * found is anything but silent: TYPO3 answers every page of such a site with HTTP 500
     * ("depends on unavailable sets").
     */
    #[Test]
    public function aggregateSetDependsOnEveryComponentAndCarriesNoPayload(): void
    {
        $aggregate = $this->setRegistry()->getSet(self::AGGREGATE_SET);

        $this->assertNotNull($aggregate, sprintf('The set "%s" is not registered.', self::AGGREGATE_SET));
        foreach (self::componentDataProvider() as $component) {
            $this->assertContains($component[0], $aggregate->dependencies);
        }
        $this->assertNotContains(
            self::CONTENT_LOAD_SET,
            $aggregate->dependencies,
            'The aggregate depends on the removed "styles.content" override.',
        );
        $this->assertSetCarriesNoPayload($aggregate);
    }

    /**
     * The settings belong to the page type and to every content element, so they are
     * declared once, with the aggregate. The defaults asserted here are the values
     * `constants.typoscript` assigns for the same paths - kept equal by hand, so that a
     * site using both delivery mechanisms does not have its configuration reset by the
     * second parse; this test reads the declarations only.
     */
    #[Test]
    public function settingsAreDeclaredWithTheAggregateSetOnly(): void
    {
        $aggregate = $this->setRegistry()->getSet(self::AGGREGATE_SET);
        $this->assertNotNull($aggregate);

        $definitions = [];
        foreach ($aggregate->settingsDefinitions as $definition) {
            $definitions[$definition->key] = $definition->default;
        }

        $this->assertSame(
            [
                'plugin.tx_academicprograms.page.layout' => 'Default',
                'plugin.tx_academicprograms.page.listPid' => 0,
                'plugin.tx_academicprograms.facts.fields' => '',
                'plugin.tx_academicprograms.card.fields' => 'degree',
                'plugin.tx_academicprograms.filter.categoryTypes' => '',
            ],
            $definitions,
        );

        foreach (self::componentDataProvider() as $component) {
            $set = $this->setRegistry()->getSet($component[0]);
            $this->assertNotNull($set);
            $this->assertSame(
                [],
                $set->settingsDefinitions,
                sprintf('The set "%s" declares settings of its own.', $component[0]),
            );
        }
    }

    private function setRegistry(): SetRegistry
    {
        $setRegistry = $this->get(SetRegistry::class);
        $this->assertInstanceOf(SetRegistry::class, $setRegistry);

        return $setRegistry;
    }

    /**
     * @param array<string, mixed> $pageTsConfig
     * @return list<string>
     */
    private function removedContentElementTypes(array $pageTsConfig): array
    {
        return GeneralUtility::trimExplode(
            ',',
            (string)($pageTsConfig['TCEFORM.']['tt_content.']['CType.']['removeItems'] ?? ''),
            true,
        );
    }

    /**
     * A set that declares neither key does not get `null`: the core defaults both to the
     * set folder itself (`YamlSetDefinitionProvider::createDefinition()`), and reads
     * whatever it finds there. "Carries no payload" therefore means the set folder holds
     * none of the four files the two mechanisms look for.
     */
    private function assertSetCarriesNoPayload(SetDefinition $set): void
    {
        $typoScriptPath = rtrim(GeneralUtility::getFileAbsFileName((string)$set->typoscript), '/') . '/';
        foreach (['constants.typoscript', 'setup.typoscript', 'include_static_file.txt'] as $fileName) {
            $this->assertFileDoesNotExist(
                $typoScriptPath . $fileName,
                sprintf('The set "%s" carries a payload of its own: %s', $set->name, $fileName),
            );
        }
        $this->assertFileDoesNotExist(
            GeneralUtility::getFileAbsFileName((string)$set->pagets),
            sprintf('The set "%s" carries a page TSconfig of its own.', $set->name),
        );
    }

    /**
     * The site identifier is derived from what the site is configured with, and that is
     * not cosmetic. `TsConfigTreeBuilder::getSitePageTsConfigTree()` caches the page
     * TSconfig a site's sets deliver under the site identifier alone, and the test
     * instance keeps that cache for the whole class. Reusing one identifier for
     * differently configured sites therefore answers the second test with the result of
     * the first - which looks exactly like a set that delivers too much.
     *
     * @param list<string> $dependencies Site sets the site configuration names.
     * @param string $includeStaticFile Static template the `sys_template` record selects.
     */
    private function setUpSite(array $dependencies = [], string $includeStaticFile = ''): void
    {
        $identifier = 'acme-' . substr(md5(implode(',', $dependencies) . '|' . $includeStaticFile), 0, 10);

        $this->importCSVDataSet(__DIR__ . '/Fixtures/SiteSetDelivery/pages.csv');
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                // Not "3": a clear flag discards everything the site sets contributed.
                'clear' => 0,
                'title' => 'Probe',
                'constants' => '',
                'config' => '@import \'EXT:academic_programs/Tests/Functional/SiteSet/Fixtures/TypoScript/Probe.typoscript\'',
                'include_static_file' => $includeStaticFile,
            ],
        );
        $this->writeSiteConfiguration(
            identifier: $identifier,
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: $dependencies === [] ? [] : ['dependencies' => $dependencies],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }
}
