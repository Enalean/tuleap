<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Queue;

use Tuleap\ForgeConfigSandbox;

final class WorkerAvailabilityTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testDefaultTo2WorkersWhenAsyncActionsCanBeExecuted(): void
    {
        \ForgeConfig::set('redis_server', 'redis.example.com');

        $worker_availability = new WorkerAvailability();

        self::assertTrue($worker_availability->canProcessAsyncTasks());
        self::assertEquals(2, $worker_availability->getWorkerCount());
    }

    public function testNoWorkerIsAvailableWhenAsyncActionsCannotBeExecuted(): void
    {
        $worker_availability = new WorkerAvailability();
        self::assertFalse($worker_availability->canProcessAsyncTasks());
        self::assertEquals(0, $worker_availability->getWorkerCount());
    }

    public function testProvidesTheNumberOfConfiguredWorkerWhenAsyncActionsCanBeExecuted(): void
    {
        \ForgeConfig::set('redis_server', 'redis.example.com');
        \ForgeConfig::set(WorkerAvailability::NB_BACKEND_WORKERS_CONFIG_KEY, '5');

        $worker_availability = new WorkerAvailability();

        self::assertTrue($worker_availability->canProcessAsyncTasks());
        self::assertEquals(5, $worker_availability->getWorkerCount());
    }
}
