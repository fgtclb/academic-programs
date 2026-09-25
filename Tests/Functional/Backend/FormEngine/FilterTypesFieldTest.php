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
 * The field "Filter types" of the program list plugin as an editor gets it: the category
 * types of the programs group, including one a project adds and without one a project
 * removes, and the stored selection.
 *
 * The form is compiled the way FormEngine compiles it when the content element is opened,
 * see `docs/architecture/backend-select-items.md`.
 */
final class FilterTypesFieldTest extends AbstractAcademicProgramsTestCase
{
    private const FIELD = 'settings.filter.categoryTypes';

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/programs-extra-category-type';
        $this->testExtensionsToLoad[] = 'tests/programs-removed-category-type';
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FilterTypesField/records.csv');
        $this->setUpBackendUser(1);
    }

    /**
     * The shipped types in the order of `CategoryTypes.yaml`, the type a project adds at
     * the end, and no `costs`, which the other fixture removes.
     */
    #[Test]
    public function theFieldOffersTheTypesOfTheProgramsGroup(): void
    {
        $this->assertSame(
            [
                'admission_restriction' => 'Admission restriction',
                'application_period' => 'Application period',
                'begin_program' => 'Begin of program',
                'paying' => 'Paying?',
                'degree' => 'Degree',
                'department' => 'Department / Faculty',
                'standard_period' => 'Standard period',
                'location' => 'Location',
                'program_type' => 'Type of program',
                'teaching_language' => 'Teaching language',
                'topic' => 'Topic',
                'internship' => 'Internship Placement',
            ],
            $this->itemLabels($this->fieldConfiguration(1, 'default')),
        );
    }

    #[Test]
    public function theFieldOffersTheTypesByTheirTranslatedTitles(): void
    {
        $labels = $this->itemLabels($this->fieldConfiguration(1, 'de'));

        $this->assertSame('Abschluss', $labels['degree']);
        $this->assertSame('Standort', $labels['location']);
        // A literal title is shown as it is registered.
        $this->assertSame('Internship Placement', $labels['internship']);
    }

    #[Test]
    public function eachTypeIsOfferedWithItsIcon(): void
    {
        $icons = [];
        foreach ($this->fieldConfiguration(1, 'default')['items'] as $item) {
            $icons[(string)$item['value']] = (string)$item['icon'];
        }

        $this->assertSame('category_types.programs.degree', $icons['degree']);
        $this->assertSame('category_types.programs.internship', $icons['internship']);
    }

    #[Test]
    public function anElementSavedBeforeTheFieldExistedHasNoFilterTypes(): void
    {
        $this->assertSame([], $this->fieldValue(1));
    }

    /**
     * The selection keeps the order the editor chose. A type the project removed after the
     * element was saved is no item any more, so FormEngine leaves it out, and the next save
     * writes it away.
     */
    #[Test]
    public function theStoredSelectionKeepsItsOrderWithoutARemovedType(): void
    {
        $this->assertSame(['location', 'degree'], $this->fieldValue(2));
    }

    /**
     * The side by side select submits the selection in the order the editor arranged it,
     * and the DataHandler stores it in that order.
     */
    #[Test]
    public function savingTheFieldStoresTheSelectionInTheChosenOrder(): void
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
                                        self::FIELD => ['vDEF' => ['program_type', 'degree', 'location']],
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

        $this->assertSame(['program_type', 'degree', 'location'], $this->fieldValue(1));
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, string>
     */
    private function itemLabels(array $configuration): array
    {
        $labels = [];
        foreach ($configuration['items'] as $item) {
            $labels[(string)$item['value']] = (string)$item['label'];
        }

        return $labels;
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldConfiguration(int $contentUid, string $language): array
    {
        $field = $this->compile($contentUid, $language)['processedTca']['columns']['pi_flexform']['config']['ds']['sheets']['sDEF']['ROOT']['el'][self::FIELD]['config'] ?? null;
        $this->assertIsArray($field);
        $this->assertIsArray($field['items'] ?? null);

        return $field;
    }

    /**
     * @return list<string>
     */
    private function fieldValue(int $contentUid): array
    {
        $value = $this->compile($contentUid, 'default')['databaseRow']['pi_flexform']['data']['sDEF']['lDEF'][self::FIELD]['vDEF'] ?? null;
        $this->assertIsArray($value);

        return array_values(array_map(strval(...), $value));
    }

    /**
     * @return array<string, mixed>
     */
    private function compile(int $contentUid, string $language): array
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create($language);
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'tt_content',
                'vanillaUid' => $contentUid,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        );
    }
}
