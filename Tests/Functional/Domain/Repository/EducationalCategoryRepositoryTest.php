<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Tests\Functional\Domain\Repository;

use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Model\EducationalCategory;
use FGTCLB\EducationalCourse\Domain\Repository\EducationalCategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class EducationalCategoryRepositoryTest extends FunctionalTestCase
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

        $this->importDataSet(__DIR__ . '/Fixtures/Category.xml');
    }

    /**
     * @test
     */
    public function findCategoryTypes(): void
    {
        $repository = GeneralUtility::makeInstance(EducationalCategoryRepository::class);
        $categories = $repository->findCategoryByType(new Category(Category::TYPE_DEPARTMENT));

        self::assertIsArray($categories);
        self::assertCount(3, $categories);
    }

    /**
     * @test
     */
    public function returnEmptyArrayWhenCategoryTypeNotFound(): void
    {
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $connection->truncate('sys_category');

        $repository = GeneralUtility::makeInstance(EducationalCategoryRepository::class);
        $categories = $repository->findCategoryByType(new Category(Category::TYPE_DEPARTMENT));

        self::assertIsArray($categories);
        self::assertCount(0, $categories);
    }

    /**
     * @test
     */
    public function mapCategoriesOnEducationalCategory(): void
    {
        $repository = GeneralUtility::makeInstance(EducationalCategoryRepository::class);
        $categories = $repository->findCategoryByType(new Category(Category::TYPE_DEPARTMENT));

        self::assertIsArray($categories);
        self::assertContainsOnlyInstancesOf(EducationalCategory::class, $categories);
    }

    /**
     * @test
     */
    public function findCategoryChildren(): void
    {
        $repository = GeneralUtility::makeInstance(EducationalCategoryRepository::class);
        $categories = $repository->findChildren(4);

        self::assertInstanceOf(CategoryCollection::class, $categories);
        self::assertEquals(1, $categories->count());
    }

    /**
     * @test
     */
    public function returnNullWhenCategoryChildrenNotFound(): void
    {
        $repository = GeneralUtility::makeInstance(EducationalCategoryRepository::class);
        $categories = $repository->findChildren(5);

        self::assertNull($categories);
    }

    /**
     * @test
     */
    public function findParentCategory(): void
    {
        $repository = GeneralUtility::makeInstance(EducationalCategoryRepository::class);
        $category = $repository->findParent(1);

        self::assertInstanceOf(EducationalCategory::class, $category);
        self::assertEquals('Category 1', $category->getTitle());
        self::assertNull($category->getType());
    }
}
