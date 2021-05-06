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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\WorkerAvailability;

final class EventSubjectToNotificationAsynchronousRedisDispatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|QueueFactory
     */
    private $queue_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|WorkerAvailability
     */
    private $worker_availability;
    /**
     * @var EventSubjectToNotificationAsynchronousRedisDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->queue_factory       = \Mockery::mock(QueueFactory::class);
        $this->worker_availability = \Mockery::mock(WorkerAvailability::class);

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

        $this->worker_availability->shouldReceive('canProcessAsyncTasks')->andReturn(true);

        $queue = \Mockery::mock(PersistentQueue::class);
        $this->queue_factory->shouldReceive('getPersistentQueue')->andReturn($queue);
        $queue->shouldReceive('pushSinglePersistentMessage')->once();
        $returned_event = $this->dispatcher->dispatch($event);

        $this->assertSame($event, $returned_event);
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

        $this->worker_availability->shouldReceive('canProcessAsyncTasks')->andReturn(false);

        $this->queue_factory->shouldNotReceive('getPersistentQueue');

        $this->expectException(NoWorkerAvailableToProcessTheQueueException::class);
        $this->dispatcher->dispatch($event);
    }

    public function testDoNothingWhenDispatchingSomethingThatIsNotAPREventSubjectToNotification(): void
    {
        $something = new class
        {
        };

        $this->queue_factory->shouldNotReceive('getPersistentQueue');

        $this->assertSame($something, $this->dispatcher->dispatch($something));
    }
}
