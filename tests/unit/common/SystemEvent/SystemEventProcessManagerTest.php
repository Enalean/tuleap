<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SystemEvent;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use SystemEventProcess;
use SystemEventProcessManager;
use Tuleap\Test\PHPUnit\TestCase;

final class SystemEventProcessManagerTest extends TestCase
{
    private SystemEventProcessManager $process_manager;
    private SystemEventProcess&MockObject $process;
    private LockFactory $lock_factory;

    public function setUp(): void
    {
        $this->process = $this->createMock(SystemEventProcess::class);
        $this->process->method('getLockName')->willReturn('lock');

        $store              = new SemaphoreStore();
        $this->lock_factory = new LockFactory($store);

        $this->process_manager = new SystemEventProcessManager($this->lock_factory);
    }

    public function testItReturnsFalseIfNoProcessRunning(): void
    {
        self::assertFalse($this->process_manager->isAlreadyRunning($this->process));
    }

    public function testItReturnsTrueIfAProcessIsRunning(): void
    {
        $lock = $this->lock_factory->createLock($this->process->getLockName());
        $lock->acquire();

        self::assertTrue($this->process_manager->isAlreadyRunning($this->process));

        $lock->release();
    }
}
