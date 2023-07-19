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
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\Notification;

use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\WorkerAvailability;

final class EventSubjectToNotificationAsynchronousRedisDispatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&QueueFactory
     */
    private $queue_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&WorkerAvailability
     */
    private $worker_availability;
    private EventSubjectToNotificationAsynchronousRedisDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->queue_factory       = $this->createMock(QueueFactory::class);
        $this->worker_availability = $this->createMock(WorkerAvailability::class);

        $this->dispatcher = new EventSubjectToNotificationAsynchronousRedisDispatcher($this->queue_factory, $this->worker_availability);
    }

    public function testEventGetsDispatchedIntoAPersistentQueue(): void
    {
        $event = new class implements EventSubjectToNotification
        {
            public static function fromWorkerEventPayload(array $payload): EventSubjectToNotification
            {
                return new self();
            }

            public function toWorkerEventPayload(): array
            {
                return [];
            }
        };

        $this->worker_availability->method('canProcessAsyncTasks')->willReturn(true);

        $queue = $this->createMock(PersistentQueue::class);
        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);
        $queue->expects(self::once())->method('pushSinglePersistentMessage');
        $returned_event = $this->dispatcher->dispatch($event);

        self::assertSame($event, $returned_event);
    }

    public function testDoesNotQueueWhenNoAsyncWorkerAreAvailable(): void
    {
        $event = new class implements EventSubjectToNotification
        {
            public static function fromWorkerEventPayload(array $payload): EventSubjectToNotification
            {
                return new self();
            }

            public function toWorkerEventPayload(): array
            {
                return [];
            }
        };

        $this->worker_availability->method('canProcessAsyncTasks')->willReturn(false);

        $this->queue_factory->expects(self::never())->method('getPersistentQueue');

        $this->expectException(NoWorkerAvailableToProcessTheQueueException::class);
        $this->dispatcher->dispatch($event);
    }

    public function testDoNothingWhenDispatchingSomethingThatIsNotAPREventSubjectToNotification(): void
    {
        $something = new class
        {
        };

        $this->queue_factory->expects(self::never())->method('getPersistentQueue');

        self::assertSame($something, $this->dispatcher->dispatch($something));
    }
}
