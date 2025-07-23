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

use Tuleap\ProgramManagement\Tests\Stub\ArtifactUpdatedEventStub;
use Tuleap\ProgramManagement\Tests\Stub\ProgramIncrementUpdateEventStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementUpdateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID         = 120;
    private const PROGRAM_INCREMENT_TRACKER_ID = 88;
    private const USER_ID                      = 183;
    private const CHANGESET_ID                 = 8996;
    private const OLD_CHANGESET_ID             = 8995;
    private VerifyIsProgramIncrementTrackerStub $tracker_verifier;
    private RetrieveProgramIncrementTrackerStub $tracker_retriever;
    private ArtifactUpdatedEventStub $artifact_updated;
    private ProgramIncrementUpdateEventStub $update_event;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker_verifier  = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();
        $this->tracker_retriever = RetrieveProgramIncrementTrackerStub::withValidTracker(
            self::PROGRAM_INCREMENT_TRACKER_ID
        );

        $this->artifact_updated = ArtifactUpdatedEventStub::withIds(
            self::PROGRAM_INCREMENT_ID,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            self::USER_ID,
            self::CHANGESET_ID,
            self::OLD_CHANGESET_ID
        );

        $this->update_event = ProgramIncrementUpdateEventStub::withIds(
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::CHANGESET_ID,
            self::OLD_CHANGESET_ID
        );
    }

    public function testItBuildsFromArtifactUpdatedEvent(): void
    {
        $update = ProgramIncrementUpdate::fromArtifactUpdatedEvent(
            $this->tracker_verifier,
            $this->artifact_updated
        );
        self::assertNotNull($update);
        self::assertSame(self::PROGRAM_INCREMENT_ID, $update->getProgramIncrement()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_ID, $update->getTimebox()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $update->getProgramIncrementTracker()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $update->getTracker()->getId());
        self::assertSame(self::CHANGESET_ID, $update->getChangeset()->getId());
        self::assertSame(self::OLD_CHANGESET_ID, $update->getOldChangeset()->getId());
        self::assertSame(self::USER_ID, $update->getUser()->getId());
    }

    public function testItReturnsNullWhenArtifactIsNotAProgramIncrement(): void
    {
        self::assertNull(
            ProgramIncrementUpdate::fromArtifactUpdatedEvent(
                VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement(),
                $this->artifact_updated
            )
        );
    }

    public function testItBuildsFromProgramIncrementUpdateEvent(): void
    {
        $update = ProgramIncrementUpdate::fromProgramIncrementUpdateEvent(
            $this->tracker_retriever,
            $this->update_event
        );
        self::assertNotNull($update);
        self::assertSame(self::PROGRAM_INCREMENT_ID, $update->getProgramIncrement()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_ID, $update->getTimebox()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $update->getProgramIncrementTracker()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $update->getTracker()->getId());
        self::assertSame(self::USER_ID, $update->getUser()->getId());
        self::assertSame(self::CHANGESET_ID, $update->getChangeset()->getId());
        self::assertSame(self::OLD_CHANGESET_ID, $update->getOldChangeset()->getId());
    }
}
