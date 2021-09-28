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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Adapter\Events\ProgramIncrementUpdateEventProxy;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\PendingIterationCreationProxy;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramIncrementUpdateProcessorStub;
use Tuleap\ProgramManagement\Tests\Stub\ProcessProgramIncrementUpdateStub;
use Tuleap\ProgramManagement\Tests\Stub\ProgramIncrementUpdateEventStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchPendingIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Queue\WorkerEvent;

final class ProgramIncrementUpdateEventHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_ITERATION_ID             = 196;
    private const SECOND_ITERATION_ID            = 532;
    private const USER_ID                        = 108;
    private const PROGRAM_INCREMENT_ID           = 58;
    private const PROGRAM_INCREMENT_TRACKER_ID   = 36;
    private const PROGRAM_INCREMENT_CHANGESET_ID = 7383;
    private TestLogger $logger;
    private SearchPendingIterationsStub $iteration_searcher;
    private VerifyIsProgramIncrementStub $program_increment_verifier;
    private VerifyIsIterationStub $iteration_verifier;
    private MockObject|DeletePendingIterations $iteration_deleter;
    private VerifyIsUserStub $user_verifier;
    private VerifyIsChangesetStub $changeset_verifier;
    private ProcessProgramIncrementUpdateStub $update_processor;
    private ProgramIncrementUpdateEventStub $event;

    protected function setUp(): void
    {
        $this->event = ProgramIncrementUpdateEventStub::withIds(
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::PROGRAM_INCREMENT_CHANGESET_ID
        );

        $this->iteration_searcher = SearchPendingIterationsStub::withPendingCreations(
            new PendingIterationCreationProxy(
                self::FIRST_ITERATION_ID,
                self::PROGRAM_INCREMENT_ID,
                self::USER_ID,
                5457
            ),
            new PendingIterationCreationProxy(
                self::SECOND_ITERATION_ID,
                self::PROGRAM_INCREMENT_ID,
                self::USER_ID,
                3325
            ),
        );

        $this->logger                     = new TestLogger();
        $this->iteration_verifier         = VerifyIsIterationStub::withValidIteration();
        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withValidProgramIncrement();
        $this->iteration_deleter          = $this->createMock(DeletePendingIterations::class);
        $this->user_verifier              = VerifyIsUserStub::withValidUser();
        $this->changeset_verifier         = VerifyIsChangesetStub::withValidChangeset();
        $this->update_processor           = ProcessProgramIncrementUpdateStub::withCount();
    }

    private function getHandler(): ProgramIncrementUpdateEventHandler
    {
        return new ProgramIncrementUpdateEventHandler(
            $this->logger,
            $this->iteration_searcher,
            $this->user_verifier,
            $this->iteration_verifier,
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            $this->program_increment_verifier,
            $this->changeset_verifier,
            $this->iteration_deleter,
            RetrieveProgramIncrementTrackerStub::withValidTracker(
                self::PROGRAM_INCREMENT_TRACKER_ID
            ),
            BuildProgramIncrementUpdateProcessorStub::withProcessor(
                $this->update_processor
            ),
            new IterationCreationProcessor($this->logger)
        );
    }

    public function testItProcessesValidEvent(): void
    {
        $this->getHandler()->handle($this->event);

        self::assertSame(1, $this->update_processor->getCallCount());
        self::assertTrue(
            $this->logger->hasDebug(
                sprintf(
                    'Processing iteration creation with iteration #%d for user #%d',
                    self::FIRST_ITERATION_ID,
                    self::USER_ID
                )
            )
        );
        self::assertTrue(
            $this->logger->hasDebug(
                sprintf('Processing iteration creation with iteration #%d for user #%d', self::SECOND_ITERATION_ID, self::USER_ID)
            )
        );
    }

    public function testItDoesNothingWhenEventIsNull(): void
    {
        $invalid_worker_event = new WorkerEvent($this->logger, [
            'event_name' => 'unrelated.topic',
            'payload'    => [],
        ]);
        $event                = ProgramIncrementUpdateEventProxy::fromWorkerEvent($this->logger, $invalid_worker_event);

        $this->getHandler()->handle($event);

        self::assertSame(0, $this->update_processor->getCallCount());
    }

    public function testItIgnoresEventWhenArtifactsFromStoredCreationsHaveBeenDeleted(): void
    {
        // For example when iteration or program increment are deleted, the store will return an empty array
        $this->iteration_searcher = SearchPendingIterationsStub::withNoCreation();

        $this->getHandler()->handle($this->event);

        self::assertFalse($this->logger->hasDebugThatContains('Processing iteration creation'));
    }

    public function testItCleansUpStoredCreationWhenIterationIsNoLongerValid(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Iteration tracker.
        $this->iteration_verifier = VerifyIsIterationStub::withNotIteration();
        $this->iteration_deleter->expects(self::atLeastOnce())->method('deletePendingIterationCreationsByIterationId');

        $this->getHandler()->handle($this->event);
    }

    public function testItCleansUpStoredCreationWhenProgramIncrementIsNoLongerValid(): void
    {
        // It can take some time between the dispatch of the event in the Queue and its processing.
        // Someone could have deleted the Artifact from the event.
        // Someone could have changed the Program Increment tracker in the configuration.
        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withNotProgramIncrement();
        $this->iteration_deleter->expects(self::atLeastOnce())->method(
            'deletePendingIterationCreationsByProgramIncrementId'
        );

        $this->getHandler()->handle($this->event);

        self::assertSame(0, $this->update_processor->getCallCount());
    }

    public function testItSkipsUpdateAndCreationWhenUserIsInvalid(): void
    {
        // It should not happen unless the database has been filled with wrong information or manually tampered with
        $this->user_verifier = VerifyIsUserStub::withNotValidUser();

        $this->getHandler()->handle($this->event);

        self::assertTrue($this->logger->hasErrorRecords());
        self::assertSame(0, $this->update_processor->getCallCount());
    }

    public function testItSkipsUpdateAndCreationWhenChangesetIsInvalid(): void
    {
        // It should not happen unless the database has been filled with wrong information or manually tampered with
        $this->changeset_verifier = VerifyIsChangesetStub::withNotValidChangeset();

        $this->getHandler()->handle($this->event);

        self::assertTrue($this->logger->hasErrorRecords());
        self::assertSame(0, $this->update_processor->getCallCount());
    }
}
