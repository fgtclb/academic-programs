<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Backend\Controller\FormSelectTreeAjaxController;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Where the category tree of a program page, of the program list and of the program
 * finder starts, as an editor gets it: at the categories the site setting
 * `plugin.tx_academicprograms.categoryRootUids` names, or, when the site names none, at
 * the top of the category tree, exactly as without the setting.
 *
 * The form is compiled the way FormEngine compiles it when a record is opened, see
 * `docs/architecture/backend-select-items.md`, and the tree is loaded the way the form
 * loads it afterwards.
 *
 * The categories of the fixture are three trees, each level ordered by title in the
 * tree: "Locations" (9) with "Berlin" (10), "News" (11) with "Press" (12) and
 * "Programs" (7) with "Bachelor of Science" (8). One site per configuration, each with a
 * program page:
 *
 * | Root | Program page | Setting                            | Sets                      |
 * |------|--------------|------------------------------------|---------------------------|
 * | 1    | 2            | `7`                                | aggregate                 |
 * | 11   | 12           | `7,9`                              | aggregate                 |
 * | 21   | 22           | `programs`                         | aggregate                 |
 * | 31   | 32           | none                               | aggregate                 |
 * | 41   | 42           | `7`, written as a tree             | the program list set only |
 * | 51   | 52           | none, the page belongs to no site  |                           |
 * | 61   | 62           | `7`, written with a dotted key     | the program list set only |
 * | 71   | 72           | `7`, page TSconfig names `9`       | aggregate                 |
 * | 81   | 82           | `[7, 9]`, a list written as a tree | the program list set only |
 */
