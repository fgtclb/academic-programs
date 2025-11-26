<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Unit;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CoreVersionTest extends UnitTestCase
{
    /**
     * @group not-core-12
     * @test
     */
    public function verifyCoreVersionEleven(): void
    {
        $this->assertSame(11, (new Typo3Version())->getMajorVersion());
    }

    /**
     * @group not-core-11
     * @test
     */
    public function verifyCoreVersionTwelve(): void
    {
        $this->assertSame(12, (new Typo3Version())->getMajorVersion());
    }
}
