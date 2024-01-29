<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Tests\Functional\Domain\Model;

use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Model\EducationalCategory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class EducationalCategoryTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/category_types',
        'typo3conf/ext/educational_course',
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
        $subject = new EducationalCategory(2, 1, 'Test', Category::TYPE_DEPARTMENT);
        self::assertEquals(Category::TYPE_DEPARTMENT, (string)$subject->getType());
    }

    /**
     * @test
     */
    public function createCategoryObjectWithoutType(): void
    {
        $subject = new EducationalCategory(2, 1, 'Test', '');
        self::assertNull($subject->getType());
    }

    /**
     * @test
     */
    public function createCategoryObjectWithTypeDefault(): void
    {
        $subject = new EducationalCategory(2, 1, 'Test', 'default');
        self::assertNull($subject->getType());
    }

    /**
     * @test
     */
    public function createCategoryObjectWithChildObject(): void
    {
        $subject = new EducationalCategory(1, 0, 'Test', '');

        $children = $subject->getChildren();
        self::assertInstanceOf(CategoryCollection::class, $children);
        self::assertEquals(2, $children->count());

        foreach ($children as $child) {
            self::assertInstanceOf(Category::class, $child->getType());
            self::assertEquals(Category::TYPE_DEPARTMENT, $child->getType());
        }
    }

    /**
     * @test
     */
    public function createCategoryObjectAndFindParentObject(): void
    {
        $subject = new EducationalCategory(2, 1, 'Test', Category::TYPE_DEPARTMENT);

        self::assertTrue($subject->hasParent());
        self::assertInstanceOf(EducationalCategory::class, $subject->getParent());

        $parent = $subject->getParent();
        self::assertInstanceOf(EducationalCategory::class, $parent);
        self::assertEquals('Category 1', $parent->getTitle());
        self::assertNull($parent->getType());
    }
}
