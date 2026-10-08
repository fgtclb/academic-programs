<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Tca;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Every content element of the extension carries the registered extension icon in its
 * CType item and as its type icon.
 *
 * Both elements named the path of the icon file where an icon identifier belongs, so
 * the page module, the list module and the type select showed the generic icon
 * (ACE-853).
 */
final class ContentElementIconTest extends AbstractAcademicProgramsTestCase
{
    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function contentElementTypeDataProvider(): \Generator
    {
        yield 'program list' => ['academicprograms_programlist'];
        yield 'program details' => ['academicprograms_programdetails'];
    }

    #[Test]
    #[DataProvider('contentElementTypeDataProvider')]
    public function contentElementCarriesTheRegisteredExtensionIcon(string $contentElementType): void
    {
        $item = null;
        foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [] as $candidate) {
            if (($candidate['value'] ?? null) === $contentElementType) {
                $item = $candidate;
                break;
            }
        }

        $this->assertIsArray($item, sprintf('The CType item "%s" is not registered.', $contentElementType));
        $this->assertSame('academic-programs', $item['icon'] ?? null, 'The CType item carries another icon.');
        $this->assertSame(
            'academic-programs',
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$contentElementType] ?? null,
            'The content element type carries another type icon.',
        );
        $this->assertTrue(GeneralUtility::makeInstance(IconRegistry::class)->isRegistered('academic-programs'));
    }
}
