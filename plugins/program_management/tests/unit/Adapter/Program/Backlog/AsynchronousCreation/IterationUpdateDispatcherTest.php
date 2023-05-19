<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use PHPUnit\Framework\MockObject\Stub;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Adapter\JSON\PendingIterationUpdateRepresentation;
use Tuleap\ProgramManagement\Domain\Events\IterationUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationUpdate;
use Tuleap\ProgramManagement\Tests\Builder\IterationUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildIterationUpdateProcessorStub;
use Tuleap\ProgramManagement\Tests\Stub\ProcessIterationUpdateStub;
use Tuleap\Queue\NoQueueSystemAvailableException;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\QueueServerConnectionException;

final class IterationUpdateDispatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TestLogger $logger;
    private ProcessIterationUpdateStub $update_processor;
    /**
     * @var Stub&QueueFactory
     */
    private $queue_factory;
    private IterationUpdate $iteration_update;

    protected function setUp(): void
    {
        $this->logger           = new TestLogger();
        $this->update_processor = ProcessIterationUpdateStub::withCount();
        $this->queue_factory    = $this->createStub(QueueFactory::class);

        $this->iteration_update = IterationUpdateBuilder::build();
    }

    private function getIterationUpdateDispatcher(): IterationUpdateDispatcher
    {
        return new IterationUpdateDispatcher(
            $this->logger,
            BuildIterationUpdateProcessorStub::withProcessor(
                $this->update_processor
            ),
            $this->queue_factory
        );
    }

    public function testItProcessesAsynchronousUpdateByDefault(): void
    {
        $queue = $this->createMock(PersistentQueue::class);
        $queue->expects(self::once())
              ->method('pushSinglePersistentMessage')
              ->with(
                  IterationUpdateEvent::TOPIC,
                  self::isInstanceOf(PendingIterationUpdateRepresentation::class)
              );
        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);

        $this->getIterationUpdateDispatcher()->dispatchUpdate($this->iteration_update);

        self::assertSame(0, $this->update_processor->getCallCount());
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItProcessesSynchronousUpdateIfThereIsNoQueueSystemAvailable(): void
    {
        $this->queue_factory->method("getPersistentQueue")->willThrowException(new NoQueueSystemAvailableException());

        $this->getIterationUpdateDispatcher()->dispatchUpdate($this->iteration_update);

        self::assertSame(1, $this->update_processor->getCallCount());
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItProcessesSynchronousUpdateIfThereIsARedisServerConnectionIssue(): void
    {
        $queue = $this->createMock(PersistentQueue::class);
        $queue->method('pushSinglePersistentMessage')->willThrowException(new QueueServerConnectionException());

        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);

        $this->getIterationUpdateDispatcher()->dispatchUpdate($this->iteration_update);

        self::assertSame(1, $this->update_processor->getCallCount());
        self::assertTrue($this->logger->hasErrorRecords());
    }
}
