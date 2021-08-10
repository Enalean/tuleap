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
use Tuleap\ProgramManagement\Adapter\Events\IterationCreationEventProxy;
use Tuleap\ProgramManagement\Domain\Events\IterationCreationEvent;
use Tuleap\ProgramManagement\Tests\Stub\CheckProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchPendingIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Test\Builders\UserTestBuilder;

final class IterationCreationEventHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_ID = 13;
    private const USER_ID      = 108;
    private TestLogger $logger;
    private SearchPendingIterationStub $iteration_searcher;
    private CheckProgramIncrementStub $program_increment_checker;
    private RetrieveUserStub $user_retriever;
    private VerifyIsIterationStub $iteration_verifier;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|DeletePendingIterations
     */
    private $iteration_deleter;

    protected function setUp(): void
    {
        $this->logger                    = new TestLogger();
        $this->iteration_searcher        = SearchPendingIterationStub::withRow(
            self::ITERATION_ID,
            2,
            self::USER_ID,
            5457
        );
        $this->iteration_verifier        = VerifyIsIterationStub::withValidIteration();
        $this->user_retriever            = RetrieveUserStub::withUser(
            UserTestBuilder::aUser()->withId(self::USER_ID)->build()
        );
        $this->program_increment_checker = CheckProgramIncrementStub::buildProgramIncrementChecker();
        $this->iteration_deleter         = $this->createMock(DeletePendingIterations::class);
    }

    private function getHandler(): IterationCreationEventHandler
    {
        return new IterationCreationEventHandler(
            $this->logger,
            $this->iteration_searcher,
            VerifyIsUserStub::withValidUser(),
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

        self::assertTrue($this->logger->hasDebug('Processing iteration creation with iteration #13 for user #108'));
    }

    public function testItDoesNothingWhenEventIsNull(): void
    {
        $invalid_worker_event = new WorkerEvent($this->logger, [
            'event_name' => 'unrelated.topic',
            'payload'    => [],
        ]);
        $event                = IterationCreationEventProxy::fromWorkerEvent($this->logger, $invalid_worker_event);

        $this->getHandler()->handle($event);

        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItDoesNothingWhenArtifactFromStoredCreationHasBeenDeleted(): void
    {
        // For example when iteration or program increment are deleted, the store will return null
        $this->iteration_searcher = SearchPendingIterationStub::withNoRow();

        $this->getHandler()->handle($this->buildValidEvent());

        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItCleansUpStoredCreationWhenIterationIsNoLongerValid(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Iteration tracker.
        $this->iteration_verifier = VerifyIsIterationStub::withNotIteration();
        $this->iteration_deleter->expects(self::once())->method('deletePendingIterationCreationsByIterationId');

        $this->getHandler()->handle($this->buildValidEvent());
    }

    public function testItCleansUpStoredCreationWhenProgramIncrementIsNoLongerValid(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Program Increment tracker.
        $this->program_increment_checker = CheckProgramIncrementStub::buildOtherArtifactChecker();
        $this->iteration_deleter->expects(self::once())->method('deletePendingIterationCreationsByProgramIncrementId');

        $this->getHandler()->handle($this->buildValidEvent());
    }

    private function buildValidEvent(): ?IterationCreationEvent
    {
        $worker_event = new WorkerEvent($this->logger, [
            'event_name' => IterationCreationEvent::TOPIC,
            'payload'    => [
                'artifact_id' => self::ITERATION_ID,
                'user_id'     => self::USER_ID,
            ]
        ]);
        return IterationCreationEventProxy::fromWorkerEvent($this->logger, $worker_event);
    }
}
