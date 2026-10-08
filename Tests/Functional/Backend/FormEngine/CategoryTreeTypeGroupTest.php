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
 * The category trees of the program list and the program finder, fetched the way FormEngine fetches it
 * when an editor opens the field: through the select tree AJAX controller.
 *
 * The plugins use the categories of the `programs` group only. The demand reads the
 * selection with `CategoryRepository::getByDatabaseFields('programs', ...)`, which
 * drops every category of another type silently, so a tree that offered them let an
 * editor select a filter the frontend never applied. The fixture holds one category
 * of four program types (1 to 4), a plain category (5) and one category each of a
 * type of the partners, projects and jobs extensions (6 to 8).
 *
 * A category of the group below a parent outside of it stays reachable: the tree
 * shows a category only together with its ancestors, so the parent is offered as
 * well (9 above 10, 12 above 13), while another category below the same parent is
 * not (11). A deleted category (14) and a translation (15) are not offered.
 */
final class CategoryTreeTypeGroupTest extends AbstractAcademicProgramsTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryTreeTypeGroup/categories.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG'], $GLOBALS['TYPO3_REQUEST']);
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function pluginProvider(): array
    {
        return [
            'program list, categories' => ['academicprograms_programlist', 'settings.categories'],
            'program finder, preselected categories' => ['academicprograms_programfinder', 'settings.preselectedCategories'],
        ];
    }

    #[Test]
    #[DataProvider('pluginProvider')]
    public function theCategoryTreeOffersTheCategoriesOfTheProgramsGroupOnly(string $cType, string $flexFormFieldName): void
    {
        $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->update('tt_content', ['CType' => $cType], ['uid' => 1]);

        $this->assertSame([1, 2, 3, 4, 9, 10, 12, 13], $this->offeredCategoryUids($flexFormFieldName));
    }

    /**
     * The category root of the site (ACE-768) and the group narrow the tree together:
     * below the plain parent 9 the tree offers the program category 10 and leaves the
     * partner category 11 out, and the root itself stays as the ancestor of 10.
     */
    #[Test]
    #[DataProvider('pluginProvider')]
    public function theCategoryTreeBelowTheCategoryRootOfTheSiteOffersTheCategoriesOfTheProgramsGroupOnly(string $cType, string $flexFormFieldName): void
    {
        $this->writeSiteConfiguration(
            identifier: 'category-root',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://category-root.localhost/',
                additionalRootConfiguration: [
                    'dependencies' => ['fgtclb/academic-programs-program-list'],
                    'settings' => ['plugin' => ['tx_academicprograms' => ['categoryRootUids' => '9']]],
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
        $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->update('tt_content', ['CType' => $cType], ['uid' => 1]);

        $this->assertSame([9, 10], $this->offeredCategoryUids($flexFormFieldName));
    }

    /**
     * @return list<int>
     */
    private function offeredCategoryUids(string $flexFormFieldName): array
    {
        $row = $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->select(['*'], 'tt_content', ['uid' => 1])
            ->fetchAssociative();
        $this->assertIsArray($row);
        // The identifier the form hands the tree request. Taken from the compiled form, as
        // FormEngine does, because v14 resolves it through the TCA schema, not the column.
        $compileRequest = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $compileRequest = $compileRequest->withAttribute('normalizedParams', NormalizedParams::createFromRequest($compileRequest));
        $GLOBALS['TYPO3_REQUEST'] = $compileRequest;
        $form = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            ['request' => $compileRequest, 'tableName' => 'tt_content', 'vanillaUid' => 1, 'command' => 'edit'],
            $this->get(TcaDatabaseRecord::class),
        );
        $dataStructureIdentifier = (string)($form['processedTca']['columns']['pi_flexform']['config']['dataStructureIdentifier'] ?? '');
        $this->assertNotSame('', $dataStructureIdentifier);

        $request = (new ServerRequest('https://localhost/typo3/ajax/record/tree/fetchData'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withQueryParams([
                'tableName' => 'tt_content',
                'fieldName' => 'pi_flexform',
                'uid' => 1,
                'command' => 'edit',
                'recordTypeValue' => $row['CType'],
                'dataStructureIdentifier' => $dataStructureIdentifier,
                'flexFormSheetName' => 'sDEF',
                'flexFormFieldName' => $flexFormFieldName,
                'flexFormContainerName' => '',
                'flexFormContainerIdentifier' => '',
                'flexFormContainerFieldName' => '',
                'flexFormSectionContainerIsNew' => '',
            ]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $response = GeneralUtility::makeInstance(FormSelectTreeAjaxController::class)->fetchDataAction($request);
        $nodes = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($nodes);

        $uids = [];
        foreach ($nodes as $node) {
            $uid = (int)($node['identifier'] ?? 0);
            if ($uid > 0) {
                $uids[] = $uid;
            }
        }
        sort($uids);
        return $uids;
    }
}
