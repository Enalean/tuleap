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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use PHPUnit\Framework\MockObject\Stub;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Adapter\JSON\PendingProgramIncrementUpdateRepresentation;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Tests\Builder\IterationCreationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildIterationCreationProcessorStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramIncrementUpdateProcessorStub;
use Tuleap\ProgramManagement\Tests\Stub\ProcessIterationCreationStub;
use Tuleap\ProgramManagement\Tests\Stub\ProcessProgramIncrementUpdateStub;
use Tuleap\Queue\NoQueueSystemAvailableException;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\QueueServerConnectionException;

final class ProgramIncrementUpdateDispatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID = 83;
    private const USER_ID              = 110;
    private const ITERATION_TRACKER_ID = 25;
    private TestLogger $logger;
    /**
     * @var Stub&QueueFactory
     */
    private $queue_factory;
    private ProcessProgramIncrementUpdateStub $update_processor;
    private ProcessIterationCreationStub $iteration_processor;
    private ProgramIncrementUpdate $program_increment_update;
    /**
     * @var IterationCreation[]
     */
    private array $iteration_creations;

    protected function setUp(): void
    {
        $this->logger              = new TestLogger();
        $this->queue_factory       = $this->createStub(QueueFactory::class);
        $this->update_processor    = ProcessProgramIncrementUpdateStub::withCount();
        $this->iteration_processor = ProcessIterationCreationStub::withCount();

        $this->program_increment_update = ProgramIncrementUpdateBuilder::buildWithIds(
            self::USER_ID,
            self::PROGRAM_INCREMENT_ID,
            17,
            6104,
            6103
        );

        $first_iteration_creation  = IterationCreationBuilder::buildWithIds(
            54,
            self::ITERATION_TRACKER_ID,
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            5539
        );
        $second_iteration_creation = IterationCreationBuilder::buildWithIds(
            89,
            self::ITERATION_TRACKER_ID,
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            5174
        );
        $this->iteration_creations = [$first_iteration_creation, $second_iteration_creation];
    }

    private function getDispatcher(): ProgramIncrementUpdateDispatcher
    {
        return new ProgramIncrementUpdateDispatcher(
            $this->logger,
            $this->queue_factory,
            BuildProgramIncrementUpdateProcessorStub::withProcessor($this->update_processor),
            BuildIterationCreationProcessorStub::withProcessor($this->iteration_processor),
        );
    }

    public function testItPushesASingleMessageForProgramIncrementUpdateAndIterationCreations(): void
    {
        $queue = $this->createMock(PersistentQueue::class);
        $queue->expects(self::once())
            ->method('pushSinglePersistentMessage')
            ->with(
                ProgramIncrementUpdateEvent::TOPIC,
                self::isInstanceOf(PendingProgramIncrementUpdateRepresentation::class)
            );
        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);

        $this->getDispatcher()->dispatchUpdate($this->program_increment_update, ...$this->iteration_creations);
    }

    public function testWhenThereIsNoQueueSystemItProcessesUpdateImmediately(): void
    {
        $this->queue_factory->method('getPersistentQueue')->willThrowException(
            new NoQueueSystemAvailableException('No queue system')
        );

        $this->getDispatcher()->dispatchUpdate($this->program_increment_update, ...$this->iteration_creations);

        self::assertSame(1, $this->update_processor->getCallCount());
        self::assertSame(2, $this->iteration_processor->getCallCount());
        self::assertTrue(
            $this->logger->hasError(
                sprintf(
                    'Unable to queue program increment mirrors update for program increment #%d',
                    self::PROGRAM_INCREMENT_ID
                )
            )
        );
    }

    public function testWhenThereIsAProblemWithQueueItProcessesUpdateImmediately(): void
    {
        $queue = $this->createStub(PersistentQueue::class);
        $queue->method('pushSinglePersistentMessage')->willThrowException(
            new QueueServerConnectionException('Error with queue')
        );
        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);

        $this->getDispatcher()->dispatchUpdate($this->program_increment_update, ...$this->iteration_creations);

        self::assertSame(1, $this->update_processor->getCallCount());
        self::assertSame(2, $this->iteration_processor->getCallCount());
        self::assertTrue(
            $this->logger->hasError(
                sprintf(
                    'Unable to queue program increment mirrors update for program increment #%d',
                    self::PROGRAM_INCREMENT_ID
                )
            )
        );
    }
}
