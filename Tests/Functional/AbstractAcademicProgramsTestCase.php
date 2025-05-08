<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

abstract class AbstractAcademicProgramsTestCase extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/category-types',
        'fgtclb/academic-programs',
    ];
}
