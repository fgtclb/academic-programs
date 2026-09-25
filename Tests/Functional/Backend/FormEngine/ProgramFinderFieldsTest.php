<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The fields of the program finder as an editor gets them, and what saving them stores -
 * the values the finder reads in the frontend.
 *
 * The form is compiled the way FormEngine compiles it when the content element is opened,
 * see `docs/architecture/backend-select-items.md`.
 */
final class ProgramFinderFieldsTest extends AbstractAcademicProgramsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProgramFinderFields/records.csv');
        $this->setUpBackendUser(1);
    }

    /**
     * FormEngine refuses to save the element until one page is chosen. The DataHandler
     * does not check `required` on a relation field, so this is the only place it is
     * enforced, and the finder renders no form without a target page.
     */
    #[Test]
    public function theTargetPageIsARequiredSinglePage(): void
    {
        $field = $this->fieldConfiguration('settings.listPid');

        $this->assertSame('group', $field['type'] ?? null);
        $this->assertSame('pages', $field['allowed'] ?? null);
        $this->assertTrue((bool)($field['required'] ?? false));
        $this->assertSame(1, (int)($field['minitems'] ?? 0));
        $this->assertSame(1, (int)($field['maxitems'] ?? 0));
    }

    /**
     * The field of the list, with the same items: the types of the programs group.
     */
    #[Test]
    public function theFilterTypesAreTheTypesOfTheProgramsGroup(): void
    {
        $values = array_map(
            static fn(array $item): string => (string)$item['value'],
            $this->fieldConfiguration('settings.filter.categoryTypes')['items'] ?? [],
        );

        $this->assertContains('degree', $values);
        $this->assertContains('topic', $values);
        $this->assertNotContains('', $values);
    }

    /**
     * The preselection is a category field whose uids the FlexForm stores inline - no MM
     * table, which a FlexForm does not support - and it offers typed categories of the
     * default language only, like the default categories of the list.
     */
    #[Test]
    public function thePreselectionIsAnInlineListOfTypedCategories(): void
    {
        $field = $this->fieldConfiguration('settings.preselectedCategories');

        $this->assertSame('category', $field['type'] ?? null);
        $this->assertSame('sys_category', $field['foreign_table'] ?? null);
        $this->assertSame('oneToMany', $field['relationship'] ?? null);
        $this->assertArrayNotHasKey('MM', $field);
        $this->assertStringContainsString("{#sys_category}.{#type} != ''", (string)($field['foreign_table_where'] ?? ''));
    }

    /**
     * FormEngine submits the page of a relation field with its table name. What the
     * DataHandler stores, and what the finder reads, is the bare uid; the preselection is
     * a uid list in the order it is submitted, which the category tree of the field does
     * in tree order.
     */
    #[Test]
    public function savingStoresTheTargetPageUidAndThePreselection(): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tt_content' => [
                    1 => [
                        'pi_flexform' => [
                            'data' => [
                                'sDEF' => [
                                    'lDEF' => [
                                        'settings.listPid' => ['vDEF' => 'pages_3'],
                                        'settings.filter.categoryTypes' => ['vDEF' => ['topic', 'degree']],
                                        'settings.preselectedCategories' => ['vDEF' => '4,1'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [],
        );
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog);

        $row = $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->select(['pi_flexform'], 'tt_content', ['uid' => 1])
            ->fetchAssociative();
        $this->assertIsArray($row);
        $fields = GeneralUtility::xml2array((string)$row['pi_flexform'])['data']['sDEF']['lDEF'] ?? null;
        $this->assertIsArray($fields);

        $this->assertSame('3', $fields['settings.listPid']['vDEF'] ?? null);
        $this->assertSame('topic,degree', $fields['settings.filter.categoryTypes']['vDEF'] ?? null);
        $this->assertSame('4,1', $fields['settings.preselectedCategories']['vDEF'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldConfiguration(string $fieldName): array
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $result = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'tt_content',
                'vanillaUid' => 1,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        );
        $field = $result['processedTca']['columns']['pi_flexform']['config']['ds']['sheets']['sDEF']['ROOT']['el'][$fieldName]['config'] ?? null;
        $this->assertIsArray($field, sprintf('The finder has no field "%s".', $fieldName));

        return $field;
    }
}
