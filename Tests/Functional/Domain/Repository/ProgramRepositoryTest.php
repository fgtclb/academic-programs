<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicBase\Environment\EnvironmentBuilderFactoryInterface;
use FGTCLB\AcademicBase\Environment\StateBuildContext;
use FGTCLB\AcademicBase\Environment\StateManagerInterface;
use FGTCLB\AcademicPrograms\Domain\Model\Program;
use FGTCLB\AcademicPrograms\Domain\Repository\ProgramRepository;
use FGTCLB\AcademicPrograms\Factory\DemandFactory;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;

final class ProgramRepositoryTest extends AbstractAcademicProgramsTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
            'iso' => 'en',
            'hrefLang' => 'en-US',
            'direction' => '',
        ],
        'DE' => [
            'id' => 1,
            'title' => 'Deutsch',
            'locale' => 'de_DE.UTF8',
            'iso' => 'de',
            'hrefLang' => 'de-DE',
            'direction' => '',
        ],
        'FR' => [
            'id' => 2,
            'title' => 'French',
            'locale' => 'fr_FR.UTF8',
            'iso' => 'fr',
            'hrefLang' => 'fr-FR',
            'direction' => '',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withDatabaseSnapshot(function () {
            $this->importCSVDataSet(__DIR__ . '/Fixtures/ProgramRepository/be_users.csv');
            $backendUser = $this->setUpBackendUser(1);
            $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
            $scenarioFile = __DIR__ . '/Fixtures/ProgramRepository/scenario.yaml';
            $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
            $writer = DataHandlerWriter::withBackendUser($backendUser);
            $writer->invokeFactory($factory);
            static::failIfArrayIsNotEmpty($writer->getErrors());
        });
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @param string $identifierFieldName
     * @return array<int, array<string, mixed>>
     */
    private function prepareRecords(array $records, string $identifierFieldName): array
    {
        /** @var array<int, array<string, mixed>> $return */
        $return = [];
        foreach ($records as $record) {
            if (!isset($record[$identifierFieldName])) {
                continue;
            }
            $return[$record[$identifierFieldName]] = $record;
        }
        return $return;
    }

    /**
     * @param array<int, array<string, mixed>> $assertions
     * @param array<int, array<string, mixed>> $actualRecords
     * @param string $identifierFieldName
     */
    private function assertArrays(array $assertions, array $actualRecords, string $identifierFieldName = 'uid'): void
    {
        $assertions = array_map(fn($v) => array_map(fn(mixed $value) => is_array($value) ? json_encode($value) : $value, $v), $assertions);
        $records = $this->prepareRecords(array_map(fn($v) => array_map(fn(mixed $value) => is_array($value) ? json_encode($value) : $value, $v), $actualRecords), $identifierFieldName);
        $failMessages = [];
        foreach ($assertions as $assertion) {
            $result = $this->assertInRecords($assertion, $records);
            if ($result === false) {
                if (empty($records[$assertion[$identifierFieldName]])) {
                    $failMessages[] = 'Record "' . $assertion[$identifierFieldName] . '" not found in records.';
                    continue;
                }
                $additionalInformation = $this->renderRecords($assertion, $records[$assertion[$identifierFieldName]]);
                $failMessages[] = 'Assertion in data-set failed for "' . $assertion[$identifierFieldName] . '":' . LF . $additionalInformation;
                unset($records[$assertion[$identifierFieldName]]);
            } else {
                // Unset asserted record
                unset($records[$result]);
                // Increase assertion counter
                $this->assertTrue($result !== false);
            }
        }
        if (!empty($records)) {
            $fields = array_keys(reset($assertions) ?: reset($records) ?: []);
            foreach ($records as $record) {
                $emptyAssertion = array_fill_keys($fields, '[none');
                $reducesRecord = array_intersect_key($record, $emptyAssertion);
                $additionalInformation = $this->renderRecords($emptyAssertion, $reducesRecord);
                $failMessages[] = 'Not asserted record found for "' . $record[$identifierFieldName] . '":' . LF . $additionalInformation;
            }
        }
        if (!empty($failMessages)) {
            $this->fail(implode(LF, $failMessages));
        }
    }

    /**
     * Sets up a root-page containing TypoScript settings for frontend testing.
     *
     * Parameter `$typoScriptFiles` can either be
     * + `[
     *      'EXT:extension/path/first.typoscript',
     *      'EXT:extension/path/second.typoscript'
     *    ]`
     *   which just loads files to the setup setion of the TypoScript template
     *   record (legacy behavior of this method)
     * + `[
     *      'constants' => ['EXT:extension/path/constants.typoscript'],
     *      'setup' => ['EXT:extension/path/setup.typoscript']
     *    ]`
     *   which allows to define contents for the `constants` and `setup` part
     *   of the TypoScript template record at the same time
     *
     * @param int $pageId
     * @param non-empty-string $identifier
     * @param array{constants?: string[], setup?: string[]}|string[] $typoScriptFiles
     * @param array<string, mixed> $templateValues
     * @param bool $createSysTemplateRecord TRUE if sys_template record should be created, FALSE does not create one
     *                                      but removes an existing one.
     * @param non-empty-string[] $germanFallbackIdentifiers
     * @param non-empty-string[] $frenchFallbackIdentifiers
     * @param non-empty-string $germanFallbackType
     * @param non-empty-string $frenchFallbackType
     * @param non-empty-string $siteConfigBase
     * @param non-empty-string $defaultLanguageBase
     * @param non-empty-string $germanLanguageBase
     * @param non-empty-string $frenchLanguageBase
     */
    private function prepareFrontendSiteConfiguration(
        int $pageId,
        string $identifier = 'website-local',
        array $typoScriptFiles = [],
        array $templateValues = [],
        bool $createSysTemplateRecord = true,
        // Site configuration language fallback identifiers
        array $germanFallbackIdentifiers = [],
        array $frenchFallbackIdentifiers = [],
        // Options: strict, fallback
        string $germanFallbackType = 'strict',
        string $frenchFallbackType = 'strict',
        // Language base urls
        string $siteConfigBase = 'https://website.local',
        string $defaultLanguageBase = '/',
        string $germanLanguageBase = '/de/',
        string $frenchLanguageBase = '/fr/',
    ): void {
        $this->setUpFrontendRootPage(
            pageId: $pageId,
            typoScriptFiles: $typoScriptFiles,
            templateValues: $templateValues,
            createSysTemplateRecord: $createSysTemplateRecord,
        );
        $this->writeSiteConfiguration(
            identifier: $identifier,
            site: $this->buildSiteConfiguration($pageId, $siteConfigBase),
            languages: [
                $this->buildDefaultLanguageConfiguration(
                    identifier: 'EN',
                    base: $defaultLanguageBase,
                ),
                $this->buildLanguageConfiguration(
                    identifier: 'DE',
                    base: $germanLanguageBase,
                    fallbackIdentifiers: $germanFallbackIdentifiers,
                    fallbackType: $germanFallbackType,
                ),
                $this->buildLanguageConfiguration(
                    identifier: 'FR',
                    base: $frenchLanguageBase,
                    fallbackIdentifiers: $frenchFallbackIdentifiers,
                    fallbackType: $frenchFallbackType,
                ),
            ],
        );
    }

    /**
     * @param QueryResult<Program> $result
     * @param int $expectedCount
     * @return array<int, array{
     *     uid: int|null,
     *     _localizedUid: mixed,
     *     _languageUid: mixed,
     *     pid: int|null,
     *     title: string,
     *     categories: array<string, array<int, array{
     *          identifier: int,
     *          parentId: int,
     *          title: string,
     *     }>>
     * }>
     */
    private function transformExtbaseQueryResultToArray(
        QueryResult $result,
        int $expectedCount = 0,
    ): array {
        $resultItems = $result->toArray();
        $this->assertCount($expectedCount, $resultItems);
        if ($result->count() === 0) {
            return [];
        }
        /** @var array<int, array{
         *  uid: int|null,
         *  _localizedUid: mixed,
         *  _languageUid: mixed,
         *  pid: int|null,
         *  title: string,
         *  categories: array<string, array<int, array{
         *      identifier: int,
         *      parentId: int,
         *      title: string,
         *  }>>
         * }> $return */
        $return = [];
        foreach ($resultItems as $item) {
            if ($item instanceof Program) {
                $entry = [
                    'uid' => $item->getUid(),
                    '_localizedUid' => $item->_getProperty('_localizedUid'),
                    '_languageUid' => $item->_getProperty('_languageUid'),
                    'pid' => $item->getPid(),
                    'title' => $item->getTitle(),
                    'categories' => [],
                ];
                foreach ($item->getCategoryCollection()->getAllCategoriesByType() as $typeIdentifier => $typeItems) {
                    foreach ($typeItems as $categoryIdentifier => $category) {
                        $entry['categories'][$typeIdentifier] ??= [];
                        $entry['categories'][$typeIdentifier][$categoryIdentifier] = [
                            'identifier' => $categoryIdentifier,
                            'parentId' => $category->getParentId(),
                            'title' => $category->getTitle(),
                        ];
                        ksort($entry['categories'][$typeIdentifier]);
                    }
                }
                ksort($entry['categories']);
                $return[] = $entry;
            }
        }
        return $return;
    }

    private function setUpContextLanguageAspectForSiteLanguage(
        string $siteIdentifier,
        int $languageId,
    ): void {
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByIdentifier($siteIdentifier);
        $siteLanguage = $site->getLanguageById($languageId);
        $stateBuildContext = new StateBuildContext(
            applicationType: ApplicationType::FRONTEND,
            pageId: $site->getRootPageId(),
            languageId: $siteLanguage->getLanguageId(),
        );
        GeneralUtility::makeInstance(StateManagerInterface::class)->apply(
            GeneralUtility::makeInstance(EnvironmentBuilderFactoryInterface::class)
                ->create($stateBuildContext)
                ->build($stateBuildContext)
        );
    }

    public static function fetchByDemandReturnsExpectedResultDataSets(): \Generator
    {
        $defaultRecordDataset = __DIR__ . '/Fixtures/ProgramRepository/programs_basic_set.csv';
        yield '#1 without filter for languageId[0] - fallback[strict]' => [
            'languageId' => 0,
            'importDataSets' => [
                $defaultRecordDataset,
            ],
            'demandFactoryData' => [
                'demandFromForm' => [],
                'settings' => [],
                'contentElementData' => [
                    'pages' => '10000',
                ],
            ],
            'createSysTemplateRecord' => true,
            'germanFallbackIdentifiers' => [],
            'germanFallbackType' => 'strict',
            'frenchFallbackIdentifiers' => [],
            'frenchFallbackType' => 'strict',
            'expectedRecords' => [
                0 => [
                    'uid' => 11000,
                    '_localizedUid' => 11000,
                    '_languageUid' => 0,
                    'pid' => 10000,
                    'title' => '[EN] Program 1',
                    'categories' =>
                        [
                            'program_type' => [
                                20 => [
                                    'identifier' => 20,
                                    'parentId' => 10,
                                    'title' => '[EN][ACE] program-type-1',
                                ],
                            ],
                        ],
                ],
                1 => [
                    'uid' => 11010,
                    '_localizedUid' => 11010,
                    '_languageUid' => 0,
                    'pid' => 10000,
                    'title' => '[EN] Program 2',
                    'categories' => [
                        'program_type' => [
                            20 => [
                                'identifier' => 20,
                                'parentId' => 10,
                                'title' => '[EN][ACE] program-type-1',
                            ],
                            30 => [
                                'identifier' => 30,
                                'parentId' => 10,
                                'title' => '[EN][ACE] program-type-2',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield '#2 without filter for languageId[1] fallback[strict]' => [
            'languageId' => 1,
            'importDataSets' => [
                $defaultRecordDataset,
            ],
            'demandFactoryData' => [
                'demandFromForm' => [],
                'settings' => [],
                'contentElementData' => [
                    'pages' => '10000',
                ],
            ],
            'createSysTemplateRecord' => true,
            'germanFallbackIdentifiers' => [],
            'germanFallbackType' => 'strict',
            'frenchFallbackIdentifiers' => [],
            'frenchFallbackType' => 'strict',
            'expectedRecords' => [
                0 => [
                    'uid' => 11000,
                    '_localizedUid' => 11001,
                    '_languageUid' => 1,
                    'pid' => 10000,
                    'title' => '[DE] Program 1',
                    'categories' =>
                        [
                            'program_type' => [
                                20 => [
                                    'identifier' => 20,
                                    'parentId' => 10,
                                    'title' => '[DE][ACE] program-type-1',
                                ],
                            ],
                        ],
                ],
                1 => [
                    'uid' => 11010,
                    '_localizedUid' => 11011,
                    '_languageUid' => 1,
                    'pid' => 10000,
                    'title' => '[DE] Program 2',
                    'categories' => [
                        'program_type' => [
                            20 => [
                                'identifier' => 20,
                                'parentId' => 10,
                                'title' => '[DE][ACE] program-type-1',
                            ],
                            30 => [
                                'identifier' => 30,
                                'parentId' => 10,
                                'title' => '[EN][ACE] program-type-2',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield '#3 without filter for languageId[2] fallback[strict]' => [
            'languageId' => 2,
            'importDataSets' => [
                $defaultRecordDataset,
            ],
            'demandFactoryData' => [
                'demandFromForm' => [],
                'settings' => [],
                'contentElementData' => [
                    'pages' => '10000',
                ],
            ],
            'createSysTemplateRecord' => true,
            'germanFallbackIdentifiers' => [],
            'germanFallbackType' => 'strict',
            'frenchFallbackIdentifiers' => [],
            'frenchFallbackType' => 'strict',
            'expectedRecords' => [
                0 => [
                    'uid' => 11000,
                    '_localizedUid' => 11002,
                    '_languageUid' => 2,
                    'pid' => 10000,
                    'title' => '[FR] Program 1',
                    'categories' =>
                        [
                            'program_type' => [
                                20 => [
                                    'identifier' => 20,
                                    'parentId' => 10,
                                    'title' => '[FR][ACE] program-type-1',
                                ],
                            ],
                        ],
                ],
            ],
        ];
        // with filter and fallback[strict] for secondary languages
        yield '#4 program-type-2[default-lang] only for languageId[0] fallback[strict]' => [
            'languageId' => 0,
            'importDataSets' => [
                $defaultRecordDataset,
            ],
            'demandFactoryData' => [
                'demandFromForm' => [
                    'filterCollection' => [
                        'program_type' => implode(',', array_filter([
                            30, // program-type-2
                        ])),
                    ],
                    'contentElementData' => [
                        'pages' => '10000',
                    ],
                ],
                'settings' => [],
                'contentElementData' => [],
            ],
            'createSysTemplateRecord' => true,
            'germanFallbackIdentifiers' => [],
            'germanFallbackType' => 'strict',
            'frenchFallbackIdentifiers' => [],
            'frenchFallbackType' => 'strict',
            'expectedRecords' => [
                0 => [
                    'uid' => 11010,
                    '_localizedUid' => 11010,
                    '_languageUid' => 0,
                    'pid' => 10000,
                    'title' => '[EN] Program 2',
                    'categories' => [
                        'program_type' => [
                            20 => [
                                'identifier' => 20,
                                'parentId' => 10,
                                'title' => '[EN][ACE] program-type-1',
                            ],
                            30 => [
                                'identifier' => 30,
                                'parentId' => 10,
                                'title' => '[EN][ACE] program-type-2',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield '#5 program-type-2[default-lang] only for languageId[1] fallback[strict]' => [
            'languageId' => 1,
            'importDataSets' => [
                $defaultRecordDataset,
            ],
            'demandFactoryData' => [
                'demandFromForm' => [
                    'filterCollection' => [
                        'program_type' => implode(',', array_filter([
                            30, // program-type-2
                        ])),
                    ],
                    'contentElementData' => [
                        'pages' => '10000',
                    ],
                ],
                'settings' => [],
                'contentElementData' => [],
            ],
            'createSysTemplateRecord' => true,
            'germanFallbackIdentifiers' => [],
            'germanFallbackType' => 'strict',
            'frenchFallbackIdentifiers' => [],
            'frenchFallbackType' => 'strict',
            'expectedRecords' => [
                0 => [
                    'uid' => 11010,
                    '_localizedUid' => 11011,
                    '_languageUid' => 1,
                    'pid' => 10000,
                    'title' => '[DE] Program 2',
                    'categories' => [
                        'program_type' => [
                            20 => [
                                'identifier' => 20,
                                'parentId' => 10,
                                'title' => '[DE][ACE] program-type-1',
                            ],
                            30 => [
                                'identifier' => 30,
                                'parentId' => 10,
                                'title' => '[EN][ACE] program-type-2',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield '#6 program-type-2[default-lang] only for languageId[1] fallback[strict]' => [
            'languageId' => 2,
            'importDataSets' => [
                $defaultRecordDataset,
            ],
            'demandFactoryData' => [
                'demandFromForm' => [
                    'filterCollection' => [
                        'program_type' => implode(',', array_filter([
                            30, // program-type-2
                        ])),
                    ],
                    'contentElementData' => [
                        'pages' => '10000',
                    ],
                ],
                'settings' => [],
                'contentElementData' => [],
            ],
            'createSysTemplateRecord' => true,
            'germanFallbackIdentifiers' => [],
            'germanFallbackType' => 'strict',
            'frenchFallbackIdentifiers' => [],
            'frenchFallbackType' => 'strict',
            // No expected result, strict mode set but localized program page for FR does not exist in import dataset.
            'expectedRecords' => [],
        ];
        // with filter and fallback[strict] for secondary languages
        yield '#7 program-type-2[default-lang] only for languageId[0] fallback[EN-strict,FR-fallback-to-EN]' => [
            'languageId' => 0,
            'importDataSets' => [
                $defaultRecordDataset,
            ],
            'demandFactoryData' => [
                'demandFromForm' => [
                    'filterCollection' => [
                        'program_type' => implode(',', array_filter([
                            30, // program-type-2
                        ])),
                    ],
                    'contentElementData' => [
                        'pages' => '10000',
                    ],
                ],
                'settings' => [],
                'contentElementData' => [],
            ],
            'createSysTemplateRecord' => true,
            'germanFallbackIdentifiers' => [],
            'germanFallbackType' => 'strict',
            'frenchFallbackIdentifiers' => ['EN'],
            'frenchFallbackType' => 'fallback',
            'expectedRecords' => [
                0 => [
                    'uid' => 11010,
                    '_localizedUid' => 11010,
                    '_languageUid' => 0,
                    'pid' => 10000,
                    'title' => '[EN] Program 2',
                    'categories' => [
                        'program_type' => [
                            20 => [
                                'identifier' => 20,
                                'parentId' => 10,
                                'title' => '[EN][ACE] program-type-1',
                            ],
                            30 => [
                                'identifier' => 30,
                                'parentId' => 10,
                                'title' => '[EN][ACE] program-type-2',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield '#8 program-type-2[default-lang] only for languageId[1] fallback[EN-strict,FR-fallback-to-EN]' => [
            'languageId' => 1,
            'importDataSets' => [
                $defaultRecordDataset,
            ],
            'demandFactoryData' => [
                'demandFromForm' => [
                    'filterCollection' => [
                        'program_type' => implode(',', array_filter([
                            30, // program-type-2
                        ])),
                    ],
                    'contentElementData' => [
                        'pages' => '10000',
                    ],
                ],
                'settings' => [],
                'contentElementData' => [],
            ],
            'createSysTemplateRecord' => true,
            'germanFallbackIdentifiers' => [],
            'germanFallbackType' => 'strict',
            'frenchFallbackIdentifiers' => ['EN'],
            'frenchFallbackType' => 'fallback',
            'expectedRecords' => [
                0 => [
                    'uid' => 11010,
                    '_localizedUid' => 11011,
                    '_languageUid' => 1,
                    'pid' => 10000,
                    'title' => '[DE] Program 2',
                    'categories' => [
                        'program_type' => [
                            20 => [
                                'identifier' => 20,
                                'parentId' => 10,
                                'title' => '[DE][ACE] program-type-1',
                            ],
                            30 => [
                                'identifier' => 30,
                                'parentId' => 10,
                                'title' => '[EN][ACE] program-type-2',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield '#9 program-type-2[default-lang] only for languageId[2] fallback[EN-strict,FR-fallback-to-EN]' => [
            'languageId' => 2,
            'importDataSets' => [
                $defaultRecordDataset,
            ],
            'demandFactoryData' => [
                'demandFromForm' => [
                    'filterCollection' => [
                        'program_type' => implode(',', array_filter([
                            30, // program-type-2
                        ])),
                    ],
                    'contentElementData' => [
                        'pages' => '10000',
                    ],
                ],
                'settings' => [],
                'contentElementData' => [],
            ],
            'createSysTemplateRecord' => true,
            'germanFallbackIdentifiers' => [],
            'germanFallbackType' => 'strict',
            'frenchFallbackIdentifiers' => ['EN'],
            'frenchFallbackType' => 'fallback',
            'expectedRecords' => [
                0 => [
                    'uid' => 11010,
                    '_localizedUid' => 11010,
                    '_languageUid' => 0,
                    'pid' => 10000,
                    'title' => '[EN] Program 2',
                    'categories' =>
                        [
                            'program_type' => [
                                20 => [
                                    'identifier' => 20,
                                    'parentId' => 10,
                                    'title' => '[FR][ACE] program-type-1',
                                ],
                                30 => [
                                    'identifier' => 30,
                                    'parentId' => 10,
                                    'title' => '[FR][ACE] program-type-2',
                                ],
                            ],
                        ],
                ],
            ],
        ];
    }

    /**
     * @param string[] $importDataSets
     * @param array{
     *     demandFromForm: array{
     *         filterCollectiom?: int[],
     *         contentElementData: array<string, mixed>,
     *     }|null,
     *     settings: array<string, mixed>,
     *     contentElementData: array<string, mixed>,
     * } $demandFactoryData
     * @param non-empty-string[] $germanFallbackIdentifiers
     * @param non-empty-string $germanFallbackType
     * @param non-empty-string[] $frenchFallbackIdentifiers
     * @param non-empty-string $frenchFallbackType
     * @param array<int, array<string, mixed>> $expectedRecords
     */
    #[DataProvider(methodName: 'fetchByDemandReturnsExpectedResultDataSets')]
    #[Test]
    public function fetchByDemanReturnsExpectedResult(
        int $languageId,
        array $importDataSets,
        array $demandFactoryData,
        bool $createSysTemplateRecord,
        array $germanFallbackIdentifiers,
        string $germanFallbackType,
        array $frenchFallbackIdentifiers,
        string $frenchFallbackType,
        array $expectedRecords,
    ): void {
        $expectedCount = count($expectedRecords);
        $siteIdentifier = 'website-local';
        $this->prepareFrontendSiteConfiguration(
            pageId: 1000,
            identifier: $siteIdentifier,
            templateValues: [
                'config' => <<<ENDOFCONFIG
page = PAGE
page.10 = TEXT
page.10.value = Hello World!
ENDOFCONFIG,
            ],
            createSysTemplateRecord: $createSysTemplateRecord,
            germanFallbackIdentifiers: $germanFallbackIdentifiers,
            frenchFallbackIdentifiers: $frenchFallbackIdentifiers,
            germanFallbackType: $germanFallbackType,
            frenchFallbackType: $frenchFallbackType,
        );
        $this->setUpContextLanguageAspectForSiteLanguage($siteIdentifier, $languageId);
        foreach ($importDataSets as $importDataSet) {
            $this->importCSVDataSet($importDataSet);
        }
        $demandFactory = GeneralUtility::makeInstance(DemandFactory::class);
        $programRepository = GeneralUtility::makeInstance(ProgramRepository::class);
        $demand = $demandFactory->createDemandObject(...$demandFactoryData);
        $result = $programRepository->findByDemand($demand);
        $resultRecords = $this->transformExtbaseQueryResultToArray($result, $expectedCount);
        $this->assertArrays($expectedRecords, $resultRecords, '_localizedUid');
    }

    /**
     * @todo debug helper, must be removed before merge
     * @return array<int, array<string, mixed>>
     */
    public function debugGetPageRecords(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('uid', 'pid', 'doktype', 'title', 'subtitle', 'sys_language_uid')
            ->from('pages')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
