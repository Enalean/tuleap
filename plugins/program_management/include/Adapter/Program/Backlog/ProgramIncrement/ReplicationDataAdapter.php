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

use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\ChangesetProxy;
use Tuleap\ProgramManagement\Adapter\ProgramManagementProjectAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\TrackerIdentifierProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\BuildProject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingArtifactCreationStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoredProgramIncrementNoLongerValidException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactChangesetNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactUserNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\BuildReplicationData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfProgramIncrement;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;

final class ReplicationDataAdapter implements BuildReplicationData
{
    public function __construct(
        private \Tracker_ArtifactFactory $artifact_factory,
        private \UserManager $user_manager,
        private PendingArtifactCreationStore $pending_artifact_creation_store,
        private \Tracker_Artifact_ChangesetFactory $changeset_factory,
        private VerifyIsProgramIncrementTracker $tracker_verifier,
        private RetrieveProgramOfProgramIncrement $program_retriever,
        private BuildProject $project_builder,
        private VerifyIsProgramIncrement $program_increment_verifier,
        private VerifyIsVisibleArtifact $visibility_verifier
    ) {
    }

    public function buildFromArtifactAndUserId(int $artifact_id, int $user_id): ?ReplicationData
    {
        $pending_artifact = $this->pending_artifact_creation_store->getPendingArtifactById(
            $artifact_id,
            $user_id
        );
        if ($pending_artifact === null) {
            return null;
        }
        $program_increment_id = $pending_artifact['program_artifact_id'];
        $user_id_from_storage = $pending_artifact['user_id'];

        $user = $this->user_manager->getUserById($user_id_from_storage);
        if (! $user) {
            throw new PendingArtifactUserNotFoundException($program_increment_id, $user_id_from_storage);
        }
        $user_identifier = UserProxy::buildFromPFUser($user);

        try {
            $program_increment = ProgramIncrementIdentifier::fromId(
                $this->program_increment_verifier,
                $this->visibility_verifier,
                $program_increment_id,
                $user_identifier
            );
        } catch (ProgramIncrementNotFoundException $e) {
            throw new PendingArtifactNotFoundException($program_increment_id, $user_id_from_storage);
        }
        $source_artifact = $this->artifact_factory->getArtifactById($program_increment_id);
        if (! $source_artifact) {
            throw new PendingArtifactNotFoundException($program_increment_id, $user_id_from_storage);
        }

        $tracker = ProgramIncrementTrackerIdentifier::fromId(
            $this->tracker_verifier,
            TrackerIdentifierProxy::fromTracker($source_artifact->getTracker())
        );
        if (! $tracker) {
            throw new StoredProgramIncrementNoLongerValidException($program_increment_id);
        }

        $source_changeset = $this->changeset_factory->getChangeset(
            $source_artifact,
            $pending_artifact['changeset_id']
        );
        if (! $source_changeset) {
            throw new PendingArtifactChangesetNotFoundException(
                $program_increment_id,
                $pending_artifact['changeset_id']
            );
        }

        $changeset    = ChangesetProxy::fromChangeset($source_changeset);
        $project_data = ProgramManagementProjectAdapter::build($source_artifact->getTracker()->getProject());

        return new ReplicationData($tracker, $changeset, $program_increment, $project_data, $user_identifier);
    }

    public function buildFromProgramIncrementCreation(ProgramIncrementCreation $creation): ReplicationData
    {
        $program_id = $this->program_retriever->getProgramOfProgramIncrement($creation->getProgramIncrement());
        $project    = $this->project_builder->buildFromId($program_id);
        return new ReplicationData(
            $creation->getProgramIncrementTracker(),
            $creation->getChangeset(),
            $creation->getProgramIncrement(),
            $project,
            $creation->getUser()
        );
    }
}
