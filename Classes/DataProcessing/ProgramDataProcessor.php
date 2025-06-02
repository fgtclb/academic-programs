<?php

namespace FGTCLB\AcademicPrograms\DataProcessing;

use FGTCLB\AcademicPrograms\Factory\ProgramDataFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Processor class for program page types
 */
class ProgramDataProcessor implements DataProcessorInterface
{
    /**
     * Make program data accessable in Fluid
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array<string, mixed> $contentObjectConfiguration The configuration of Content Object
     * @param array<string, mixed> $processorConfiguration The configuration of this processor
     * @param array<string, mixed> $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array<string, mixed> the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        // Try to fetch page data for FLUIDTEMPLATE
        $pageData = $processedData['data'] ?? [];
        if ($pageData === []) {
            // If no page data is available in FLUIDTEMPLATE, try to fetch page data from PAGEVIEW
            $pageData = $processedData['page']->getPageRecord() ?? [];
        }
        if ($pageData !== []) {
            $programDataFactory = GeneralUtility::makeInstance(ProgramDataFactory::class);
            $processedData['program'] = $programDataFactory->get($pageData);
        }
        return $processedData;
    }
}
