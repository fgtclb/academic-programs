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

/**
 * Processor class for program page types
 *
 * Adds the variables `program` and `facts`. `facts` are the facts of the program page, in
 * the order of the option `factsFields` - a comma-separated field list, see
 * {@see ProgramFactsBuilder}. The option takes the field list through the processor rather
 * than through `settings`, which a PAGEVIEW page object does not read.
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
        // Try to fetch page data for FLUIDTEMPLATE
        $pageData = $processedData['data'] ?? [];
        if ($pageData === []) {
            // If no page data is available in FLUIDTEMPLATE, try to fetch page data from PAGEVIEW
            $pageData = $processedData['page']->getPageRecord() ?? [];
        }
        if ($pageData !== []) {
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
            );
        }
        return $processedData;
    }
}
