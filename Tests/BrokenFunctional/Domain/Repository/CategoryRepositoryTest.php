<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPrograms\Collection\CategoryCollection;
use FGTCLB\AcademicPrograms\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicPrograms\Enumeration\CategoryTypes;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CategoryRepositoryTest extends FunctionalTestCase
{
    /**
     * @var non-empty-string[]
     */
    protected array $testExtensionsToLoad = [
        'fgtclb/category-types',
        'fgtclb/academic-programs',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/Fixtures/Category.xml');
    }

    /**
     * @test
     */
    public function findCategoryChildren(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $repository->findChildren(4);

        $this->assertInstanceOf(CategoryCollection::class, $categories);
        $this->assertEquals(1, $categories->count());
    }

    /**
     * @test
     */
    public function returnNullWhenCategoryChildrenNotFound(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $repository->findChildren(5);

        $this->assertNull($categories);
    }

    /**
     * @test
     */
    public function findParentCategory(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $category = $repository->findParent(1);

        $this->assertInstanceOf(CategoryTypes::class, $category);
        $this->assertEquals('Category 1', $category->getTitle());
        $this->assertNull($category->getType());
    }

    /**
     * @test
     */
    public function findCategoryByType(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $category = $repository->findByType(3, new CategoryTypes(CategoryTypes::TYPE_DEPARTMENT));

        $this->assertInstanceOf(CategoryCollection::class, $category);
        $this->assertEquals(3, $category->count());
    }

    /**
     * @test
     */
    public function returnEmptyCollectionWhenCategoryTypeNotFound(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $category = $repository->findByType(2, new CategoryTypes(CategoryTypes::TYPE_DEPARTMENT));

        $this->assertInstanceOf(CategoryCollection::class, $category);
        $this->assertEquals(0, $category->count());

        unset($category);
        $category = $repository->findByType(2, new CategoryTypes(CategoryTypes::TYPE_COURSE_TYPE));

        $this->assertInstanceOf(CategoryCollection::class, $category);
        $this->assertEquals(0, $category->count());
    }

    /**
     * @test
     */
    public function findAllCategoriesByPageId(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $repository->findAllByPageId(3);

        $this->assertCount(3, $categories);

        foreach ($categories as $category) {
            $this->assertEquals(CategoryTypes::TYPE_DEPARTMENT, $category->getType());
        }
    }

    /**
     * @test
     */
    public function returnEmptyCollectionWhenPageIdeNotFound(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $repository->findAllByPageId(10);

        $this->assertInstanceOf(CategoryCollection::class, $categories);
        $this->assertEquals(0, $categories->count());
    }

    /**
     * @test
     */
    public function findContentCategories(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $repository->getByDatabaseFields(1);

        $this->assertInstanceOf(CategoryCollection::class, $categories);
        $this->assertEquals(1, $categories->count());
    }

    /**
     * @test
     */
    public function returnEmptyCollectionWhenNotFindContentCategories(): void
    {
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $connection->truncate('sys_category_record_mm');

        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $repository->getByDatabaseFields(1);

        $this->assertInstanceOf(CategoryCollection::class, $categories);
        $this->assertEquals(0, $categories->count());
    }

    /**
     * @test
     */
    public function findCategoriesByIdListAndType(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $category = $repository->findByUidListAndType([2, 3], new CategoryTypes(CategoryTypes::TYPE_DEPARTMENT));

        $this->assertIsArray($category);
        $this->assertContainsOnlyInstancesOf(CategoryTypes::class, $category);
        $this->assertCount(2, $category);
        $this->assertEquals(CategoryTypes::TYPE_DEPARTMENT, $category[0]->getType());
        $this->assertEquals(CategoryTypes::TYPE_DEPARTMENT, $category[1]->getType());
    }
}
