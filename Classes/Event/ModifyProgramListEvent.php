<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPrograms\Controller\ProgramController;
use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Domain\Model\Program;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidViewInterface;

/**
 * Dispatched in {@see ProgramController::listAction()} and {@see ProgramController::finderAction()}
 * after the query and before the view variables are assigned. A listener replaces the
 * programs, replaces the applicable categories, or assigns variables of its own to the
 * view.
 *
 * The categories are the ones computed from the queried programs. They are not recomputed
 * after the event, so a listener that replaces the programs and wants the filter to match
 * them sets the categories as well. The finder renders no program: its selects offer the
 * categories, and its preselection is read from them.
 *
 * @api
 */
final class ModifyProgramListEvent
{
    /**
     * @param QueryResultInterface<int, Program> $programs
     */
    public function __construct(
        private QueryResultInterface $programs,
        private CategoryCollection $categories,
        private readonly ProgramDemand $demand,
        private readonly FluidViewInterface|CoreViewInterface $view,
        private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
    ) {}

    /**
     * @return QueryResultInterface<int, Program>
     */
    public function getPrograms(): QueryResultInterface
    {
        return $this->programs;
    }

    /**
     * @param QueryResultInterface<int, Program> $programs
     */
    public function setPrograms(QueryResultInterface $programs): void
    {
        $this->programs = $programs;
    }

    public function getCategories(): CategoryCollection
    {
        return $this->categories;
    }

    public function setCategories(CategoryCollection $categories): void
    {
        $this->categories = $categories;
    }

    public function getDemand(): ProgramDemand
    {
        return $this->demand;
    }

    public function getView(): FluidViewInterface|CoreViewInterface
    {
        return $this->view;
    }

    public function getPluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }
}
