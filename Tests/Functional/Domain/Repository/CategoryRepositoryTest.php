<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Tests\Functional\Domain\Repository;

use FGTCLB\EducationalCourse\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Repository\CategoryRepository;
use FGTCLB\EducationalCourse\Enumeration\CategoryTypes;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CategoryRepositoryTest extends FunctionalTestCase
{
    /**
     * @var non-empty-string[]
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
    public function findCategoryChildren(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $repository->findChildren(4);

        self::assertInstanceOf(CategoryCollection::class, $categories);
        self::assertEquals(1, $categories->count());
    }

    /**
     * @test
     */
    public function returnNullWhenCategoryChildrenNotFound(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $repository->findChildren(5);

        self::assertNull($categories);
    }

    /**
     * @test
     */
    public function findParentCategory(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $category = $repository->findParent(1);

        self::assertInstanceOf(CategoryTypes::class, $category);
        self::assertEquals('Category 1', $category->getTitle());
        self::assertNull($category->getType());
    }

    /**
     * @test
     */
    public function findCategoryByType(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $category = $repository->findByType(3, new CategoryTypes(CategoryTypes::TYPE_DEPARTMENT));

        self::assertInstanceOf(CategoryCollection::class, $category);
        self::assertEquals(3, $category->count());
    }

    /**
     * @test
     */
    public function returnEmptyCollectionWhenCategoryTypeNotFound(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $category = $repository->findByType(2, new CategoryTypes(CategoryTypes::TYPE_DEPARTMENT));

        self::assertInstanceOf(CategoryCollection::class, $category);
        self::assertEquals(0, $category->count());

        unset($category);
        $category = $repository->findByType(2, new CategoryTypes(CategoryTypes::TYPE_COURSE_TYPE));

        self::assertInstanceOf(CategoryCollection::class, $category);
        self::assertEquals(0, $category->count());
    }

    /**
     * @test
     */
    public function findAllCategoriesByPageId(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $repository->findAllByPageId(3);

        self::assertCount(3, $categories);

        foreach ($categories as $category) {
            self::assertEquals(CategoryTypes::TYPE_DEPARTMENT, $category->getType());
        }
    }

    /**
     * @test
     */
    public function returnEmptyCollectionWhenPageIdeNotFound(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $repository->findAllByPageId(10);

        self::assertInstanceOf(CategoryCollection::class, $categories);
        self::assertEquals(0, $categories->count());
    }

    /**
     * @test
     */
    public function findContentCategories(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $categories = $repository->getByDatabaseFields(1);

        self::assertInstanceOf(CategoryCollection::class, $categories);
        self::assertEquals(1, $categories->count());
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

        self::assertInstanceOf(CategoryCollection::class, $categories);
        self::assertEquals(0, $categories->count());
    }

    /**
     * @test
     */
    public function findCategoriesByIdListAndType(): void
    {
        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        $category = $repository->findByUidListAndType([2, 3], new CategoryTypes(CategoryTypes::TYPE_DEPARTMENT));

        self::assertIsArray($category);
        self::assertContainsOnlyInstancesOf(CategoryTypes::class, $category);
        self::assertCount(2, $category);
        self::assertEquals(CategoryTypes::TYPE_DEPARTMENT, $category[0]->getType());
        self::assertEquals(CategoryTypes::TYPE_DEPARTMENT, $category[1]->getType());
    }
}