final class CategoryTreeRootTest extends AbstractAcademicProgramsTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const SETTING = 'plugin.tx_academicprograms.categoryRootUids';

    /**
     * The whole tree, below the node core renders as its top.
     */
    private const WHOLE_TREE = [0, 9, 10, 11, 12, 7, 8];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryTreeRoot/records.csv');
        $this->setUpBackendUser(1);
        $this->writeSite('one-root', 1, ['fgtclb/academic-programs'], [self::SETTING => '7']);
        $this->writeSite('two-roots', 11, ['fgtclb/academic-programs'], [self::SETTING => '7,9']);
        $this->writeSite('not-a-uid', 21, ['fgtclb/academic-programs'], [self::SETTING => 'programs']);
        $this->writeSite('no-setting', 31, ['fgtclb/academic-programs'], []);
        // The component sets declare no settings, so both sites write a setting without a
        // definition. Core keeps it under its path only when it is written as a tree.
        $this->writeSite('list-set-tree', 41, ['fgtclb/academic-programs-program-list'], ['plugin' => ['tx_academicprograms' => ['categoryRootUids' => '7']]]);
        $this->writeSite('list-set-dotted-key', 61, ['fgtclb/academic-programs-program-list'], [self::SETTING => '7']);
        $this->writeSite('page-tsconfig', 71, ['fgtclb/academic-programs'], [self::SETTING => '7']);
        $this->writeSite('list-set-list', 81, ['fgtclb/academic-programs-program-list'], ['plugin' => ['tx_academicprograms' => ['categoryRootUids' => [7, 9]]]]);
    }

    #[Test]
    public function theTreeOfAProgramPageStartsAtTheConfiguredCategory(): void
    {
        $this->assertSame('7', $this->pageCategoriesField(2)['config']['treeConfig']['startingPoints'] ?? null);

        $tree = $this->loadPageTree(2);
        $this->assertSame([7, 8], array_keys($tree));
        $this->assertTrue($tree[7]);
    }

    #[Test]
    public function severalConfiguredCategoriesAreAllRootsOfTheTree(): void
    {
        $this->assertSame('7,9', $this->pageCategoriesField(12)['config']['treeConfig']['startingPoints'] ?? null);

        $tree = $this->loadPageTree(12);
        $this->assertSame([0, 9, 10, 7, 8], array_keys($tree));
        $this->assertFalse($tree[0]);
        $this->assertTrue($tree[7]);
        $this->assertTrue($tree[9]);
    }

    /**
     * Where the setting names no category, the field is the field without the setting.
     * Core would resolve its marker to a single starting point `0` there, which offers the
     * whole tree with its top selectable.
     *
     * @return \Generator<string, array{0: int}>
     */
    public static function pagesOfferingTheWholeTreeDataProvider(): \Generator
    {
        yield 'no setting' => [32];
        yield 'not a uid' => [22];
        yield 'no site' => [52];
        yield 'dotted key without a definition' => [62];
    }

    #[DataProvider('pagesOfferingTheWholeTreeDataProvider')]
    #[Test]
    public function aPageWhoseSiteNamesNoCategoryOffersTheWholeTree(int $pageUid): void
    {
        $this->assertArrayNotHasKey('startingPoints', $this->pageCategoriesField($pageUid)['config']['treeConfig'] ?? []);

        $tree = $this->loadPageTree($pageUid);
        $this->assertSame(self::WHOLE_TREE, array_keys($tree));
        $this->assertFalse($tree[0]);
    }

    #[Test]
    public function theTreeOfAStandardPageIsLeftAlone(): void
    {
        $this->assertArrayNotHasKey('startingPoints', $this->pageCategoriesField(3)['config']['treeConfig'] ?? []);
        $this->assertSame(self::WHOLE_TREE, array_keys($this->loadTree('pages', 3, '1', 'categories')));
    }

    /**
     * A site that depends on a component set only does not declare the setting, and still
     * gets it when its settings write it as a tree.
     */
    #[Test]
    public function aSiteWithTheProgramListSetOnlyStartsAtTheConfiguredCategory(): void
    {
        $this->assertSame([7, 8], array_keys($this->loadPageTree(42)));
        $this->assertSame([7, 8], array_keys($this->loadElementTree(41, 'settings.categories')));
    }

    /**
     * Without a definition, nothing validates the value as a string, and core resolves a
     * list written in YAML as well. The site settings do not keep the order of the list,
     * which the tree does not need: it orders its roots by title.
     */
    #[Test]
    public function aListOnASiteWithoutTheDefinitionNamesSeveralRoots(): void
    {
        $startingPoints = explode(',', (string)($this->pageCategoriesField(82)['config']['treeConfig']['startingPoints'] ?? ''));
        sort($startingPoints);
        $this->assertSame(['7', '9'], $startingPoints);
        $this->assertSame([0, 9, 10, 7, 8], array_keys($this->loadPageTree(82)));
    }

    /**
     * A program page that is being created gets its site from the page it is created on.
     * The form hands the tree the uid of that page and the default values of the record.
     *
     * @return \Generator<string, array{0: int, 1: ?string, 2: list<int>}>
     */
    public static function newProgramPageDataProvider(): \Generator
    {
        yield 'configured site' => [1, '7', [7, 8]];
        yield 'no setting' => [31, null, self::WHOLE_TREE];
    }

    /**
     * @param list<int> $expectedTree
     */
    #[DataProvider('newProgramPageDataProvider')]
    #[Test]
    public function aNewProgramPageStartsWhereItsSiteSays(int $parentPageUid, ?string $expectedStartingPoints, array $expectedTree): void
    {
        $defaultValues = ['pages' => ['doktype' => 20]];
        $field = $this->compile('pages', $parentPageUid, 'new', $defaultValues)['processedTca']['columns']['categories'] ?? null;
        $this->assertIsArray($field);
        $this->assertSame($expectedStartingPoints, $field['config']['treeConfig']['startingPoints'] ?? null);

        $tree = $this->loadTree('pages', $parentPageUid, '20', 'categories', null, 'new', $defaultValues);
        $this->assertSame($expectedTree, array_keys($tree));
        $this->assertFalse($tree[0] ?? false);
    }

    /**
     * The starting points a project sets in page TSconfig, as it did before the setting,
     * are read after the setting and win until the project removes them.
     */
    #[Test]
    public function pageTsConfigStillWins(): void
    {
        $this->assertSame([9, 10], array_keys($this->loadPageTree(72)));
    }

    /**
     * @return \Generator<string, array{0: int, 1: string}>
     */
    public static function elementCategoryFieldDataProvider(): \Generator
    {
        yield 'program list, categories' => [1, 'settings.categories'];
        yield 'program finder, preselected categories' => [2, 'settings.preselectedCategories'];
    }

    #[DataProvider('elementCategoryFieldDataProvider')]
    #[Test]
    public function theTreeOfAnElementStartsAtTheConfiguredCategory(int $contentUid, string $fieldName): void
    {
        $this->assertSame('7', $this->flexFormField($contentUid, $fieldName)['config']['treeConfig']['startingPoints'] ?? null);
        $this->assertSame([7, 8], array_keys($this->loadElementTree($contentUid, $fieldName)));
    }

    #[DataProvider('elementCategoryFieldDataProvider')]
    #[Test]
    public function theTreeOfAnElementOnASiteWithoutTheSettingIsTheWholeTree(int $contentUid, string $fieldName): void
    {
        // The elements of the site without the setting are 31 and 32.
        $contentUid += 30;
        $this->assertArrayNotHasKey('startingPoints', $this->flexFormField($contentUid, $fieldName)['config']['treeConfig'] ?? []);

        $tree = $this->loadElementTree($contentUid, $fieldName);
        $this->assertSame(self::WHOLE_TREE, array_keys($tree));
        $this->assertFalse($tree[0]);
    }

    /**
     * The program page of the configured site carries "Press" (12), which is outside of
     * the tree it is offered. The form starts with the value, so saving the page without
     * touching the tree keeps the category. The tree does not show it, though, and the tree
     * writes the field from its own selection as soon as the editor changes it, which
     * drops the category.
     */
    #[Test]
    public function aCategoryOutsideOfTheConfiguredRootIsKeptInTheForm(): void
    {
        $this->assertSame(['8', '12'], array_map(strval(...), $this->compile('pages', 2)['databaseRow']['categories'] ?? []));
        $this->assertArrayNotHasKey(12, $this->loadPageTree(2));
    }

    /**
     * @param non-empty-string $identifier
     * @param list<string> $dependencies
     * @param array<string, mixed> $settings
     */
    private function writeSite(string $identifier, int $rootPageId, array $dependencies, array $settings): void
    {
        $this->writeSiteConfiguration(
            identifier: $identifier,
            site: $this->buildSiteConfiguration(
                rootPageId: $rootPageId,
                base: 'https://' . $identifier . '.localhost/',
                additionalRootConfiguration: [
                    'dependencies' => $dependencies,
                    'settings' => $settings,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function pageCategoriesField(int $pageUid): array
    {
        $field = $this->compile('pages', $pageUid)['processedTca']['columns']['categories'] ?? null;
        $this->assertIsArray($field, sprintf('Page %d has no category field.', $pageUid));

        return $field;
    }

    /**
     * @return array<string, mixed>
     */
    private function flexFormField(int $contentUid, string $fieldName): array
    {
        $field = $this->compile('tt_content', $contentUid)['processedTca']['columns']['pi_flexform']['config']['ds']['sheets']['sDEF']['ROOT']['el'][$fieldName] ?? null;
        $this->assertIsArray($field, sprintf('Element %d has no field "%s".', $contentUid, $fieldName));

        return $field;
    }

    /**
     * @return array<int, bool> The selectability of each item, by its uid, in the order of
     *         the tree
     */
    private function loadPageTree(int $pageUid): array
    {
        return $this->loadTree('pages', $pageUid, '20', 'categories');
    }

    /**
     * @return array<int, bool>
     */
    private function loadElementTree(int $contentUid, string $flexFormFieldName): array
    {
        $contentType = (string)($this->compile('tt_content', $contentUid)['recordTypeValue'] ?? '');

        return $this->loadTree('tt_content', $contentUid, $contentType, 'pi_flexform', $flexFormFieldName);
    }

    /**
     * The tree an editor sees is not part of the form: the form loads it afterwards from
     * `FormSelectTreeAjaxController`, which compiles the field alone through a form data
     * group of its own. This is the query the form sends for a field of a record or of its
     * FlexForm.
     *
     * @param array<string, array<string, mixed>> $defaultValues
     * @return array<int, bool>
     */
    private function loadTree(
        string $tableName,
        int $uid,
        string $recordType,
        string $fieldName,
        ?string $flexFormFieldName = null,
        string $command = 'edit',
        array $defaultValues = [],
    ): array {
        $queryParameters = [
            'tableName' => $tableName,
            'fieldName' => $fieldName,
            'uid' => (string)$uid,
            'command' => $command,
            'recordTypeValue' => $recordType,
        ];
        if ($defaultValues !== []) {
            $queryParameters['defaultValues'] = json_encode($defaultValues, JSON_THROW_ON_ERROR);
        }
        if ($flexFormFieldName !== null) {
            $queryParameters += [
                'dataStructureIdentifier' => $this->compile($tableName, $uid)['processedTca']['columns'][$fieldName]['config']['dataStructureIdentifier'] ?? '',
                'flexFormSheetName' => 'sDEF',
                'flexFormFieldName' => $flexFormFieldName,
                'flexFormContainerName' => '',
                'flexFormContainerIdentifier' => '',
                'flexFormContainerFieldName' => '',
                'flexFormSectionContainerIsNew' => '0',
            ];
        }
        $request = (new ServerRequest('https://localhost/typo3/ajax/record/tree/fetchData'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams($queryParameters);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        $response = $this->get(FormSelectTreeAjaxController::class)->fetchDataAction($request);
        $items = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($items);

        $tree = [];
        foreach ($items as $item) {
            $tree[(int)$item['identifier']] = (bool)$item['selectable'];
        }

        return $tree;
    }

    /**
     * @param array<string, array<string, mixed>> $defaultValues
     * @return array<string, mixed>
     */
    private function compile(string $tableName, int $uid, string $command = 'edit', array $defaultValues = []): array
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => $tableName,
                'vanillaUid' => $uid,
                'command' => $command,
                'defaultValues' => $defaultValues,
            ],
            $this->get(TcaDatabaseRecord::class),
        );
    }
}
