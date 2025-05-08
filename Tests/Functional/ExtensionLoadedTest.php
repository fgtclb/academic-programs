<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

final class ExtensionLoadedTest extends AbstractAcademicProgramsTestCase
{
    #[Test]
    public function testCaseLoadsExtension(): void
    {
        $this->assertContains('fgtclb/academic-programs', $this->testExtensionsToLoad);
    }

    #[Test]
    public function extensionIsLoaded(): void
    {
        $this->assertTrue(ExtensionManagementUtility::isLoaded('academic_programs'));
    }
}
