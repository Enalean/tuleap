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

use Exception;
use IRunInAMutex;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use SystemEvent;
use SystemEventProcess;
use SystemEventProcessorMutex;
use Tuleap\DB\DBConnection;
use Tuleap\Test\PHPUnit\TestCase;

final class SystemEventProcessorMutexTest extends TestCase
{
    private IRunInAMutex&MockObject $object;
    private DBConnection&MockObject $db_connexion;
    private SystemEventProcessorMutex&MockObject $mutex;

    protected function setUp(): void
    {
        $process      = $this->createMock(SystemEventProcess::class);
        $this->object = $this->createMock(IRunInAMutex::class);

        $process->method('getQueue')->willReturn(SystemEvent::DEFAULT_QUEUE);
        $process->method('getLockName')->willReturn('lock');

        $this->object->method('getProcess')->willReturn($process);
        $this->object->method('getProcessOwner')->willReturn('root');

        $this->db_connexion = $this->createMock(DBConnection::class);

        $this->mutex = $this->getMockBuilder(SystemEventProcessorMutex::class)
            ->setConstructorArgs([$this->object, new LockFactory(new SemaphoreStore()), $this->db_connexion])
            ->onlyMethods(['checkCurrentUserProcessOwner'])
            ->getMock();
    }

    public function testItExecuteCallable(): void
    {
        $this->mutex->expects(self::once())->method('checkCurrentUserProcessOwner');

        $this->db_connexion->expects(self::never())->method('reconnectAfterALongRunningProcess');
        $this->object->expects(self::once())->method('execute');

        $this->mutex->execute();
    }

    public function testItStopsIfCurrentUserIsNotTheOneThatShouldRun(): void
    {
        $this->mutex->expects(self::once())->method('checkCurrentUserProcessOwner')->willThrowException(new Exception());

        self::expectException(Exception::class);
        $this->object->expects(self::never())->method('execute');

        $this->mutex->execute();
    }

    public function testWaitAndExecuteReconnectsToTheDatabase(): void
    {
        $this->mutex->expects(self::once())->method('checkCurrentUserProcessOwner');

        $this->db_connexion->expects(self::once())->method('reconnectAfterALongRunningProcess');
        $this->object->expects(self::once())->method('execute');

        $this->mutex->waitAndExecute();
    }
}
