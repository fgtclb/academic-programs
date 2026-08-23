<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Tca;

use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pins how the page type of this extension reaches the PageDoktypeRegistry, and that
 * registering it does not damage the allow list of the standard page type (ACE-462).
 *
 * The order of the two test methods is load-bearing, because the functional test case
 * builds the TCA cold for its first test only and reuses the cache for every following
 * one - which is precisely the difference the registration used to be sensitive to:
 *
 * - contentElementsAreAllowedOnAStandardPageWithAColdTcaCache()
 *   runs against a cold TCA cache, the state in which "Configuration/TCA/Overrides"
 *   files are executed. Registering the page type from there latched an allow list of
 *   the "default" page type that was collected before the TCA was loaded, so "tt_content"
 *   was missing from it and the DataHandler refused content elements on every standard
 *   page.
 * - everyRecordTypeIsAllowedOnTheRegisteredPageTypeWithAWarmTcaCache()
 *   runs against a warm TCA cache, where the override files are not executed at all. A
 *   page type registered from there is then simply absent and silently falls back to the
 *   allow list of the "default" page type.
 *
 * Both are answered by doing the registration from a BootCompletedEvent listener, which
 * runs after the TCA is loaded and on every request.
 */
final class PageDoktypeRegistrationTest extends AbstractAcademicProgramsTestCase
{
    /**
     * First test of this test case, so the TCA is built cold and the TCA override files
     * of the extension are executed.
     */
    #[Test]
    public function contentElementsAreAllowedOnAStandardPageWithAColdTcaCache(): void
    {
        $this->assertTrue(
            GeneralUtility::makeInstance(PageDoktypeRegistry::class)
                ->isRecordTypeAllowedForDoktype('tt_content', 1),
            'The standard page type does not allow "tt_content".'
        );

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageDoktypeRegistration/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageDoktypeRegistration/pages.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tt_content' => [
                    'NEW1' => [
                        'pid' => 1,
                        'header' => 'Created by the data handler',
                        'CType' => 'text',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $this->assertSame(1, $this->countContentElements());
    }

    /**
     * Not the first test of this test case, so the TCA comes from the cache and the TCA
     * override files of the extension are not executed.
     *
     * "sys_file_metadata" is asserted rather than "tt_content" because it is not part of
     * the allow list the "default" page type falls back to, so the assertion fails when
     * the page type is not registered at all.
     */
    #[Test]
    public function everyRecordTypeIsAllowedOnTheRegisteredPageTypeWithAWarmTcaCache(): void
    {
        $this->assertTrue(
            GeneralUtility::makeInstance(PageDoktypeRegistry::class)
                ->isRecordTypeAllowedForDoktype('sys_file_metadata', PageTypes::TYPE_ACADEMIC_PROGRAM),
            'The page type of this extension does not allow every record type.'
        );
    }

    private function countContentElements(): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder->count('uid')->from('tt_content')->executeQuery()->fetchOne();
    }
}
