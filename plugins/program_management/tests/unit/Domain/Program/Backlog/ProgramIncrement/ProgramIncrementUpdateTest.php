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
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\PendingProgramIncrementUpdateProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoredChangesetNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoredProgramIncrementNoLongerValidException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoredUserNotFoundException;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
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
    private VerifyIsProgramIncrementTrackerStub $tracker_verifier;
    private ArtifactUpdatedProxy $artifact_updated;
    private VerifyIsUserStub $user_verifier;
    private VerifyIsProgramIncrementStub $program_increment_verifier;
    private VerifyIsVisibleArtifactStub $visibility_verifier;
    private VerifyIsChangesetStub $changeset_verifier;
    private RetrieveProgramIncrementTrackerStub $tracker_retriever;
    private PendingProgramIncrementUpdateProxy $pending_update;

    protected function setUp(): void
    {
        $this->tracker_verifier           = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();
        $this->user_verifier              = VerifyIsUserStub::withValidUser();
        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withValidProgramIncrement();
        $this->visibility_verifier        = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $this->changeset_verifier         = VerifyIsChangesetStub::withValidChangeset();
        $this->tracker_retriever          = RetrieveProgramIncrementTrackerStub::withValidTracker(
            self::PROGRAM_INCREMENT_TRACKER_ID
        );

        $this->pending_update = new PendingProgramIncrementUpdateProxy(
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::CHANGESET_ID
        );

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
            $this->tracker_verifier,
            $this->artifact_updated
        );
        self::assertSame(self::PROGRAM_INCREMENT_ID, $update->program_increment->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $update->tracker->getId());
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

    public function testItBuildsFromPendingProgramIncrementUpdate(): void
    {
        $update = ProgramIncrementUpdate::fromPendingUpdate(
            $this->user_verifier,
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $this->changeset_verifier,
            $this->tracker_retriever,
            $this->pending_update
        );
        self::assertSame(self::PROGRAM_INCREMENT_ID, $update->program_increment->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $update->tracker->getId());
        self::assertSame(self::USER_ID, $update->user->getId());
        self::assertSame(self::CHANGESET_ID, $update->changeset->getId());
    }

    public function testItThrowsWhenStoredUserIsNotValid(): void
    {
        // It's not supposed to happen as users cannot be deleted in Tuleap. They change status.
        $this->expectException(StoredUserNotFoundException::class);
        ProgramIncrementUpdate::fromPendingUpdate(
            VerifyIsUserStub::withNotValidUser(),
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $this->changeset_verifier,
            $this->tracker_retriever,
            $this->pending_update
        );
    }

    public function testItThrowsWhenStoredProgramIncrementIsNotValid(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Program Increment tracker.
        $this->expectException(StoredProgramIncrementNoLongerValidException::class);
        ProgramIncrementUpdate::fromPendingUpdate(
            $this->user_verifier,
            VerifyIsProgramIncrementStub::withNotProgramIncrement(),
            $this->visibility_verifier,
            $this->changeset_verifier,
            $this->tracker_retriever,
            $this->pending_update
        );
    }

    public function testItThrowsWhenStoredChangesetIsNotValid(): void
    {
        // It's not supposed to happen as changesets cannot be deleted in Tuleap.
        $this->expectException(StoredChangesetNotFoundException::class);
        ProgramIncrementUpdate::fromPendingUpdate(
            $this->user_verifier,
            $this->program_increment_verifier,
            $this->visibility_verifier,
            VerifyIsChangesetStub::withNotValidChangeset(),
            $this->tracker_retriever,
            $this->pending_update
        );
    }
}
