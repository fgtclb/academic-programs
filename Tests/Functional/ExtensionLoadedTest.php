<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional;

use FGTCLB\TestingHelper\FunctionalTestCase\ExtensionsLoadedTestsTrait;

final class ExtensionLoadedTest extends AbstractAcademicProgramsTestCase
{
    use ExtensionsLoadedTestsTrait;

    private static $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/academic-base',
        'fgtclb/academic-programs',
        // extension keys
        'academic_base',
        'academic_programs',
    ];
}
