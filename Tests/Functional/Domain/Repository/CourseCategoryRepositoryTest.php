<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Tests\Functional\Domain\Repository;

use FGTCLB\EducationalCourse\Domain\Collection\CategoryCollection;
use FGTCLB\EducationalCourse\Domain\Enumeration\Category;
use FGTCLB\EducationalCourse\Domain\Model\EducationalCategory;
use FGTCLB\EducationalCourse\Domain\Repository\CourseCategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CourseCategoryRepositoryTest extends FunctionalTestCase
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
    public function findCategoryByType(): void
    {
        $repository = GeneralUtility::makeInstance(CourseCategoryRepository::class);
        $category = $repository->findByType(3, new Category(Category::TYPE_DEPARTMENT));

        self::assertInstanceOf(CategoryCollection::class, $category);
        self::assertEquals(3, $category->count());
    }

    /**
     * @test
     */
    public function returnEmptyCollectionWhenCategoryTypeNotFound(): void
    {
        $repository = GeneralUtility::makeInstance(CourseCategoryRepository::class);
        $category = $repository->findByType(2, new Category(Category::TYPE_DEPARTMENT));

        self::assertInstanceOf(CategoryCollection::class, $category);
        self::assertEquals(0, $category->count());

        unset($category);
        $category = $repository->findByType(2, new Category(Category::TYPE_COURSE_TYPE));

        self::assertInstanceOf(CategoryCollection::class, $category);
        self::assertEquals(0, $category->count());
    }

    /**
     * @test
     */
    public function findAllCategoriesByPageId(): void
    {
        $repository = GeneralUtility::makeInstance(CourseCategoryRepository::class);
        $categories = $repository->findAllByPageId(3);

        self::assertCount(3, $categories);

        foreach ($categories as $category) {
            self::assertEquals(Category::TYPE_DEPARTMENT, $category->getType());
        }
    }

    /**
     * @test
     */
    public function returnEmptyCollectionWhenPageIdeNotFound(): void
    {
        $repository = GeneralUtility::makeInstance(CourseCategoryRepository::class);
        $categories = $repository->findAllByPageId(10);

        self::assertInstanceOf(CategoryCollection::class, $categories);
        self::assertEquals(0, $categories->count());
    }

    /**
     * @test
     */
    public function findAllCategories(): void
    {
        $repository = GeneralUtility::makeInstance(CourseCategoryRepository::class);
        $categories = $repository->findAll();

        self::assertCount(3, $categories);
    }

    /**
     * @test
     */
    public function returnEmptyCollectionWhenCategoriesNotFound(): void
    {
        $connection = $this->getConnectionPool()->getConnectionByName('Default');
        $connection->truncate('sys_category');

        $repository = GeneralUtility::makeInstance(CourseCategoryRepository::class);
        $categories = $repository->findAll();

        self::assertInstanceOf(CategoryCollection::class, $categories);
        self::assertEquals(0, $categories->count());
    }

    /**
     * @test
     */
    public function findContentCategories(): void
    {
        $repository = GeneralUtility::makeInstance(CourseCategoryRepository::class);
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

        $repository = GeneralUtility::makeInstance(CourseCategoryRepository::class);
        $categories = $repository->getByDatabaseFields(1);

        self::assertInstanceOf(CategoryCollection::class, $categories);
        self::assertEquals(0, $categories->count());
    }

    /**
     * @test
     */
    public function findCategoriesByIdListAndType(): void
    {
        $repository = GeneralUtility::makeInstance(CourseCategoryRepository::class);
        $category = $repository->findByUidListAndType([2, 3], new Category(Category::TYPE_DEPARTMENT));

        self::assertIsArray($category);
        self::assertContainsOnlyInstancesOf(EducationalCategory::class, $category);
        self::assertCount(2, $category);
        self::assertEquals(Category::TYPE_DEPARTMENT, $category[0]->getType());
        self::assertEquals(Category::TYPE_DEPARTMENT, $category[1]->getType());
    }
}
