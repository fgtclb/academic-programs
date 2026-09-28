<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Unit\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPrograms\Domain\Model\Dto\ProgramDemand;
use FGTCLB\AcademicPrograms\Domain\Model\Program;
use FGTCLB\AcademicPrograms\Event\ModifyProgramListEvent;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyProgramListEventTest extends UnitTestCase
{
    /**
     * @param QueryResultInterface<int, Program>|null $programs
     */
    private function event(
        ?QueryResultInterface $programs = null,
        ?CategoryCollection $categories = null,
        ?ProgramDemand $demand = null,
        ?ViewInterface $view = null,
        ?PluginControllerActionContextInterface $context = null,
    ): ModifyProgramListEvent {
        return new ModifyProgramListEvent(
            $programs ?? $this->createStub(QueryResultInterface::class),
            $categories ?? new CategoryCollection(),
            $demand ?? new ProgramDemand(),
            $view ?? $this->createStub(ViewInterface::class),
            $context ?? $this->createStub(PluginControllerActionContextInterface::class),
        );
    }

    #[Test]
    public function getProgramsReturnsInstanceSetInConstructor(): void
    {
        $programs = $this->createStub(QueryResultInterface::class);
        $this->assertSame($programs, $this->event(programs: $programs)->getPrograms());
    }

    #[Test]
    public function getProgramsReturnsTheResultAListenerReplacedItWith(): void
    {
        $replacement = $this->createStub(QueryResultInterface::class);
        $event = $this->event();
        $event->setPrograms($replacement);
        $this->assertSame($replacement, $event->getPrograms());
    }

    #[Test]
    public function getCategoriesReturnsInstanceSetInConstructor(): void
    {
        $categories = new CategoryCollection();
        $this->assertSame($categories, $this->event(categories: $categories)->getCategories());
    }

    #[Test]
    public function getCategoriesReturnsTheCollectionAListenerReplacedItWith(): void
    {
        $replacement = new CategoryCollection();
        $event = $this->event();
        $event->setCategories($replacement);
        $this->assertSame($replacement, $event->getCategories());
    }

    #[Test]
    public function getDemandReturnsInstanceSetInConstructor(): void
    {
        $demand = new ProgramDemand();
        $this->assertSame($demand, $this->event(demand: $demand)->getDemand());
    }

    #[Test]
    public function getViewReturnsInstanceSetInConstructor(): void
    {
        $view = $this->createStub(ViewInterface::class);
        $this->assertSame($view, $this->event(view: $view)->getView());
    }

    #[Test]
    public function getPluginControllerActionContextReturnsInstanceSetInConstructor(): void
    {
        $context = $this->createStub(PluginControllerActionContextInterface::class);
        $this->assertSame($context, $this->event(context: $context)->getPluginControllerActionContext());
    }
}
