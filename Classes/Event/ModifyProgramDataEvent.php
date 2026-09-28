<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Event;

use FGTCLB\AcademicPrograms\DataProcessing\ProgramDataProcessor;
use FGTCLB\AcademicPrograms\Domain\Model\ProgramData;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dispatched in {@see ProgramDataProcessor::process()} once the data of a program page is
 * built from its page record, and before the facts of the page are built from it. The data
 * a listener hands back is the one the page template receives as `program`, and the facts
 * show it.
 *
 * @api
 */
final class ModifyProgramDataEvent
{
    /**
     * @param array<string, mixed> $pageRecord
     */
    public function __construct(
        private ProgramData $program,
        private readonly array $pageRecord,
        private readonly ServerRequestInterface $request,
    ) {}

    public function getProgram(): ProgramData
    {
        return $this->program;
    }

    public function setProgram(ProgramData $program): void
    {
        $this->program = $program;
    }

    /**
     * The record of the program page the data was built from.
     *
     * @return array<string, mixed>
     */
    public function getPageRecord(): array
    {
        return $this->pageRecord;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
