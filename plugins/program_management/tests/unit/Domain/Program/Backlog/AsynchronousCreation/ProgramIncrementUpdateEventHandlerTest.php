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

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Adapter\Events\ProgramIncrementUpdateEventProxy;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\PendingIterationCreationProxy;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementUpdateEvent;
use Tuleap\ProgramManagement\Tests\Stub\CheckProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchPendingIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProgramIncrementUpdateEventHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_ITERATION_ID   = 196;
    private const SECOND_ITERATION_ID  = 532;
    private const USER_ID              = 108;
    private const PROGRAM_INCREMENT_ID = 58;
    private TestLogger $logger;
    private SearchPendingIterationsStub $iteration_searcher;
    private CheckProgramIncrementStub $program_increment_checker;
    private RetrieveUserStub $user_retriever;
    private VerifyIsIterationStub $iteration_verifier;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|DeletePendingIterations
     */
    private $iteration_deleter;
    private VerifyIsUserStub $user_verifier;

    protected function setUp(): void
    {
        $this->logger                    = new TestLogger();
        $this->iteration_searcher        = SearchPendingIterationsStub::withPendingCreations(
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
        $this->iteration_verifier        = VerifyIsIterationStub::withValidIteration();
        $this->user_retriever            = RetrieveUserStub::withUser(
            UserTestBuilder::aUser()->withId(self::USER_ID)->build()
        );
        $this->program_increment_checker = CheckProgramIncrementStub::buildProgramIncrementChecker();
        $this->iteration_deleter         = $this->createMock(DeletePendingIterations::class);
        $this->user_verifier             = VerifyIsUserStub::withValidUser();
    }

    private function getHandler(): ProgramIncrementUpdateEventHandler
    {
        return new ProgramIncrementUpdateEventHandler(
            $this->logger,
            $this->iteration_searcher,
            $this->user_verifier,
            $this->iteration_verifier,
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            $this->user_retriever,
            $this->program_increment_checker,
            VerifyIsChangesetStub::withValidChangeset(),
            $this->iteration_deleter
        );
    }

    public function testItProcessesValidEvent(): void
    {
        $this->getHandler()->handle($this->buildValidEvent());

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

    public function testItDoesNothingWhenArtifactsFromStoredCreationsHaveBeenDeleted(): void
    {
        // For example when iteration or program increment are deleted, the store will return an empty array
        $this->iteration_searcher = SearchPendingIterationsStub::withNoCreation();

        $this->getHandler()->handle($this->buildValidEvent());

        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItCleansUpStoredCreationWhenIterationIsNoLongerValid(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Iteration tracker.
        $this->iteration_verifier = VerifyIsIterationStub::withNotIteration();
        $this->iteration_deleter->expects(self::atLeastOnce())->method('deletePendingIterationCreationsByIterationId');

        $this->getHandler()->handle($this->buildValidEvent());
    }

    public function testItCleansUpStoredCreationWhenProgramIncrementIsNoLongerValid(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Program Increment tracker.
        $this->program_increment_checker = CheckProgramIncrementStub::buildOtherArtifactChecker();
        $this->iteration_deleter->expects(self::atLeastOnce())->method('deletePendingIterationCreationsByProgramIncrementId');

        $this->getHandler()->handle($this->buildValidEvent());
    }

    public function testItSkipsCreationWhenUserIsInvalid(): void
    {
        // It should not happen unless the database has been filled with wrong information
        $this->user_verifier = VerifyIsUserStub::withNotValidUser();

        $this->getHandler()->handle($this->buildValidEvent());

        self::assertFalse($this->logger->hasDebugRecords());
    }

    private function buildValidEvent(): ?ProgramIncrementUpdateEvent
    {
        $worker_event = new WorkerEvent($this->logger, [
            'event_name' => ProgramIncrementUpdateEvent::TOPIC,
            'payload'    => [
                'artifact_id' => self::PROGRAM_INCREMENT_ID,
                'user_id'     => self::USER_ID,
            ]
        ]);
        return ProgramIncrementUpdateEventProxy::fromWorkerEvent($this->logger, $worker_event);
    }
}
