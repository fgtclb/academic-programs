<?php

declare(strict_types=1);

namespace TESTS\TestProgramEvents\EventListener;

use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Event\ModifyProgramDemandEvent;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Changes the program demand, driven by plugin settings so that one fixture extension
 * serves every scenario: a test includes the TypoScript file of the behaviour it wants and
 * the listener stays inert for every other test of the same class.
 *
 * Both behaviours hand a demand back through `setDemand()` rather than changing the one
 * they were handed, so a controller that kept its own demand would not pick them up.
 */
final class ProgramDemandListener
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    #[AsEventListener(identifier: 'test-program-events/demand')]
    public function __invoke(ModifyProgramDemandEvent $event): void
    {
        $settings = $event->getPluginControllerActionContext()->getSettings();

        if ((string)($settings['testProgramDemandReplaceWithFresh'] ?? '') === '1') {
            $replacement = new ProgramDemand();
            $replacement->setPages(GeneralUtility::intExplode(',', (string)($settings['testProgramDemandReplacementPages'] ?? ''), true));
            $event->setDemand($replacement);
            return;
        }

        $category = (int)($settings['testProgramDemandCategory'] ?? 0);
        if ($category > 0) {
            $restricted = clone $event->getDemand();
            $restricted->setFilterCollection(new FilterCollection(
                $this->categoryRepository->findByGroupAndUidList('programs', [$category]),
            ));
            $event->setDemand($restricted);
        }
    }
}
