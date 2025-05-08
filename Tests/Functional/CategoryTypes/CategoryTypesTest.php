<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use PHPUnit\Framework\Attributes\Test;

final class CategoryTypesTest extends AbstractAcademicProgramsTestCase
{
    #[Test]
    public function extensionCategoryTypesYamlIsLoaded(): void
    {
        /** @var CategoryTypeRegistry $categoryTypeRegistry */
        $categoryTypeRegistry = $this->get(CategoryTypeRegistry::class);
        $groupedCategoryTypes = $categoryTypeRegistry->getGroupedCategoryTypes();
        $this->assertCount(1, array_keys($groupedCategoryTypes));
        $this->assertArrayHasKey('programs', $groupedCategoryTypes);
        $expected = include __DIR__ . '/Fixtures/DefaultExtensionCategoryTypes.php';
        $this->assertSame($expected, $categoryTypeRegistry->toArray());
    }
}
