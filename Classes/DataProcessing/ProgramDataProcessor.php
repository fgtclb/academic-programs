<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\DataProcessing;

use FGTCLB\AcademicPrograms\Enumeration\ProgramFactsPlace;
use FGTCLB\AcademicPrograms\Event\ModifyProgramDataEvent;
use FGTCLB\AcademicPrograms\Factory\ProgramDataFactory;
use FGTCLB\AcademicPrograms\Service\ProgramFactsBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Page\PageInformation;

/**
 * Processor class for program page types
 *
 * Adds the variables `program` and `facts`. `facts` are the facts of the program page, in
 * the order of the option `factsFields` - a comma-separated field list, see
 * {@see ProgramFactsBuilder}. With the option `factsMostSpecificOnly` set, a category is left
 * out of the facts when the program carries a descendant of it of the same type. The options
 * reach the processor directly rather than through `settings`, which a PAGEVIEW page object
 * does not read.
 *
 * {@see ModifyProgramDataEvent} lets a listener change the data before the facts are built
 * from it, so the facts show what the listener changed.
 */
class ProgramDataProcessor implements DataProcessorInterface
{
    public function __construct(
        private readonly ProgramFactsBuilder $programFactsBuilder,
        private readonly ProgramDataFactory $programDataFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

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
        // object assigns, or "data" of a FLUIDTEMPLATE page object. "page" first, because
        // PAGEVIEW reserves that name, while a PAGEVIEW site package may assign a "data" of
        // its own, even an array that is not the page record. The partner and project page
        // processors read it in the same order.
        $page = $processedData['page'] ?? null;
        $pageData = $page instanceof PageInformation ? $page->getPageRecord() : ($processedData['data'] ?? []);
        if (is_array($pageData) && $pageData !== []) {
            /** @var ModifyProgramDataEvent $event */
            $event = $this->eventDispatcher->dispatch(new ModifyProgramDataEvent(
                $this->programDataFactory->get($pageData),
                $pageData,
                $cObj->getRequest(),
            ));
            $program = $event->getProgram();
            $processedData['program'] = $program;
            $processedData['facts'] = $this->programFactsBuilder->build(
                $program,
                (string)$cObj->stdWrapValue('factsFields', $processorConfiguration),
                ProgramFactsPlace::Page,
                (bool)$cObj->stdWrapValue('factsMostSpecificOnly', $processorConfiguration),
            );
        }
        return $processedData;
    }
}
