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
 *  10 = FGTCLB\EducationalCourse\DataProcessor\CourseCategoryProcessor
 *  10 {
 *    type = category-type-identifier
 *    storages = 13,15
 *    fields = title,link,images
 *    as = category
 *  }
 */
class CourseCategoryProcessor implements DataProcessorInterface
{
    protected ContentDataProcessor $contentDataProcessor;

    public function __construct()
    {
        $this->contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);
    }

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
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
        $fields = GeneralUtility::trimExplode(',', $fieldList);

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
            $recordContentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $recordContentObjectRenderer->start($record, $tableName, $request);
            $processedRecordVariables[$key] = ['data' => $record];
            $processedRecordVariables[$key] = $this->contentDataProcessor->process($recordContentObjectRenderer, $processorConfiguration, $processedRecordVariables[$key]);
        }

        $processedData[$targetVariableName] = $processedRecordVariables;

        return $processedData;
    }
}
