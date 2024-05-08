<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\DataProcessor;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Select sys_category with selected type find in storage folder
 *
 * Example TypoScript configuration:
 *
 *  10 = FGTCLB\EducationalCourse\DataProcessor\CategoryProcessor
 *  10 {
 *    type = category-type-identifier
 *    storages = 13,15
 *    fields = title,link,images
 *    as = category
 *  }
 */
class CategoryProcessor implements DataProcessorInterface
{
    public function __construct(
        protected ContentDataProcessor $contentDataProcessor
    ) {}

    /**
     * Process data
     *
     * @param ContentObjectRenderer $cObj
     * @param array<string, mixed> $contentObjectConfiguration
     * @param array<string, mixed> $processorConfiguration
     * @param array<string, mixed> $processedData
     * @return array<string, mixed>
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        if (!isset($processorConfiguration['type'])) {
            return $processedData;
        }

        if (!isset($processorConfiguration['storages'])) {
            return $processedData;
        }

        $type = (string)$processorConfiguration['type'];
        $storages = (string)$processorConfiguration['storages'];
        $tableName = 'sys_category';

        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'records');
        $fieldList = $cObj->stdWrapValue('fields', $processorConfiguration, 'title');
        $fields = GeneralUtility::trimExplode(',', (string)$fieldList);

        $fieldList = implode(', ', array_map(function (string $fieldName) use ($tableName) {
            return $tableName . '.' . $fieldName;
        }, $fields));

        $records = $cObj->getRecords($tableName, [
            'pidInList' => $storages . ',-1',
            'selectFields' => $fieldList,
            'join' => 'sys_category_record_mm ON sys_category_record_mm.uid_local = ' . $tableName . '.uid',
            'where.' => [
                'stdWrap.' => [
                    'cObject' => 'COA',
                    'cObject.' => [
                        '10' => 'TEXT',
                        '10.' => [
                            'data' => 'field:_ORIG_uid // field:uid',
                            'noTrimWrap' => '| sys_category_record_mm.uid_foreign=| AND |',
                        ],
                        '20' => 'TEXT',
                        '20.' => [
                            'value' => $tableName . '.type=\'' . $type . '\'' ,
                        ],
                    ],
                ],
                'noTrimWrap' => '| AND | |',
                'as' => '',
            ],
            'recursive' => 10,
        ]);

        $request = $cObj->getRequest();
        $processedRecordVariables = [];
        foreach ($records as $key => $record) {
            /** @var ContentObjectRenderer $recordContentObjectRenderer */
            $recordContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $recordContentObjectRenderer->start($record, $tableName, $request);
            $processedRecordVariables[(string)$key] = ['data' => $record];
            $processedRecordVariables[(string)$key] = $this->contentDataProcessor->process(
                $recordContentObjectRenderer,
                $processorConfiguration,
                $processedRecordVariables[$key]
            );
        }

        $processedData[(string)$targetVariableName] = $processedRecordVariables;

        return $processedData;
    }
}
