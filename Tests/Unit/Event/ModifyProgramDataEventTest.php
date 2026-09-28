<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Unit\Event;

use FGTCLB\AcademicPrograms\Domain\Model\ProgramData;
use FGTCLB\AcademicPrograms\Event\ModifyProgramDataEvent;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyProgramDataEventTest extends UnitTestCase
{
    #[Test]
    public function getProgramReturnsInstanceSetInConstructor(): void
    {
        $program = new ProgramData();
        $event = new ModifyProgramDataEvent($program, [], $this->createStub(ServerRequestInterface::class));
        $this->assertSame($program, $event->getProgram());
    }

    #[Test]
    public function getProgramReturnsTheDataAListenerReplacedItWith(): void
    {
        $replacement = new ProgramData();
        $event = new ModifyProgramDataEvent(new ProgramData(), [], $this->createStub(ServerRequestInterface::class));
        $event->setProgram($replacement);
        $this->assertSame($replacement, $event->getProgram());
    }

    #[Test]
    public function getPageRecordReturnsTheRecordSetInConstructor(): void
    {
        $pageRecord = ['uid' => 10, 'title' => 'Applied Physics'];
        $event = new ModifyProgramDataEvent(new ProgramData(), $pageRecord, $this->createStub(ServerRequestInterface::class));
        $this->assertSame($pageRecord, $event->getPageRecord());
    }

    #[Test]
    public function getRequestReturnsInstanceSetInConstructor(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $event = new ModifyProgramDataEvent(new ProgramData(), [], $request);
        $this->assertSame($request, $event->getRequest());
    }
}
