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

use Tuleap\ProgramManagement\Adapter\Events\ArtifactUpdatedProxy;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementUpdateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID         = 120;
    private const PROGRAM_INCREMENT_TRACKER_ID = 88;
    private const USER_ID                      = 183;
    private const CHANGESET_ID                 = 8996;
    private VerifyIsProgramIncrementTrackerStub $program_increment_verifier;
    private ArtifactUpdatedProxy $artifact_updated;

    protected function setUp(): void
    {
        $this->program_increment_verifier = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();

        $user      = UserTestBuilder::aUser()->withId(self::USER_ID)->build();
        $tracker   = TrackerTestBuilder::aTracker()->withId(self::PROGRAM_INCREMENT_TRACKER_ID)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(self::PROGRAM_INCREMENT_ID)->inTracker($tracker)->build();
        $changeset = ChangesetTestBuilder::aChangeset((string) self::CHANGESET_ID)
            ->ofArtifact($artifact)
            ->submittedBy($user->getId())
            ->build();

        $tracker_event          = new ArtifactUpdated($artifact, $user, $changeset);
        $this->artifact_updated = ArtifactUpdatedProxy::fromArtifactUpdated($tracker_event);
    }

    public function testItBuildsFromArtifactUpdatedEvent(): void
    {
        $update = ProgramIncrementUpdate::fromArtifactUpdatedEvent(
            $this->program_increment_verifier,
            $this->artifact_updated
        );
        self::assertSame(self::PROGRAM_INCREMENT_ID, $update->program_increment->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $update->tracker->id);
        self::assertSame(self::CHANGESET_ID, $update->changeset->getId());
        self::assertSame(self::USER_ID, $update->user->getId());
    }

    public function testItReturnsNullWhenArtifactIsNotAProgramIncrement(): void
    {
        $update = ProgramIncrementUpdate::fromArtifactUpdatedEvent(
            VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement(),
            $this->artifact_updated
        );
        self::assertNull($update);
    }
}
