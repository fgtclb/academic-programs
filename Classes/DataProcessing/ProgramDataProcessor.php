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
        // The page record: the one of the page information object "page" a PAGEVIEW page
        // object assigns on TYPO3 v13, or "data" of a FLUIDTEMPLATE page object. "page"
        // first, because PAGEVIEW reserves that name, while a PAGEVIEW site package may
        // assign a "data" of its own, even an array that is not the page record. The class
        // of the page information object does not exist on TYPO3 v12, so the object is
        // recognised by its method.
        $page = $processedData['page'] ?? null;
        $pageData = is_object($page) && method_exists($page, 'getPageRecord')
            ? $page->getPageRecord()
            : ($processedData['data'] ?? []);
        if (is_array($pageData) && $pageData !== []) {
            $programDataFactory = GeneralUtility::makeInstance(ProgramDataFactory::class);
            $processedData['program'] = $programDataFactory->get($pageData);
        }
        return $processedData;
    }
}
