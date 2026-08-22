<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Tca;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pins the values an installation stores in "sys_template.include_static_file" and
 * in "pages.tsconfig_includes".
 *
 * They are not implementation detail: they are written into records, so renaming a
 * registered folder silently empties the configuration of every installation that
 * selected it. Whenever an expectation here changes, the extension needs a Breaking
 * changelog entry naming the old and the new value.
 */
final class StaticRegistrationTest extends AbstractAcademicProgramsTestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function staticTemplateIsRegisteredDataProvider(): \Generator
    {
        yield 'program list' => [
            'EXT:academic_programs/Configuration/TypoScript/ProgramList',
            'Academic Programs: Program List (academic_programs)',
        ];
        yield 'program details' => [
            'EXT:academic_programs/Configuration/TypoScript/ProgramDetails',
            'Academic Programs: Program Details (academic_programs)',
        ];
        yield 'content load override' => [
            'EXT:academic_programs/Configuration/TypoScript/ContentLoad',
            'Academic Programs: Content load override (academic_programs)',
        ];
        yield 'all components' => [
            'EXT:academic_programs/Configuration/TypoScript/Full',
            'Academic Programs: All components (academic_programs)',
        ];
        // The value installations stored before the configuration was cut per component.
        // It is the shared plugin block, and it keeps its folder for exactly that reason.
        yield 'shared plugin settings' => [
            'EXT:academic_programs/Configuration/TypoScript/',
            'Academic Programs: Shared plugin settings and page rendering (academic_programs)',
        ];
    }

    #[Test]
    #[DataProvider('staticTemplateIsRegisteredDataProvider')]
    public function staticTemplateIsRegistered(string $value, string $label): void
    {
        $this->assertContains(
            ['label' => $label, 'value' => $value],
            $GLOBALS['TCA']['sys_template']['columns']['include_static_file']['config']['items'] ?? [],
        );
    }

    /**
     * The registration above is a string, so it stays green when the folder it names
     * is renamed or removed - which is the failure this test class exists for. A
     * static template that points at a folder without any of the three files the core
     * looks for is not an error either, it simply contributes nothing, so the folder
     * and its content have to be asserted separately.
     */
    #[Test]
    #[DataProvider('staticTemplateIsRegisteredDataProvider')]
    public function registeredStaticTemplateFolderExistsAndCarriesTypoScript(string $value, string $label): void
    {
        $path = rtrim(GeneralUtility::getFileAbsFileName($value), '/');

        $this->assertDirectoryExists(
            $path,
            sprintf('The folder registered as "%s" does not exist.', $label),
        );

        $carriedFiles = array_values(array_filter(
            ['constants.typoscript', 'setup.typoscript', 'include_static_file.txt'],
            static fn(string $fileName): bool => file_exists($path . '/' . $fileName),
        ));

        $this->assertNotSame(
            [],
            $carriedFiles,
            sprintf(
                'The folder registered as "%s" holds none of "constants.typoscript", "setup.typoscript" or'
                    . ' "include_static_file.txt", so the static template delivers nothing.',
                $label,
            ),
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function pageTsConfigFileIsRegisteredDataProvider(): \Generator
    {
        yield 'program list' => [
            'EXT:academic_programs/Configuration/TSconfig/ProgramList/page.tsconfig',
            'Academic Programs: Program List (academic_programs)',
        ];
        yield 'program details' => [
            'EXT:academic_programs/Configuration/TSconfig/ProgramDetails/page.tsconfig',
            'Academic Programs: Program Details (academic_programs)',
        ];
        yield 'all components' => [
            'EXT:academic_programs/Configuration/TSconfig/Full/page.tsconfig',
            'Academic Programs: All components (academic_programs)',
        ];
    }

    #[Test]
    #[DataProvider('pageTsConfigFileIsRegisteredDataProvider')]
    public function pageTsConfigFileIsRegistered(string $value, string $label): void
    {
        $this->assertContains(
            ['label' => $label, 'value' => $value],
            $GLOBALS['TCA']['pages']['columns']['tsconfig_includes']['config']['items'] ?? [],
        );
    }

    /**
     * As above, and worse: an unresolved page TSconfig include is silent, so a
     * registration that names a file which is not there configures nothing and reports
     * nothing.
     */
    #[Test]
    #[DataProvider('pageTsConfigFileIsRegisteredDataProvider')]
    public function registeredPageTsConfigFileExists(string $value, string $label): void
    {
        $this->assertFileExists(
            GeneralUtility::getFileAbsFileName($value),
            sprintf('The file registered as "%s" does not exist.', $label),
        );
    }

    /**
     * Every entry this extension offers has to point into this extension. The assertions
     * above pass just as well when a label of this extension carries the path of another
     * one - which is not hypothetical in this extension family, "academic_partners"
     * registered its static template under the key of "academic_programs" for years.
     */
    #[Test]
    public function everyRegisteredEntryOfThisExtensionPointsIntoThisExtension(): void
    {
        $entries = [
            ...($GLOBALS['TCA']['sys_template']['columns']['include_static_file']['config']['items'] ?? []),
            ...($GLOBALS['TCA']['pages']['columns']['tsconfig_includes']['config']['items'] ?? []),
        ];

        foreach ($entries as $entry) {
            $label = (string)($entry['label'] ?? '');
            if (!str_contains($label, 'Academic Programs')) {
                continue;
            }
            $this->assertStringStartsWith(
                'EXT:academic_programs/',
                (string)($entry['value'] ?? ''),
                sprintf('The entry "%s" points at another extension.', $label),
            );
        }
    }
}
