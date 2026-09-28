<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPrograms\Controller\ProgramController;
use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;

/**
 * Dispatched in {@see ProgramController::listAction()} and {@see ProgramController::finderAction()}
 * after the demand has been built from the content element settings and the request, and
 * before the programs are queried. The demand a listener hands back is the one that is
 * queried, and in the list the one assigned to the view as `demand`.
 *
 * The categories of the demand are the selected ones. When the element includes
 * subcategories, the repository widens each of them by its subcategories when it queries,
 * so a category a listener adds is widened as well.
 *
 * @api
 */
final class ModifyProgramDemandEvent
{
    public function __construct(
        private ProgramDemand $demand,
        private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
    ) {}

    public function getDemand(): ProgramDemand
    {
        return $this->demand;
    }

    public function setDemand(ProgramDemand $demand): void
    {
        $this->demand = $demand;
    }

    public function getPluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }
}
