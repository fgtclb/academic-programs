<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Plugins;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;

/**
 * The program finder renders outside of the cached page. Which of its options are disabled
 * depends on program pages elsewhere in the tree, and no cache tag ties the finder's page
 * to them, so a cached finder would keep offering what changed there.
 *
 * A class of its own, because the testing framework replaces the page cache by a
 * NullBackend, and only a real page cache can show the difference. The fixture is the one
 * of {@see AcademicProgramsFinderTest}.
 */
final class AcademicProgramsFinderCachingTest extends AbstractAcademicProgramsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        'pages' => [
                            'backend' => Typo3DatabaseBackend::class,
                        ],
                    ],
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProgramsFinder/records.csv');
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

    /**
     * The Diploma is on no program at first. Once a program in storage carries it, the next
     * request offers it - from a page that is cached by then, without a cache flush.
     */
    #[Test]
    public function theOptionsFollowTheProgramsFromACachedPage(): void
    {
        $this->assertStringContainsString(
            '<option value="3" class="level-0" disabled="disabled">Diploma</option>',
            $this->renderFrontendPage('https://www.acme.com/home'),
        );
        $this->assertSame(
            1,
            $this->getConnectionPool()->getConnectionForTable('cache_pages')->count('*', 'cache_pages', []),
            'The page of the finder was not cached, so this test proves nothing.',
        );

        $this->getConnectionPool()->getConnectionForTable('sys_category_record_mm')->insert(
            'sys_category_record_mm',
            ['uid_local' => 3, 'uid_foreign' => 10, 'tablenames' => 'pages', 'fieldname' => 'categories', 'sorting' => 0, 'sorting_foreign' => 4],
        );

        $this->assertStringContainsString(
            '<option value="3" class="level-0">Diploma</option>',
            $this->renderFrontendPage('https://www.acme.com/home'),
        );
    }
}
