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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Git_GitoliteHousekeeping_ChainOfResponsibility_CheckRunningEventsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->process_manager = \Mockery::spy(\SystemEventProcessManager::class);
        $this->process         = \Mockery::spy(\SystemEventProcess::class);
        $this->response        = \Mockery::spy(\Git_GitoliteHousekeeping_GitoliteHousekeepingResponse::class);
        $this->next            = \Mockery::spy(\Git_GitoliteHousekeeping_ChainOfResponsibility_Command::class);

        $this->command = new Git_GitoliteHousekeeping_ChainOfResponsibility_CheckRunningEvents($this->response, $this->process_manager, $this->process);
        $this->command->setNextCommand($this->next);
    }

    public function testItExecuteTheNextCommandIfThereIsNoRunningEvents(): void
    {
        $this->process_manager->shouldReceive('isAlreadyRunning')->with($this->process)->andReturns(false);

        $this->next->shouldReceive('execute')->once();

        $this->command->execute();
    }

    public function testItDoesNotExectuteTheNextCommandIfThereIsARunningEvent(): void
    {
        $this->process_manager->shouldReceive('isAlreadyRunning')->with($this->process)->andReturns(true);

        $this->next->shouldReceive('execute')->never();

        $this->command->execute();
    }

    public function testItStopsTheExecutionWhenThereIsARemainingSystemEventRunning(): void
    {
        $this->process_manager->shouldReceive('isAlreadyRunning')->with($this->process)->andReturns(true);

        $this->response->shouldReceive('error')->with('There is still an event marked as running. Start again when all events marked as running are done.')->once();
        $this->response->shouldReceive('abort')->once();

        $this->command->execute();
    }
}
