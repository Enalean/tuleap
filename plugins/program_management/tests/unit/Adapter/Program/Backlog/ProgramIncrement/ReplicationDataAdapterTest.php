<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingArtifactCreationStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoredProgramIncrementNoLongerValidException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactChangesetNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactUserNotFoundException;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ReplicationDataAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ARTIFACT_ID          = 1;
    private const USER_ID              = 101;
    private const SUBMISSION_TIMESTAMP = 1234567890;
    private const TRACKER_ID           = 10;
    private const CHANGESET_ID         = 666;
    private const PROJECT_ID           = 158;

    private Stub|\Tracker_Artifact_ChangesetFactory $changeset_factory;
    private Stub|PendingArtifactCreationStore $pending_artifact_creation_store;
    private Stub|\UserManager $user_manager;
    private Stub|\Tracker_ArtifactFactory $artifact_factory;
    private VerifyIsProgramIncrementTrackerStub $program_increment_verifier;
    private array $pending_row;
    private Artifact $artifact;
    private \PFUser $user;
    private \Tracker_Artifact_Changeset $changeset;

    protected function setUp(): void
    {
        $this->artifact_factory                = $this->createStub(\Tracker_ArtifactFactory::class);
        $this->user_manager                    = $this->createStub(\UserManager::class);
        $this->pending_artifact_creation_store = $this->createStub(PendingArtifactCreationStore::class);
        $this->changeset_factory               = $this->createStub(\Tracker_Artifact_ChangesetFactory::class);
        $this->program_increment_verifier      = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();

        $this->pending_row = ['program_artifact_id' => self::ARTIFACT_ID, 'user_id' => self::USER_ID, 'changeset_id' => self::CHANGESET_ID];
        $project           = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $tracker           = TrackerTestBuilder::aTracker()
            ->withId(self::TRACKER_ID)
            ->withProject($project)
            ->build();
        $this->artifact    = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)
            ->withSubmissionTimestamp(self::SUBMISSION_TIMESTAMP)
            ->inTracker($tracker)
            ->build();
        $this->user        = UserTestBuilder::aUser()->withId(self::USER_ID)->build();

        $this->changeset = new \Tracker_Artifact_Changeset(
            self::CHANGESET_ID,
            $this->artifact,
            self::USER_ID,
            self::SUBMISSION_TIMESTAMP,
            null
        );
    }

    private function getAdapter(): ReplicationDataAdapter
    {
        return new ReplicationDataAdapter(
            $this->artifact_factory,
            $this->user_manager,
            $this->pending_artifact_creation_store,
            $this->changeset_factory,
            $this->program_increment_verifier
        );
    }

    public function testReturnsNullWhenPendingArtifactIsNotFoundInDB(): void
    {
        $this->pending_artifact_creation_store->method('getPendingArtifactById')->willReturn(null);

        self::assertNull($this->getAdapter()->buildFromArtifactAndUserId(self::ARTIFACT_ID, self::USER_ID));
    }

    public function testItThrowsWhenPendingArtifactIsNotFound(): void
    {
        $this->pending_artifact_creation_store->method('getPendingArtifactById')->willReturn($this->pending_row);
        $this->artifact_factory->method('getArtifactById')->willReturn(null);

        $this->expectException(PendingArtifactNotFoundException::class);
        $this->getAdapter()->buildFromArtifactAndUserId(self::ARTIFACT_ID, self::USER_ID);
    }

    public function testItThrowsWhenArtifactIsNotAProgramIncrement(): void
    {
        $this->pending_artifact_creation_store->method('getPendingArtifactById')->willReturn($this->pending_row);
        $this->artifact_factory->method('getArtifactById')->willReturn($this->artifact);
        $this->program_increment_verifier = VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement();

        $this->expectException(StoredProgramIncrementNoLongerValidException::class);
        $this->getAdapter()->buildFromArtifactAndUserId(self::ARTIFACT_ID, self::USER_ID);
    }

    public function testItThrowsWhenUserIsNotFound(): void
    {
        $this->pending_artifact_creation_store->method('getPendingArtifactById')->willReturn($this->pending_row);
        $this->artifact_factory->method('getArtifactById')->willReturn($this->artifact);
        $this->user_manager->method('getUserById')->willReturn(null);

        $this->expectException(PendingArtifactUserNotFoundException::class);
        $this->getAdapter()->buildFromArtifactAndUserId(self::ARTIFACT_ID, self::USER_ID);
    }

    public function testItThrowsWhenChangesetIsNotFound(): void
    {
        $this->pending_artifact_creation_store->method('getPendingArtifactById')->willReturn($this->pending_row);
        $this->artifact_factory->method('getArtifactById')->willReturn($this->artifact);
        $this->user_manager->method('getUserById')->willReturn($this->user);
        $this->changeset_factory->method('getChangeset')->willReturn(null);

        $this->expectException(PendingArtifactChangesetNotFoundException::class);
        $this->getAdapter()->buildFromArtifactAndUserId(self::ARTIFACT_ID, self::USER_ID);
    }

    public function testItBuildsReplicationData(): void
    {
        $this->pending_artifact_creation_store->method('getPendingArtifactById')->willReturn($this->pending_row);
        $this->artifact_factory->method('getArtifactById')->willReturn($this->artifact);
        $this->user_manager->method('getUserById')->willReturn($this->user);
        $this->changeset_factory->method('getChangeset')->willReturn($this->changeset);

        $replication = $this->getAdapter()->buildFromArtifactAndUserId(self::ARTIFACT_ID, self::USER_ID);
        self::assertSame(self::ARTIFACT_ID, $replication->getArtifact()->getId());
        self::assertSame(self::SUBMISSION_TIMESTAMP, $replication->getArtifact()->getSubmittedOn());
        self::assertSame(self::USER_ID, $replication->getUserIdentifier()->getId());
        self::assertSame(self::CHANGESET_ID, $replication->getChangeset()->getId());
        self::assertSame(self::TRACKER_ID, $replication->getTracker()->getId());
        self::assertSame(self::PROJECT_ID, $replication->getProject()->getId());
    }
}
