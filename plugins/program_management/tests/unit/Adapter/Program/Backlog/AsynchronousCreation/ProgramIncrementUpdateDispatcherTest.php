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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessIterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\JustLinkedIterationCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramIncrementUpdateProcessorStub;
use Tuleap\ProgramManagement\Tests\Stub\ProcessProgramIncrementUpdateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationHasBeenLinkedBeforeStub;
use Tuleap\Queue\NoQueueSystemAvailableException;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\QueueServerConnectionException;

final class ProgramIncrementUpdateDispatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID              = 110;
    private const PROGRAM_INCREMENT_ID = 83;
    private const CHANGESET_ID         = 6104;
    private TestLogger $logger;
    private Stub|QueueFactory $queue_factory;
    private ProcessProgramIncrementUpdateStub $update_processor;
    private MockObject|ProcessIterationCreation $iteration_processor;
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
        $this->iteration_processor = $this->createMock(ProcessIterationCreation::class);

        $this->program_increment_update = ProgramIncrementUpdateBuilder::buildWithIds(
            self::USER_ID,
            self::PROGRAM_INCREMENT_ID,
            17,
            self::CHANGESET_ID
        );

        $iterations                = IterationIdentifier::buildCollectionFromProgramIncrement(
            SearchIterationsStub::withIterationIds(54, 89),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            $this->program_increment_update->getProgramIncrement(),
            $this->program_increment_update->getUser()
        );
        $just_linked_iterations    = JustLinkedIterationCollection::fromIterations(
            VerifyIterationHasBeenLinkedBeforeStub::withNoIteration(),
            $this->program_increment_update->getProgramIncrement(),
            ...$iterations
        );
        $this->iteration_creations = IterationCreation::buildCollectionFromJustLinkedIterations(
            RetrieveLastChangesetStub::withLastChangesetIds(5539, 5174),
            $this->logger,
            $just_linked_iterations,
            $this->program_increment_update->getUser()
        );
    }

    private function getDispatcher(): ProgramIncrementUpdateDispatcher
    {
        return new ProgramIncrementUpdateDispatcher(
            $this->logger,
            $this->queue_factory,
            BuildProgramIncrementUpdateProcessorStub::withProcessor($this->update_processor),
            $this->iteration_processor,
        );
    }

    public function testItPushesASingleMessageForProgramIncrementUpdateAndIterationCreations(): void
    {
        $queue = $this->createMock(PersistentQueue::class);
        $queue->expects(self::once())
            ->method('pushSinglePersistentMessage')
            ->with(
                ProgramIncrementUpdateEvent::TOPIC,
                [
                    'artifact_id'  => self::PROGRAM_INCREMENT_ID,
                    'user_id'      => self::USER_ID,
                    'changeset_id' => self::CHANGESET_ID
                ]
            );
        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);

        $this->getDispatcher()->dispatchUpdate($this->program_increment_update, ...$this->iteration_creations);
    }

    public function testWhenThereIsNoQueueSystemItProcessesUpdateImmediately(): void
    {
        $this->queue_factory->method('getPersistentQueue')->willThrowException(
            new NoQueueSystemAvailableException('No queue system')
        );
        $this->iteration_processor->expects(self::exactly(2))->method('processIterationCreation');

        $this->getDispatcher()->dispatchUpdate($this->program_increment_update, ...$this->iteration_creations);

        self::assertSame(1, $this->update_processor->getCallCount());
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
        $this->iteration_processor->expects(self::exactly(2))->method('processIterationCreation');

        $this->getDispatcher()->dispatchUpdate($this->program_increment_update, ...$this->iteration_creations);

        self::assertSame(1, $this->update_processor->getCallCount());
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
