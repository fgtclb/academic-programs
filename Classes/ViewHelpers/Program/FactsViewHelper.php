<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\ViewHelpers\Program;

use FGTCLB\AcademicPrograms\Domain\Model\ProgramFact;
use FGTCLB\AcademicPrograms\Domain\Model\ProgramFactsSourceInterface;
use FGTCLB\AcademicPrograms\Enumeration\ProgramFactsPlace;
use FGTCLB\AcademicPrograms\Service\ProgramFactsBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * The facts of one program, for a template that renders several programs - the program
 * card of the list:
 *
 * ```html
 * <html xmlns:ace="http://typo3.org/ns/FGTCLB/AcademicPrograms/ViewHelpers" data-namespace-typo3-fluid="true">
 *
 * <f:variable name="facts" value="{ace:program.facts(program: program, fields: settings.card.fields)}" />
 * <f:render partial="Program/Facts" arguments="{facts: facts}" />
 * ```
 *
 * `place` is `card` unless given, and decides what an empty `fields` stands for - see
 * {@see ProgramFactsBuilder}. The program page and the details content element get their
 * facts from the data processor and the controller instead.
 */
final class FactsViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly ProgramFactsBuilder $programFactsBuilder,
    ) {}

    public function initializeArguments(): void
    {
        // Not required, so a template handing in no program renders no fact on Fluid 4 and 5 alike.
        $this->registerArgument('program', ProgramFactsSourceInterface::class, 'The program whose facts are built.', false);
        $this->registerArgument('fields', 'string', 'The comma-separated field list, "settings.card.fields" for the card.', false, '');
        $this->registerArgument('place', 'string', 'One of "page", "details" and "card"; any other value is read as "card".', false, ProgramFactsPlace::Card->value);
    }

    /**
     * @return list<ProgramFact>
     */
    public function render(): array
    {
        $program = $this->arguments['program'] ?? null;
        if (!$program instanceof ProgramFactsSourceInterface) {
            return [];
        }
        return $this->programFactsBuilder->build(
            $program,
            (string)($this->arguments['fields'] ?? ''),
            ProgramFactsPlace::tryFrom((string)$this->arguments['place']) ?? ProgramFactsPlace::Card,
        );
    }
}
