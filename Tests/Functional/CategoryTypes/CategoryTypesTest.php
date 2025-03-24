<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\CategoryTypes;

use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CategoryTypesTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/category-types',
        'fgtclb/academic-programs',
    ];

    /**
     * @test
     */
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
