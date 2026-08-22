<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\SiteSet;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The same contract as `Tests/Functional/Core13/SiteSet/SiteSetDeliveryTest.php`, through
 * the mechanism that exists on both TYPO3 versions this branch supports.
 *
 * Site sets arrived in TYPO3 v13.1, so on TYPO3 v12 a `sys_template` record and the page
 * field :guilabel:`Page TSconfig` are the only way anything this extension ships reaches a
 * site. That makes this the class that matters on the older version, and it asserts what
 * the set based one asserts: the shared `plugin.tx_academicprograms` block arrives through every
 * component, the `styles.content` override arrives through its own component and through
 * nothing else, and a content element stays hidden until its component is included.
 *
 * Both halves fail silently when they are wrong, which is why they are tested at all. An
 * `include_static_file.txt` is comma separated (`SysTemplateTreeBuilder`, identical on
 * both versions) and an entry that does not resolve includes nothing without a word of
 * warning; an unresolvable `pages.tsconfig_includes` entry is equally quiet.
 *
 * The `sys_template` record is written by hand with `clear = 0`.
 * `FunctionalTestCase::setUpFrontendRootPage()` hardcodes `clear = 3`, which discards
 * everything included before the record - on v13 that is the site set contribution, and it
 * would make the two classes assert different things.
 */
final class StaticTemplateDeliveryTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

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
     * What the "content load" component assigns, and the only thing that assigns it.
     */
    private const CONTENT_LOAD = '<div id="contentLoad">{#colPos}=0</div>';

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function componentDataProvider(): \Generator
    {
        yield 'program list' => [
            'ProgramList',
            'academicprograms_programlist',
            'EXT:academic_programs/Configuration/TSconfig/ProgramList/page.tsconfig',
        ];
        yield 'program details' => [
            'ProgramDetails',
            'academicprograms_programdetails',
            'EXT:academic_programs/Configuration/TSconfig/ProgramDetails/page.tsconfig',
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

    /**
     * A component folder holds nothing but an `include_static_file.txt` naming the shared
     * folder, so this is the assertion that the one line in it is written the way the core
     * reads it. Written any other way the static template contributes nothing at all, and
     * says nothing about it.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function componentStaticTemplateDeliversTheSharedTypoScript(string $component): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_programs/Configuration/TypoScript/' . $component);

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

        $this->assertStringContainsString(
            self::SHARED_CONSTANT,
            $body,
            sprintf('The static template "%s" did not deliver the constants of the shared block.', $component),
        );
        $this->assertStringContainsString(
            self::SHARED_SETUP,
            $body,
            sprintf('The static template "%s" did not deliver the setup of the shared block.', $component),
        );
    }

    /**
     * The aggregate lists every component in its own `include_static_file.txt`, so it
     * covers the comma separated multi entry form the component files do not exercise.
     */
    #[Test]
    public function aggregateStaticTemplateDeliversTheSharedTypoScript(): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_programs/Configuration/TypoScript/Full');

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

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
     * The value installations stored before the configuration was cut per component. It is
     * the shared block itself, and it has to keep delivering it.
     */
    #[Test]
    public function sharedStaticTemplateStillDeliversTheSharedTypoScript(): void
    {
        $this->setUpSite(includeStaticFile: 'EXT:academic_programs/Configuration/TypoScript/');

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

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
     * The `styles.content.getContent` override redefines a global TypoScript object path
     * for every page of the site, so it is a component of its own and has to arrive with
     * that component and with the aggregate - and with nothing else. The entry an
     * installation already stores is the interesting row: it used to carry the override
     * and must not carry it any more.
     *
     * @return \Generator<string, array{0: string, 1: bool}>
     */
    public static function contentLoadDataProvider(): \Generator
    {
        yield 'aggregate static template' => ['EXT:academic_programs/Configuration/TypoScript/Full', true];
        yield 'content load static template' => ['EXT:academic_programs/Configuration/TypoScript/ContentLoad', true];
        yield 'component static template alone' => ['EXT:academic_programs/Configuration/TypoScript/ProgramList', false];
        yield 'stored shared static template' => ['EXT:academic_programs/Configuration/TypoScript/', false];
        yield 'nothing included' => ['', false];
    }

    #[Test]
    #[DataProvider('contentLoadDataProvider')]
    public function contentLoadOverrideIsDeliveredByItsOwnStaticTemplateOnly(string $includeStaticFile, bool $expected): void
    {
        $this->setUpSite(includeStaticFile: $includeStaticFile);

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

        if ($expected) {
            $this->assertStringContainsString(
                self::CONTENT_LOAD,
                $body,
                'The "styles.content.getContent" override was not delivered.',
            );

            return;
        }
        $this->assertStringNotContainsString(
            self::CONTENT_LOAD,
            $body,
            'The "styles.content.getContent" override was delivered although nothing asked for it.',
        );
    }

    /**
     * The hide half, asserted on its own. Without it the re-enable assertions below cannot
     * fail: they check that a content element is absent from `removeItems`, and an empty
     * list satisfies that just as well as a correct one.
     */
    #[Test]
    public function everyContentElementIsHiddenWithoutAPageTsConfigInclude(): void
    {
        $this->setUpSite();

        $removeItems = $this->removedContentElementTypes(BackendUtility::getPagesTSconfig(1));

        foreach (self::componentDataProvider() as $component) {
            $this->assertContains(
                $component[1],
                $removeItems,
                sprintf('The content element "%s" is selectable although no page TSconfig enables it.', $component[1]),
            );
        }
    }

    /**
     * A page TSconfig include re-enables its own content element and nothing else. Without
     * this the whole per-component split is decoration: one file that re-enabled every
     * element would pass every other assertion here.
     *
     * The wizard entry is asserted with it, and so is the `show` list. TYPO3 v12 gates
     * every wizard element on that list (`NewContentElementController::getWizards()`),
     * TYPO3 v13 ignores it, and it is the one part of this configuration that the newer
     * version cannot notice missing.
     */
    #[Test]
    #[DataProvider('componentDataProvider')]
    public function pageTsConfigIncludeReEnablesItsOwnContentElementOnly(
        string $component,
        string $contentElementType,
        string $pageTsConfigPath,
    ): void {
        $this->setUpSite(tsConfigIncludes: $pageTsConfigPath);

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);
        $removeItems = $this->removedContentElementTypes($pageTsConfig);
        $wizard = $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.'] ?? [];

        $this->assertNotContains(
            $contentElementType,
            $removeItems,
            sprintf('The page TSconfig of "%s" did not re-enable "%s".', $component, $contentElementType),
        );
        $this->assertArrayHasKey(
            $contentElementType . '.',
            $wizard['elements.'] ?? [],
            sprintf('The page TSconfig of "%s" did not deliver the wizard entry of "%s".', $component, $contentElementType),
        );
        $this->assertContains(
            $contentElementType,
            GeneralUtility::trimExplode(',', (string)($wizard['show'] ?? ''), true),
            sprintf('The page TSconfig of "%s" did not add "%s" to the wizard "show" list.', $component, $contentElementType),
        );

        foreach (self::componentDataProvider() as $other) {
            if ($other[1] === $contentElementType) {
                continue;
            }
            $this->assertContains(
                $other[1],
                $removeItems,
                sprintf('The page TSconfig of "%s" also re-enabled "%s".', $component, $other[1]),
            );
        }
    }

    /**
     * The aggregate page TSconfig is what an installation without site sets selects, and
     * it is a plain list of `@import` lines - a form that reports nothing when one of them
     * does not resolve.
     */
    #[Test]
    public function aggregatePageTsConfigIncludeReEnablesEveryContentElement(): void
    {
        $this->setUpSite(tsConfigIncludes: 'EXT:academic_programs/Configuration/TSconfig/Full/page.tsconfig');

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);
        $removeItems = $this->removedContentElementTypes($pageTsConfig);
        $showItems = GeneralUtility::trimExplode(
            ',',
            (string)($pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['show'] ?? ''),
            true,
        );

        foreach (self::componentDataProvider() as $component) {
            $this->assertNotContains(
                $component[1],
                $removeItems,
                sprintf('The aggregate page TSconfig did not re-enable the content element "%s".', $component[1]),
            );
            $this->assertContains(
                $component[1],
                $showItems,
                sprintf('The aggregate page TSconfig did not add "%s" to the wizard "show" list.', $component[1]),
            );
        }
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
     * The site identifier is derived from what the site is configured with, for the same
     * reason the set based class derives it: page TSconfig is cached per site for the whole
     * test instance, so reusing one identifier answers the second test with the result of
     * the first.
     *
     * @param string $includeStaticFile Static template the `sys_template` record selects.
     * @param string $tsConfigIncludes Page TSconfig file the site root page selects.
     */
    private function setUpSite(string $includeStaticFile = '', string $tsConfigIncludes = ''): void
    {
        $identifier = 'acme-' . substr(md5($includeStaticFile . '|' . $tsConfigIncludes), 0, 10);

        $this->importCSVDataSet(__DIR__ . '/Fixtures/SiteSetDelivery/pages.csv');
        if ($tsConfigIncludes !== '') {
            $this->getConnectionPool()->getConnectionForTable('pages')->update(
                'pages',
                ['tsconfig_includes' => $tsConfigIncludes],
                ['uid' => 1],
            );
        }
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                // Not "3": a clear flag discards everything included before this record.
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
                base: $this->frontendPluginTestBase(),
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }
}
