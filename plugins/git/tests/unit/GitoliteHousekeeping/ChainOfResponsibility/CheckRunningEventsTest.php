<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Git\GitoliteHousekeeping\ChainOfResponsibility;

use Git_GitoliteHousekeeping_ChainOfResponsibility_CheckRunningEvents;
use Git_GitoliteHousekeeping_ChainOfResponsibility_Command;
use Git_GitoliteHousekeeping_GitoliteHousekeepingResponse;
use PHPUnit\Framework\MockObject\MockObject;
use SystemEventProcess;
use SystemEventProcessManager;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CheckRunningEventsTest extends TestCase
{
    private SystemEventProcessManager&MockObject $process_manager;
    private SystemEventProcess&MockObject $process;
    private Git_GitoliteHousekeeping_GitoliteHousekeepingResponse&MockObject $response;
    private Git_GitoliteHousekeeping_ChainOfResponsibility_Command&MockObject $next;
    private Git_GitoliteHousekeeping_ChainOfResponsibility_CheckRunningEvents $command;

    protected function setUp(): void
    {
        $this->process_manager = $this->createMock(SystemEventProcessManager::class);
        $this->process         = $this->createMock(SystemEventProcess::class);
        $this->response        = $this->createMock(Git_GitoliteHousekeeping_GitoliteHousekeepingResponse::class);
        $this->next            = $this->createMock(Git_GitoliteHousekeeping_ChainOfResponsibility_Command::class);

        $this->command = new Git_GitoliteHousekeeping_ChainOfResponsibility_CheckRunningEvents($this->response, $this->process_manager, $this->process);
        $this->command->setNextCommand($this->next);
    }

    public function testItExecuteTheNextCommandIfThereIsNoRunningEvents(): void
    {
        $this->process_manager->method('isAlreadyRunning')->with($this->process)->willReturn(false);

        $this->next->expects($this->once())->method('execute');

        $this->command->execute();
    }

    public function testItDoesNotExectuteTheNextCommandIfThereIsARunningEvent(): void
    {
        $this->process_manager->method('isAlreadyRunning')->with($this->process)->willReturn(true);

        $this->next->expects($this->never())->method('execute');

        $this->response->method('error');
        $this->response->method('abort');
        $this->command->execute();
    }

    public function testItStopsTheExecutionWhenThereIsARemainingSystemEventRunning(): void
    {
        $this->process_manager->method('isAlreadyRunning')->with($this->process)->willReturn(true);

        $this->response->expects($this->once())->method('error')->with('There is still an event marked as running. Start again when all events marked as running are done.');
        $this->response->expects($this->once())->method('abort');

        $this->command->execute();
    }
}
