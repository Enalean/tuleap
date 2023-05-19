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

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementUpdateEvent;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Test\PHPUnit\TestCase;

final class ProgramIncrementUpdateEventProxyTest extends TestCase
{
    private const PROGRAM_INCREMENT_ID               = 29;
    private const USER_ID                            = 186;
    private const PROGRAM_INCREMENT_CHANGESET_ID     = 7806;
    private const PROGRAM_INCREMENT_OLD_CHANGESET_ID = 7805;
    private const FIRST_ITERATION_ID                 = 95;
    private const FIRST_ITERATION_CHANGESET_ID       = 3874;
    private const SECOND_ITERATION_ID                = 15;
    private const SECOND_ITERATION_CHANGESET_ID      = 2197;
    private TestLogger $logger;
    private VerifyIsUserStub $user_verifier;
    private VerifyIsProgramIncrementStub $program_increment_verifier;
    private VerifyIsVisibleArtifactStub $visibility_verifier;
    private VerifyIsIterationStub $iteration_verifier;
    private VerifyIsChangesetStub $changeset_verifier;
    private WorkerEvent $worker_event;

    protected function setUp(): void
    {
        $this->logger                     = new TestLogger();
        $this->user_verifier              = VerifyIsUserStub::withValidUser();
        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withValidProgramIncrement();
        $this->visibility_verifier        = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $this->iteration_verifier         = VerifyIsIterationStub::withValidIteration();
        $this->changeset_verifier         = VerifyIsChangesetStub::withValidChangeset();

        $this->worker_event = new WorkerEvent($this->logger, [
            'event_name' => ProgramIncrementUpdateEvent::TOPIC,
            'payload'    => [
                'program_increment_id' => self::PROGRAM_INCREMENT_ID,
                'user_id'              => self::USER_ID,
                'changeset_id'         => self::PROGRAM_INCREMENT_CHANGESET_ID,
                'old_changeset_id'     => self::PROGRAM_INCREMENT_OLD_CHANGESET_ID,
                'iterations'           => [
                    ['id' => self::FIRST_ITERATION_ID, 'changeset_id' => self::FIRST_ITERATION_CHANGESET_ID],
                    ['id' => self::SECOND_ITERATION_ID, 'changeset_id' => self::SECOND_ITERATION_CHANGESET_ID],
                ],
            ],
        ]);
    }

    public function testItBuildsFromValidWorkerEvent(): void
    {
        $event = ProgramIncrementUpdateEventProxy::fromWorkerEvent(
            $this->logger,
            $this->user_verifier,
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $this->iteration_verifier,
            $this->changeset_verifier,
            $this->worker_event
        );

        if (! $event) {
            throw new \LogicException("Event is not properly created");
        }

        self::assertSame(self::PROGRAM_INCREMENT_ID, $event->getProgramIncrement()->getId());
        self::assertSame(self::USER_ID, $event->getUser()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_CHANGESET_ID, $event->getChangeset()->getId());
        foreach ($event->getIterations() as $iteration) {
            self::assertContains(
                $iteration->getIteration()->getId(),
                [self::FIRST_ITERATION_ID, self::SECOND_ITERATION_ID]
            );
            self::assertContains(
                $iteration->getChangeset()->getId(),
                [self::FIRST_ITERATION_CHANGESET_ID, self::SECOND_ITERATION_CHANGESET_ID]
            );
        }
    }

    public function testItReturnsNullWhenUnrelatedTopic(): void
    {
        $worker_event = new WorkerEvent($this->logger, [
            'event_name' => 'unrelated.topic',
            'payload'    => [],
        ]);
        self::assertNull(
            ProgramIncrementUpdateEventProxy::fromWorkerEvent(
                $this->logger,
                $this->user_verifier,
                $this->program_increment_verifier,
                $this->visibility_verifier,
                $this->iteration_verifier,
                $this->changeset_verifier,
                $worker_event
            )
        );
    }

