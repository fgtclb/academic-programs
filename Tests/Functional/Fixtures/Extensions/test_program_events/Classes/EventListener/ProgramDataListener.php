<?php

declare(strict_types=1);

namespace TESTS\TestProgramEvents\EventListener;

use FGTCLB\AcademicPrograms\Event\ModifyProgramDataEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;

/**
 * Changes the data of a program page. A data processor has no plugin settings, so the
 * listener reads its switches from the TypoScript setup of the request, below
 * `plugin.tx_academicprograms.settings`, and stays inert without them.
 *
 * It hands a copy back through `setProgram()` rather than changing the data it was handed,
 * so a processor that kept its own data would not pick it up.
 */
final class ProgramDataListener
{
    #[AsEventListener(identifier: 'test-program-events/data')]
    public function __invoke(ModifyProgramDataEvent $event): void
    {
        $typoScript = $event->getRequest()->getAttribute('frontend.typoscript');
        if (!$typoScript instanceof FrontendTypoScript) {
            return;
        }
        $settings = $typoScript->getSetupArray()['plugin.']['tx_academicprograms.']['settings.'] ?? [];

        $subtitle = (string)($settings['testProgramDataSubtitle'] ?? '');
        $creditPoints = (int)($settings['testProgramDataCreditPoints'] ?? 0);
        if ($subtitle === '' && $creditPoints === 0) {
            return;
        }

        $program = clone $event->getProgram();
        if ($subtitle !== '') {
            $program->setSubtitle($subtitle);
        }
        if ($creditPoints > 0) {
            $program->setCreditPoints($creditPoints);
        }
        $event->setProgram($program);
    }
}
