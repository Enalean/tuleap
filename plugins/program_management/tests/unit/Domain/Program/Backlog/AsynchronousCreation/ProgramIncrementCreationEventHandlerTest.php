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

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Adapter\Events\ProgramIncrementCreationEventProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Tests\Stub\ProcessProgramIncrementCreationStub;
use Tuleap\ProgramManagement\Tests\Stub\ProgramIncrementCreationEventStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramIncrementCreationProcessorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Queue\WorkerEvent;

final class ProgramIncrementCreationEventHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID         = 38;
    private const USER_ID                      = 173;
    private const PROGRAM_INCREMENT_TRACKER_ID = 22;
    private const CHANGESET_ID                 = 5265;
    private TestLogger $logger;
    private VerifyIsProgramIncrementStub $program_increment_verifier;
    private ProcessProgramIncrementCreationStub $processor;
    private ProgramIncrementCreationEventStub $event;

    protected function setUp(): void
    {
        $this->logger                     = new TestLogger();
        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withValidProgramIncrement();
        $this->processor                  = ProcessProgramIncrementCreationStub::withCount();

        $this->event = ProgramIncrementCreationEventStub::withIds(
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::CHANGESET_ID
        );
    }

    private function getHandler(): ProgramIncrementCreationEventHandler
    {
        return new ProgramIncrementCreationEventHandler(
            MessageLog::buildFromLogger($this->logger),
            $this->program_increment_verifier,
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            VerifyIsChangesetStub::withValidChangeset(),
            RetrieveProgramIncrementTrackerStub::withValidTracker(self::PROGRAM_INCREMENT_TRACKER_ID),
            BuildProgramIncrementCreationProcessorStub::withProcessor($this->processor)
        );
    }

    public function testItProcessesValidEvent(): void
    {
        $this->getHandler()->handle($this->event);

        self::assertSame(1, $this->processor->getCallCount());
    }

    public function testItDoesNothingWhenEventIsNull(): void
    {
        $invalid_worker_event = new WorkerEvent($this->logger, [
            'event_name' => 'unrelated.topic',
            'payload'    => [],
        ]);
        $event                = ProgramIncrementCreationEventProxy::fromWorkerEvent(
            $this->logger,
            $this->createStub(\UserManager::class),
            $invalid_worker_event
        );

        $this->getHandler()->handle($event);

        self::assertSame(0, $this->processor->getCallCount());
    }

    public function testItIgnoresEventWhenArtifactIsNoLongerAProgramIncrement(): void
    {
        // It can take some time between the dispatch of the event in the Queue and its processing.
        // Someone could have deleted the Artifact from the event.
        // Someone could have changed the Program Increment tracker in the configuration.
        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withNotProgramIncrement();

        $this->getHandler()->handle($this->event);

        self::assertSame(0, $this->processor->getCallCount());
        self::assertTrue(
            $this->logger->hasError(
                sprintf(
                    'Invalid data given in payload, skipping program increment creation for artifact #%d, user #%d and changeset #%d',
                    self::PROGRAM_INCREMENT_ID,
                    self::USER_ID,
                    self::CHANGESET_ID
                )
            )
        );
    }
}
