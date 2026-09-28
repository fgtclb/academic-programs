<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Unit\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Event\ModifyProgramDemandEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyProgramDemandEventTest extends UnitTestCase
{
    #[Test]
    public function getDemandReturnsInstanceSetInConstructor(): void
    {
        $demand = new ProgramDemand();
        $event = new ModifyProgramDemandEvent($demand, $this->createStub(PluginControllerActionContextInterface::class));
        $this->assertSame($demand, $event->getDemand());
    }

    #[Test]
    public function getDemandReturnsTheDemandAListenerReplacedItWith(): void
    {
        $replacement = new ProgramDemand();
        $event = new ModifyProgramDemandEvent(
            new ProgramDemand(),
            $this->createStub(PluginControllerActionContextInterface::class),
        );
        $event->setDemand($replacement);
        $this->assertSame($replacement, $event->getDemand());
    }

    #[Test]
    public function getPluginControllerActionContextReturnsInstanceSetInConstructor(): void
    {
        $context = $this->createStub(PluginControllerActionContextInterface::class);
        $event = new ModifyProgramDemandEvent(new ProgramDemand(), $context);
        $this->assertSame($context, $event->getPluginControllerActionContext());
    }
}
