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
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\PendingProgramIncrementUpdateProxy;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementUpdateEvent;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveChangesetSubmissionDateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchPendingIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchPendingProgramIncrementUpdatesStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;
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
    private SearchPendingProgramIncrementUpdatesStub $update_searcher;
    private MockObject|DeletePendingProgramIncrementUpdates $update_deleter;
    private VerifyIsChangesetStub $changeset_verifier;

    protected function setUp(): void
    {
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

        $this->update_searcher = SearchPendingProgramIncrementUpdatesStub::withUpdate(
            new PendingProgramIncrementUpdateProxy(
                self::PROGRAM_INCREMENT_ID,
                self::USER_ID,
                self::PROGRAM_INCREMENT_CHANGESET_ID
            )
        );

        $this->logger                     = new TestLogger();
        $this->iteration_verifier         = VerifyIsIterationStub::withValidIteration();
        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withValidProgramIncrement();
        $this->iteration_deleter          = $this->createMock(DeletePendingIterations::class);
        $this->user_verifier              = VerifyIsUserStub::withValidUser();
        $this->update_deleter             = $this->createMock(DeletePendingProgramIncrementUpdates::class);
        $this->changeset_verifier         = VerifyIsChangesetStub::withValidChangeset();
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
            $this->update_searcher,
            RetrieveProgramIncrementTrackerStub::withValidTracker(
                self::PROGRAM_INCREMENT_TRACKER_ID
            ),
            $this->update_deleter,
            new ProgramIncrementUpdateProcessor(
                $this->logger,
                GatherSynchronizedFieldsStub::withFieldsPreparations(
                    new SynchronizedFieldsStubPreparation(531, 230, 645, 627, 156, 979),
                    new SynchronizedFieldsStubPreparation(340, 984, 368, 817, 268, 678),
                    new SynchronizedFieldsStubPreparation(238, 624, 580, 208, 113, 106),
                ),
                RetrieveFieldValuesGathererStub::withGatherer(GatherFieldValuesStub::withDefault()),
                RetrieveChangesetSubmissionDateStub::withDefaults(),
                SearchMirroredTimeboxesStub::withIds(738, 633),
                RetrieveTrackerOfArtifactStub::withTrackers(
                    TrackerIdentifierStub::withId(45),
                    TrackerIdentifierStub::withId(33)
                )
            ),
            new IterationCreationProcessor($this->logger)
        );
    }

    public function testItProcessesValidEvent(): void
    {
        $this->getHandler()->handle($this->buildValidEvent());

        self::assertTrue(
            $this->logger->hasDebug('Processing program increment update with program increment #58 for user #108')
        );
        self::assertTrue($this->logger->hasDebug('Processing iteration creation with iteration #196 for user #108'));
        self::assertTrue($this->logger->hasDebug('Processing iteration creation with iteration #532 for user #108'));
    }

    public function testItDoesNothingWhenEventIsNull(): void
    {
        $invalid_worker_event = new WorkerEvent($this->logger, [
            'event_name' => 'unrelated.topic',
            'payload'    => [],
        ]);
        $event                = ProgramIncrementUpdateEventProxy::fromWorkerEvent($this->logger, $invalid_worker_event);

        $this->getHandler()->handle($event);

        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItDoesNothingWhenArtifactFromStoredUpdateHasBeenDeleted(): void
    {
        // For example when program increment is deleted, the store will return null
        $this->update_searcher = SearchPendingProgramIncrementUpdatesStub::withNoUpdate();

        $this->getHandler()->handle($this->buildValidEvent());

        self::assertFalse($this->logger->hasDebugThatContains('Processing program increment update'));
    }

    public function testItDoesNothingWhenArtifactsFromStoredCreationsHaveBeenDeleted(): void
    {
        // For example when iteration or program increment are deleted, the store will return an empty array
        $this->iteration_searcher = SearchPendingIterationsStub::withNoCreation();

        $this->getHandler()->handle($this->buildValidEvent());

        self::assertFalse($this->logger->hasDebugThatContains('Processing iteration creation'));
    }

    public function testItCleansUpStoredCreationWhenIterationIsNoLongerValid(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Iteration tracker.
        $this->iteration_verifier = VerifyIsIterationStub::withNotIteration();
        $this->iteration_deleter->expects(self::atLeastOnce())->method('deletePendingIterationCreationsByIterationId');

        $this->getHandler()->handle($this->buildValidEvent());
    }

    public function testItCleansUpStoredUpdateAndCreationWhenProgramIncrementIsNoLongerValid(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Program Increment tracker.
        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withNotProgramIncrement();
        $this->update_deleter->expects(self::atLeastOnce())->method(
            'deletePendingProgramIncrementUpdatesByProgramIncrementId'
        );
        $this->iteration_deleter->expects(self::atLeastOnce())->method(
            'deletePendingIterationCreationsByProgramIncrementId'
        );

        $this->getHandler()->handle($this->buildValidEvent());
    }

    public function testItSkipsUpdateAndCreationWhenUserIsInvalid(): void
    {
        // It should not happen unless the database has been filled with wrong information or manually tampered with
        $this->user_verifier = VerifyIsUserStub::withNotValidUser();

        $this->getHandler()->handle($this->buildValidEvent());

        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItSkipsUpdateAndCreationWhenChangesetIsInvalid(): void
    {
        // It should not happen unless the database has been filled with wrong information or manually tampered with
        $this->changeset_verifier = VerifyIsChangesetStub::withNotValidChangeset();

        $this->getHandler()->handle($this->buildValidEvent());

        self::assertTrue($this->logger->hasErrorRecords());
    }

    private function buildValidEvent(): ?ProgramIncrementUpdateEvent
    {
        $worker_event = new WorkerEvent($this->logger, [
            'event_name' => ProgramIncrementUpdateEvent::TOPIC,
            'payload'    => [
                'artifact_id'  => self::PROGRAM_INCREMENT_ID,
                'user_id'      => self::USER_ID,
                'changeset_id' => self::PROGRAM_INCREMENT_CHANGESET_ID
            ]
        ]);
        return ProgramIncrementUpdateEventProxy::fromWorkerEvent($this->logger, $worker_event);
    }
}