    public function testItLogsMalformedPayload(): void
    {
        $worker_event = new WorkerEvent($this->logger, [
            'event_name' => ProgramIncrementUpdateEventProxy::TOPIC,
            'payload'    => [],
        ]);
        self::assertNull(
            ProgramIncrementUpdateEventProxy::fromWorkerEvent(
                $this->logger,
                $this->user_verifier,
                $this->program_increment_verifier,
                $this->visibility_verifier,
                $this->iteration_verifier,
                $this->changeset_verifier,
                $worker_event
            )
        );
        self::assertTrue(
            $this->logger->hasWarning(
                sprintf('The payload for %s seems to be malformed, ignoring', ProgramIncrementUpdateEvent::TOPIC)
            )
        );
    }

    public function testItLogsErrorWhenGivenUnknownUser(): void
    {
        // It's not supposed to happen as users cannot be deleted in Tuleap. They change status.
        self::assertNull(
            ProgramIncrementUpdateEventProxy::fromWorkerEvent(
                $this->logger,
                VerifyIsUserStub::withNotValidUser(),
                $this->program_increment_verifier,
                $this->visibility_verifier,
                $this->iteration_verifier,
                $this->changeset_verifier,
                $this->worker_event
            )
        );
        self::assertTrue(
            $this->logger->hasError(
                sprintf(
                    'Invalid data given in payload, skipping program increment update for artifact #%d, user #%d and changeset #%d (previous changeset id #%d)',
                    self::PROGRAM_INCREMENT_ID,
                    self::USER_ID,
                    self::PROGRAM_INCREMENT_CHANGESET_ID,
                    self::PROGRAM_INCREMENT_OLD_CHANGESET_ID
                )
            )
        );
    }

    public function testItLogsDebugWhenGivenProgramIncrementIsNoLongerValid(): void
    {
        // It can take some time between the dispatch of the event in the Queue and its processing.
        // Someone could have deleted the Artifact from the event.
        // Someone could have changed the Program Increment tracker in the configuration.
        self::assertNull(
            ProgramIncrementUpdateEventProxy::fromWorkerEvent(
                $this->logger,
                $this->user_verifier,
                VerifyIsProgramIncrementStub::withNotProgramIncrement(),
                $this->visibility_verifier,
                $this->iteration_verifier,
                $this->changeset_verifier,
                $this->worker_event
            )
        );
        self::assertTrue(
            $this->logger->hasDebug(
                sprintf('Program increment #%d is no longer valid, skipping update', self::PROGRAM_INCREMENT_ID)
            )
        );
    }

    public function testItLogsErrorWhenGivenChangesetIsInvalid(): void
    {
        // It's not supposed to happen as changesets cannot be deleted in Tuleap.
        self::assertNull(
            ProgramIncrementUpdateEventProxy::fromWorkerEvent(
                $this->logger,
                $this->user_verifier,
                $this->program_increment_verifier,
                $this->visibility_verifier,
                $this->iteration_verifier,
                VerifyIsChangesetStub::withNotValidChangeset(),
                $this->worker_event
            )
        );
        self::assertTrue(
            $this->logger->hasError(
                sprintf(
                    'Invalid data given in payload, skipping program increment update for artifact #%d, user #%d and changeset #%d (previous changeset id #%d)',
                    self::PROGRAM_INCREMENT_ID,
                    self::USER_ID,
                    self::PROGRAM_INCREMENT_CHANGESET_ID,
                    self::PROGRAM_INCREMENT_OLD_CHANGESET_ID
                )
            )
        );
    }

    public function testItLogsDebugWhenGivenIterationIsNoLongerValid(): void
    {
        // It can take some time between the dispatch of the event in the Queue and its processing.
        // Someone could have deleted the Artifact from the event.
        // Someone could have changed the Iteration tracker in the configuration.
        $event = ProgramIncrementUpdateEventProxy::fromWorkerEvent(
            $this->logger,
            $this->user_verifier,
            $this->program_increment_verifier,
            $this->visibility_verifier,
            VerifyIsIterationStub::withNotIteration(),
            $this->changeset_verifier,
            $this->worker_event
        );
        self::assertEmpty($event?->getIterations());
        self::assertTrue(
            $this->logger->hasDebug(
                sprintf('Iteration #%d is no longer valid, skipping creation', self::FIRST_ITERATION_ID)
            )
        );
        self::assertTrue(
            $this->logger->hasDebug(
                sprintf('Iteration #%d is no longer valid, skipping creation', self::SECOND_ITERATION_ID)
            )
        );
    }
}
