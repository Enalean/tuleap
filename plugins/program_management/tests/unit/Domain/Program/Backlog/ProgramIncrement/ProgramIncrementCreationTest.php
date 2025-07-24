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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Tests\Stub\ArtifactCreatedEventStub;
use Tuleap\ProgramManagement\Tests\Stub\ProgramIncrementCreationEventStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementCreationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID         = 195;
    private const PROGRAM_INCREMENT_TRACKER_ID = 88;
    private const USER_ID                      = 106;
    private const CHANGESET_ID                 = 6023;
    private VerifyIsProgramIncrementTrackerStub $tracker_verifier;
    private VerifyIsProgramIncrementStub $program_increment_verifier;
    private VerifyIsVisibleArtifactStub $visibility_verifier;
    private VerifyIsChangesetStub $changeset_verifier;
    private RetrieveProgramIncrementTrackerStub $tracker_retriever;
    private ArtifactCreatedEventStub $artifact_created;
    private ProgramIncrementCreationEventStub $creation_event;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker_verifier           = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();
        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withValidProgramIncrement();
        $this->visibility_verifier        = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $this->changeset_verifier         = VerifyIsChangesetStub::withValidChangeset();
        $this->tracker_retriever          = RetrieveProgramIncrementTrackerStub::withValidTracker(
            self::PROGRAM_INCREMENT_TRACKER_ID
        );

        $this->artifact_created = ArtifactCreatedEventStub::withIds(
            self::PROGRAM_INCREMENT_ID,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            self::USER_ID,
            self::CHANGESET_ID
        );

        $this->creation_event = ProgramIncrementCreationEventStub::withIds(
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::CHANGESET_ID
        );
    }

    public function testItBuildsFromArtifactCreatedEvent(): void
    {
        $creation = ProgramIncrementCreation::fromArtifactCreatedEvent(
            $this->tracker_verifier,
            $this->artifact_created
        );
        self::assertSame(self::PROGRAM_INCREMENT_ID, $creation?->getProgramIncrement()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_ID, $creation?->getTimebox()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $creation?->getProgramIncrementTracker()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $creation?->getTracker()->getId());
        self::assertSame(self::CHANGESET_ID, $creation?->getChangeset()->getId());
        self::assertSame(self::USER_ID, $creation?->getUser()->getId());
    }

    public function testItReturnsNullWhenArtifactIsNotAProgramIncrement(): void
    {
        self::assertNull(
            ProgramIncrementCreation::fromArtifactCreatedEvent(
                VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement(),
                $this->artifact_created
            )
        );
    }

    public function testItBuildsFromProgramIncrementCreationEvent(): void
    {
        $creation = ProgramIncrementCreation::fromProgramIncrementCreationEvent(
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $this->changeset_verifier,
            $this->tracker_retriever,
            $this->creation_event
        );
        self::assertSame(self::PROGRAM_INCREMENT_ID, $creation?->getProgramIncrement()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_ID, $creation?->getTimebox()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $creation?->getProgramIncrementTracker()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $creation?->getTracker()->getId());
        self::assertSame(self::CHANGESET_ID, $creation?->getChangeset()->getId());
        self::assertSame(self::USER_ID, $creation?->getUser()->getId());
    }

    public function testItReturnsNullWhenArtifactFromEventIsNotAProgramIncrement(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Program Increment tracker.
        self::assertNull(
            ProgramIncrementCreation::fromProgramIncrementCreationEvent(
                VerifyIsProgramIncrementStub::withNotProgramIncrement(),
                $this->visibility_verifier,
                $this->changeset_verifier,
                $this->tracker_retriever,
                $this->creation_event
            )
        );
    }

    public function testItReturnsNullWhenChangesetFromEventIsNotValid(): void
    {
        // It's not supposed to happen as changesets cannot be deleted in Tuleap.
        self::assertNull(
            ProgramIncrementCreation::fromProgramIncrementCreationEvent(
                $this->program_increment_verifier,
                $this->visibility_verifier,
                VerifyIsChangesetStub::withNotValidChangeset(),
                $this->tracker_retriever,
                $this->creation_event
            )
        );
    }
}
