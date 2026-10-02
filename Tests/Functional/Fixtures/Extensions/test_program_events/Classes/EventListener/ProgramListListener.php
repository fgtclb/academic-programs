<?php

declare(strict_types=1);

namespace TESTS\TestProgramEvents\EventListener;

use FGTCLB\AcademicPrograms\Event\ModifyProgramListEvent;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Changes the queried programs and the view, driven by plugin settings. See
 * {@see ProgramDemandListener} for why.
 */
final class ProgramListListener
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    #[AsEventListener(identifier: 'test-program-events/list')]
    public function __invoke(ModifyProgramListEvent $event): void
    {
        $context = $event->getPluginControllerActionContext();
        $settings = $context->getSettings();

        // An additional view variable, rendered by the template this fixture extension
        // ships, reporting the action and the number of queried programs.
        $marker = (string)($settings['testProgramListMarker'] ?? '');
        if ($marker !== '') {
            $event->getView()->assign('testProgramListMarker', sprintf(
                '%s|%s|%d',
                $marker,
                $context->getActionName() ?? '',
                count($event->getPrograms()->toArray()),
            ));
        }

        // Replaces the result with the same query in reverse order. The setter is typed to a
        // query result, so the reversal runs as a query rather than on the records.
        if ((string)($settings['testProgramListReverse'] ?? '') === '1') {
            $query = $event->getPrograms()->getQuery();
            $reversed = [];
            foreach ($query->getOrderings() as $field => $direction) {
                $reversed[$field] = $direction === QueryInterface::ORDER_ASCENDING
                    ? QueryInterface::ORDER_DESCENDING
                    : QueryInterface::ORDER_ASCENDING;
            }
            $query->setOrderings($reversed);
            $event->setPrograms($query->execute());
        }

        // Replaces the result with the same query without one program.
        $excluded = (int)($settings['testProgramListExclude'] ?? 0);
        if ($excluded > 0) {
            $query = $event->getPrograms()->getQuery();
            $constraint = $query->getConstraint();
            $without = $query->logicalNot($query->equals('uid', $excluded));
            $query->matching($constraint === null ? $without : $query->logicalAnd($constraint, $without));
            $event->setPrograms($query->execute());
        }

        // Replaces the applicable categories. The programs are left alone, which is what tells
        // the two setters apart.
        $category = (int)($settings['testProgramListCategory'] ?? 0);
        if ($category > 0) {
            $event->setCategories($this->categoryRepository->findByGroupAndUidList('programs', [$category]));
        }
    }
}
