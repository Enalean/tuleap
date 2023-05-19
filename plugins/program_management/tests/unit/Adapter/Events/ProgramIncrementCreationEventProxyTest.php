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

namespace Tuleap\ProgramManagement\Adapter\Events;

use PHPUnit\Framework\MockObject\Stub;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementCreationEvent;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProgramIncrementCreationEventProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ARTIFACT_ID  = 29;
    private const USER_ID      = 186;
    private const CHANGESET_ID = 7806;
    private TestLogger $logger;
    /**
     * @var Stub&\UserManager
     */
    private $user_manager;

    protected function setUp(): void
    {
        $this->logger       = new TestLogger();
        $this->user_manager = $this->createStub(\UserManager::class);
    }

    public function testItBuildsFromValidWorkerEvent(): void
    {
        $worker_event = new WorkerEvent($this->logger, [
            'event_name' => ProgramIncrementCreationEvent::TOPIC,
            'payload'    => [
                'artifact_id'  => self::ARTIFACT_ID,
                'user_id'      => self::USER_ID,
                'changeset_id' => self::CHANGESET_ID,
            ],
        ]);
        $this->user_manager->method('getUserById')->willReturn(UserTestBuilder::buildWithId(186));

        $event = ProgramIncrementCreationEventProxy::fromWorkerEvent(
            $this->logger,
            $this->user_manager,
            $worker_event
        );
        self::assertSame(self::ARTIFACT_ID, $event?->getArtifactId());
        self::assertSame(self::USER_ID, $event?->getUser()->getId());
        self::assertSame(self::CHANGESET_ID, $event?->getChangesetId());
    }

    public function testItReturnsNullWhenUnrelatedTopic(): void
    {
        $worker_event = new WorkerEvent($this->logger, [
            'event_name' => 'unrelated.topic',
            'payload'    => [],
        ]);
        self::assertNull(
            ProgramIncrementCreationEventProxy::fromWorkerEvent($this->logger, $this->user_manager, $worker_event)
        );
    }

    public function testItLogsMalformedPayload(): void
    {
        $worker_event = new WorkerEvent($this->logger, [
            'event_name' => ProgramIncrementCreationEvent::TOPIC,
            'payload'    => [],
        ]);
        self::assertNull(
            ProgramIncrementCreationEventProxy::fromWorkerEvent($this->logger, $this->user_manager, $worker_event)
        );
        self::assertTrue(
            $this->logger->hasWarning(
                sprintf('The payload for %s seems to be malformed, ignoring', ProgramIncrementCreationEvent::TOPIC)
            )
        );
    }

    public function testItLogsUnknownUser(): void
    {
        $worker_event = new WorkerEvent($this->logger, [
            'event_name' => ProgramIncrementCreationEvent::TOPIC,
            'payload'    => [
                'artifact_id'  => self::ARTIFACT_ID,
                'user_id'      => 404,
                'changeset_id' => self::CHANGESET_ID,
            ],
        ]);
        $this->user_manager->method('getUserById')->willReturn(null);

        self::assertNull(
            ProgramIncrementCreationEventProxy::fromWorkerEvent($this->logger, $this->user_manager, $worker_event)
        );
        self::assertTrue($this->logger->hasError('Could not find user with id #404'));
    }
}
