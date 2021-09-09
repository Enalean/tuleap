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
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingArtifactCreationStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoredProgramIncrementNoLongerValidException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactChangesetNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactUserNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Artifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\BuildReplicationData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;

final class ReplicationDataAdapter implements BuildReplicationData
{
    public function __construct(
        private \Tracker_ArtifactFactory $artifact_factory,
        private \UserManager $user_manager,
        private PendingArtifactCreationStore $pending_artifact_creation_store,
        private \Tracker_Artifact_ChangesetFactory $changeset_factory,
        private VerifyIsProgramIncrementTracker $program_increment_verifier
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
        $source_artifact      = $this->artifact_factory->getArtifactById($program_increment_id);
        if (! $source_artifact) {
            throw new PendingArtifactNotFoundException(
                $program_increment_id,
                $pending_artifact['user_id']
            );
        }

        $tracker = ProgramIncrementTrackerIdentifier::fromId(
            $this->program_increment_verifier,
            $source_artifact->getTrackerId()
        );
        if (! $tracker) {
            throw new StoredProgramIncrementNoLongerValidException($program_increment_id);
        }

        $user = $this->user_manager->getUserById($pending_artifact['user_id']);
        if (! $user) {
            throw new PendingArtifactUserNotFoundException(
                $program_increment_id,
                $pending_artifact['user_id']
            );
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

        return self::build($source_artifact, $user, $source_changeset, $tracker);
    }

    public static function build(
        \Tuleap\Tracker\Artifact\Artifact $source_artifact,
        \PFUser $user,
        \Tracker_Artifact_Changeset $source_changeset,
        ProgramIncrementTrackerIdentifier $source_tracker
    ): ReplicationData {
        $artifact_data   = new Artifact((int) $source_artifact->getId());
        $changeset       = ChangesetProxy::fromChangeset($source_changeset);
        $project_data    = ProgramManagementProjectAdapter::build($source_artifact->getTracker()->getProject());
        $user_identifier = UserProxy::buildFromPFUser($user);

        return new ReplicationData($source_tracker, $changeset, $artifact_data, $project_data, $user_identifier);
    }
}
