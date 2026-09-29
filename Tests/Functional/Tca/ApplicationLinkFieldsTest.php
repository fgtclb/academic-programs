<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Tca;

use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * The two fields of the application link: on the program tab of the program page type,
 * absent from a standard page, and without a line in "ext_tables.sql" - the columns are
 * derived from the TCA, and the test instance builds its database the same way a
 * database compare does.
 */
final class ApplicationLinkFieldsTest extends AbstractAcademicProgramsTestCase
{
    private const PROGRAM_TAB = '--div--;LLL:EXT:academic_programs/Resources/Private/Language/locallang_be.xlf:pages.div.program';

    #[Test]
    public function applicationLinkAcceptsAPageOrAnExternalUrl(): void
    {
        $config = $GLOBALS['TCA']['pages']['columns']['application_link']['config'] ?? [];

        $this->assertSame('link', $config['type'] ?? null);
        $this->assertSame(['page', 'url'], $config['allowedTypes'] ?? null);
    }

    #[Test]
    public function applicationLinkLabelHoldsAtMostSixtyCharacters(): void
    {
        $config = $GLOBALS['TCA']['pages']['columns']['application_link_label']['config'] ?? [];

        $this->assertSame('input', $config['type'] ?? null);
        $this->assertSame(60, $config['max'] ?? null);
    }

    #[Test]
    public function programTabOffersTheApplicationLinkAfterTheCreditPoints(): void
    {
        $fields = $this->programTabFields(PageTypes::TYPE_ACADEMIC_PROGRAM);

        $this->assertSame(
            ['credit_points', 'application_link', 'application_link_label', 'job_profile'],
            array_slice($fields, 0, 4),
        );
    }

    #[Test]
    public function standardPageOffersNeitherField(): void
    {
        $showitem = (string)$GLOBALS['TCA']['pages']['types'][PageRepository::DOKTYPE_DEFAULT]['showitem'];

        $this->assertStringNotContainsString('application_link', $showitem);
    }

    #[Test]
    public function bothColumnsAreDerivedFromTheTca(): void
    {
        $columns = $this->getConnectionPool()->getConnectionForTable('pages')->createSchemaManager()->listTableColumns('pages');

        $this->assertArrayHasKey('application_link', $columns);
        $this->assertArrayHasKey('application_link_label', $columns);
        $this->assertSame(60, $columns['application_link_label']->getLength());
        $this->assertStringNotContainsString(
            'application_link',
            (string)file_get_contents(ExtensionManagementUtility::extPath('academic_programs') . 'ext_tables.sql'),
        );
    }

    /**
     * @return list<string> the fields of the program tab, in their order
     */
    private function programTabFields(int $doktype): array
    {
        $showitem = (string)$GLOBALS['TCA']['pages']['types'][$doktype]['showitem'];
        $tabStart = strpos($showitem, self::PROGRAM_TAB);
        $this->assertIsInt($tabStart, 'The program tab is missing.');
        $tab = substr($showitem, $tabStart + strlen(self::PROGRAM_TAB));
        $tabEnd = strpos($tab, '--div--');
        if ($tabEnd !== false) {
            $tab = substr($tab, 0, $tabEnd);
        }

        return array_values(array_filter(array_map(
            static fn(string $field): string => trim(explode(';', $field)[0]),
            explode(',', $tab),
        )));
    }
}
