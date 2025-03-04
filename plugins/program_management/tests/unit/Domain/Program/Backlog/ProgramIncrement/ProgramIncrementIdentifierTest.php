<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactCreatedEventStub;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactUpdatedEventStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID = 96;
    private UserIdentifier $user;
    private VerifyIsProgramIncrementStub $program_increment_verifier;
    private VerifyIsVisibleArtifactStub $visibility_verifier;
    private VerifyIsProgramIncrementTrackerStub $tracker_verifier;
    private ArtifactUpdatedEventStub $artifact_updated;
    private ArtifactCreatedEventStub $artifact_created;

    protected function setUp(): void
    {
        $user_id    = 101;
        $this->user = UserIdentifierStub::withId($user_id);

        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withValidProgramIncrement();
        $this->visibility_verifier        = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $this->tracker_verifier           = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();

        $tracker_id             = 127;
        $changeset_id           = 919;
        $old_changeset_id       = 918;
        $this->artifact_updated = ArtifactUpdatedEventStub::withIds(
            self::PROGRAM_INCREMENT_ID,
            $tracker_id,
            $user_id,
            $changeset_id,
            $old_changeset_id
        );
        $this->artifact_created = ArtifactCreatedEventStub::withIds(
            self::PROGRAM_INCREMENT_ID,
            $tracker_id,
            $user_id,
            $changeset_id
        );
    }

    public function testItBuildsFromId(): void
    {
        $program_increment = ProgramIncrementIdentifier::fromId(
            $this->program_increment_verifier,
            $this->visibility_verifier,
            self::PROGRAM_INCREMENT_ID,
            $this->user
        );
        self::assertSame(self::PROGRAM_INCREMENT_ID, $program_increment->getId());
    }

    public function testItThrowsAnExceptionWhenIdIsNotAProgramIncrement(): void
    {
        $this->expectException(ProgramIncrementNotFoundException::class);
        ProgramIncrementIdentifier::fromId(
            VerifyIsProgramIncrementStub::withNotProgramIncrement(),
            $this->visibility_verifier,
            1,
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenArtifactIsNotVisibleByUser(): void
    {
        $this->expectException(ProgramIncrementNotFoundException::class);
        ProgramIncrementIdentifier::fromId(
            $this->program_increment_verifier,
            VerifyIsVisibleArtifactStub::withNoVisibleArtifact(),
            404,
            $this->user
        );
    }

    public function testItReturnsNullWhenArtifactFromEventIsNotAProgramIncrement(): void
    {
        self::assertNull(
            ProgramIncrementIdentifier::fromArtifactEvent(
                VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement(),
                $this->artifact_created
            )
        );
        self::assertNull(
            ProgramIncrementIdentifier::fromArtifactEvent(
                VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement(),
                $this->artifact_updated
            )
        );
    }

    public function testItReturnsAProgramIncrementFromArtifactCreatedEvent(): void
    {
        $program_increment = ProgramIncrementIdentifier::fromArtifactEvent(
            $this->tracker_verifier,
            $this->artifact_created
        );
        self::assertSame(self::PROGRAM_INCREMENT_ID, $program_increment?->getId());
    }

    public function testItReturnsAProgramIncrementFromArtifactUpdatedEvent(): void
    {
        $program_increment = ProgramIncrementIdentifier::fromArtifactEvent(
            $this->tracker_verifier,
            $this->artifact_updated
        );
        self::assertSame(self::PROGRAM_INCREMENT_ID, $program_increment?->getId());
    }
}
