<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Events;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Queue\WorkerEvent;

class TeamSynchronizationEventProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new TestLogger();
    }

    public function testItReturnsNullWhenWorkerEventIsNotForTeamSync(): void
    {
        $worker_event = new WorkerEvent($this->logger, [
            'event_name' => "a.random.event",
            'payload'    => [],
        ]);

        $event = TeamSynchronizationEventProxy::fromWorkerEvent(
            $this->logger,
            $worker_event
        );

        self::assertNull($event);
    }

    public function testItReturnsNullWhenPayloadIsMalformedAndLogsAWarning(): void
    {
        $worker_event = new WorkerEvent($this->logger, [
            'event_name' => TeamSynchronizationEventProxy::TOPIC,
            'payload'    => [],
        ]);

        $event = TeamSynchronizationEventProxy::fromWorkerEvent(
            $this->logger,
            $worker_event
        );

        self::assertNull($event);
        self::assertTrue($this->logger->hasWarningThatContains("malformed, ignoring"));
    }

    public function testItBuildsFromWorkerEvent(): void
    {
        $worker_event = new WorkerEvent($this->logger, [
            'event_name' => TeamSynchronizationEventProxy::TOPIC,
            'payload'    => [
                'program_id' => 1,
                'team_id' => 123,
                'user_id' => 456,
            ],
        ]);

        $event = TeamSynchronizationEventProxy::fromWorkerEvent(
            $this->logger,
            $worker_event
        );

        self::assertNotNull($event);
        self::assertEquals(1, $event->getProgramId());
        self::assertEquals(123, $event->getTeamId());
    }
}
