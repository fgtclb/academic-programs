<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\SiteSet;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Draws the line the site set conversion must not cross.
 *
 * Cutting the configuration of this extension per component made its content elements
 * opt-in: they are hidden for the whole installation and a component set brings one back.
 * The page type this extension registers and the backend layout that belongs to it are
 * the opposite case and must stay installation wide, because both are values persisted on
 * :sql:`pages` records. A page carrying doktype 20 or `pagets__AcademicProgram` exists
 * before any site configuration is read, and it has to open and render on a site that
 * names no set of this extension at all.
 *
 * This is not hypothetical for this extension family: two of the three already shipped
 * that regression once and documented the fix, because the backend layout used to be
 * imported from the set only and resolved to "[ MISSING LABEL ]" everywhere else.
 */
final class InstallationWideRegistrationTest extends AbstractAcademicProgramsTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    /**
     * A written site configuration outlives the test instance, so it has to be removed
     * explicitly - otherwise the next test finds a site it did not write.
     */
    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * The page type is registered in TCA, which no site configuration can switch off -
     * and no site configuration may be made to switch on. The icon is asserted with it
     * because a doktype without one renders the page tree with the default page icon and
     * is the kind of loss nobody reports.
     */
    #[Test]
    public function pageTypeIsRegisteredWithoutASiteSet(): void
    {
        $values = array_map(
            static fn(array $item): string => (string)($item['value'] ?? ''),
            $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] ?? [],
        );

        $this->assertContains('20', $values, 'The page type of this extension is not selectable.');
        $this->assertSame(
            'academic-programs',
            $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][20] ?? null,
            'The page type of this extension has no icon.',
        );
        $this->assertArrayHasKey(
            20,
            $GLOBALS['TCA']['pages']['types'] ?? [],
            'The page type of this extension has no form definition.',
        );
    }

    /**
     * A site exists here and it declares no dependencies at all. That is what separates
     * this from the assertion that the layout is defined somewhere: a backend layout
     * moved into a component set still resolves for a site that enables the set, and
     * fails only for the sites nobody tests.
     */
    #[Test]
    public function backendLayoutIsAvailableOnASiteThatDeclaresNoSet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InstallationWideRegistration/pages.csv');
        $this->writeSiteConfiguration(
            identifier: 'acme-without-sets',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: 'https://www.acme.com/'),
            languages: [$this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/')],
        );

        $backendLayouts = BackendUtility::getPagesTSconfig(1)['mod.']['web_layout.']['BackendLayouts.'] ?? [];

        $this->assertArrayHasKey(
            'AcademicProgram.',
            $backendLayouts,
            'The backend layout of the page type is not available on a site without any set.',
        );
    }

    /**
     * The static guard for the same thing, and the cheaper one to read: none of the page
     * TSconfig files a set of this extension delivers may define the backend layout or
     * import the folder it lives in. Moving that import into a component is the exact
     * regression this test class exists for, and it would pass the two tests above only
     * as long as the moved copy is still reachable.
     */
    #[Test]
    public function noSetDeliveredPageTsConfigCarriesTheBackendLayout(): void
    {
        $files = glob(dirname(__DIR__, 3) . '/Configuration/TSconfig/*/page.tsconfig');
        $this->assertNotEmpty($files, 'No component page TSconfig file was found.');

        foreach ((array)$files as $file) {
            $contents = (string)file_get_contents((string)$file);
            $this->assertStringNotContainsString(
                'BackendLayouts',
                $contents,
                sprintf('"%s" delivers the backend layout, which has to stay installation wide.', (string)$file),
            );
        }
    }
}
