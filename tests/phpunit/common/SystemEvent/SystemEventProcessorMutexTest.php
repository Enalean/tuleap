<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\SystemEvent;

use IRunInAMutex;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use SystemEvent;
use SystemEventProcess;
use SystemEventProcessorMutex;
use Tuleap\DB\DBConnection;

class SystemEventProcessorMutexTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|SystemEventProcess
     */
    private $process;

    /**
     * @var IRunInAMutex|Mockery\MockInterface
     */
    private $object;

    /**
     * @var Mockery\MockInterface|DBConnection
     */
    private $db_connexion;

    /**
     * @var Mockery\MockInterface|SystemEventProcessorMutex
     */
    private $mutex;

    protected function setUp(): void
    {
        parent::setUp();

        $this->process  = Mockery::mock(SystemEventProcess::class);
        $this->object   = Mockery::mock(IRunInAMutex::class);

        $this->process->shouldReceive('getQueue')->andReturn(SystemEvent::DEFAULT_QUEUE);
        $this->process->shouldReceive('getLockName')->andReturn('lock');

        $this->object->shouldReceive('getProcess')->andReturn($this->process);
        $this->object->shouldReceive('getProcessOwner')->andReturn('root');

        $this->db_connexion = Mockery::mock(DBConnection::class);

        $this->mutex = Mockery::mock(
            SystemEventProcessorMutex::class,
            [$this->object, new LockFactory(new SemaphoreStore()), $this->db_connexion]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testItExecuteCallable()
    {
        $this->mutex->shouldReceive('checkCurrentUserProcessOwner')->once();

        $this->db_connexion->shouldReceive('reconnectAfterALongRunningProcess')->never();
        $this->object->shouldReceive('execute')->once();

        $this->mutex->execute();
    }

    public function testItStopsIfCurrentUserIsNotTheOneThatShouldRun()
    {
        $this->mutex->shouldReceive('checkCurrentUserProcessOwner')->once()->andThrow(new Exception());

        $this->expectException(Exception::class);
        $this->object->shouldReceive('execute')->never();

        $this->mutex->execute();
    }

    public function testWaitAndExecuteReconnectsToTheDatabase()
    {
        $this->mutex->shouldReceive('checkCurrentUserProcessOwner')->once();

        $this->db_connexion->shouldReceive('reconnectAfterALongRunningProcess')->once();
        $this->object->shouldReceive('execute')->once();

        $this->mutex->waitAndExecute();
    }
}
