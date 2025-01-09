<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Domain\Model;

use FGTCLB\AcademicPrograms\Collection\CategoryCollection;
use FGTCLB\AcademicPrograms\Domain\Model\Category;
use FGTCLB\AcademicPrograms\Enumeration\CategoryTypes;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CategoryTest extends FunctionalTestCase
{
    /**
     * @var non-empty-string[]
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/category_types',
        'typo3conf/ext/academic_programs',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/Fixtures/CategoryInit.xml');
    }

    /**
     * @test
     */
    public function createCategoryObject(): void
    {
        $subject = new Category(2, 1, 'Test', CategoryTypes::TYPE_DEPARTMENT);
        static::assertEquals(CategoryTypes::TYPE_DEPARTMENT, (string)$subject->getType());
    }

    /**
     * @test
     */
    public function createCategoryObjectWithoutType(): void
    {
        $subject = new Category(2, 1, 'Test', '');
        static::assertNull($subject->getType());
    }

    /**
     * @test
     */
    public function createCategoryObjectWithTypeDefault(): void
    {
        $subject = new Category(2, 1, 'Test', 'default');
        static::assertNull($subject->getType());
    }

    /**
     * @test
     */
    public function createCategoryObjectWithChildObject(): void
    {
        $subject = new Category(1, 0, 'Test', '');

        $children = $subject->getChildren();
        static::assertInstanceOf(CategoryCollection::class, $children);
        static::assertEquals(2, $children->count());

        foreach ($children as $child) {
            static::assertInstanceOf(CategoryTypes::class, $child->getType());
            static::assertEquals(CategoryTypes::TYPE_DEPARTMENT, $child->getType());
        }
    }

    /**
     * @test
     */
    public function createCategoryObjectAndFindParentObject(): void
    {
        $subject = new Category(2, 1, 'Test', CategoryTypes::TYPE_DEPARTMENT);

        static::assertTrue($subject->hasParent());
        static::assertInstanceOf(CategoryTypes::class, $subject->getParent());

        $parent = $subject->getParent();
        static::assertInstanceOf(CategoryTypes::class, $parent);
        static::assertEquals('Category 1', $parent->getTitle());
        static::assertNull($parent->getType());
    }
}
