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

use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingArtifactCreationStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactChangesetNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PendingArtifactUserNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Artifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\BuildReplicationData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Domain\ProgramTracker;

final class ReplicationDataAdapter implements BuildReplicationData
{
    private \Tracker_ArtifactFactory $artifact_factory;
    private \UserManager $user_manager;
    private PendingArtifactCreationStore $pending_artifact_creation_store;
    private \Tracker_Artifact_ChangesetFactory $changeset_factory;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        \UserManager $user_manager,
        PendingArtifactCreationStore $pending_artifact_creation_store,
        \Tracker_Artifact_ChangesetFactory $changeset_factory
    ) {
        $this->artifact_factory                = $artifact_factory;
        $this->user_manager                    = $user_manager;
        $this->pending_artifact_creation_store = $pending_artifact_creation_store;
        $this->changeset_factory               = $changeset_factory;
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

        $source_artifact = $this->artifact_factory->getArtifactById($pending_artifact['program_artifact_id']);
        if (! $source_artifact) {
            throw new PendingArtifactNotFoundException(
                $pending_artifact['program_artifact_id'],
                $pending_artifact['user_id']
            );
        }

        $user = $this->user_manager->getUserById($pending_artifact['user_id']);
        if (! $user) {
            throw new PendingArtifactUserNotFoundException(
                $pending_artifact['program_artifact_id'],
                $pending_artifact['user_id']
            );
        }

        $source_changeset = $this->changeset_factory->getChangeset(
            $source_artifact,
            $pending_artifact['changeset_id']
        );
        if (! $source_changeset) {
            throw new PendingArtifactChangesetNotFoundException(
                $pending_artifact['program_artifact_id'],
                $pending_artifact['changeset_id']
            );
        }

        return self::build($source_artifact, $user, $source_changeset);
    }

    public static function build(
        \Tuleap\Tracker\Artifact\Artifact $source_artifact,
        \PFUser $user,
        \Tracker_Artifact_Changeset $source_changeset
    ): ReplicationData {
        $artifact_data = new Artifact((int) $source_artifact->getId(), (int) $source_artifact->getSubmittedOn());
        $tracker_data  = new ProgramTracker($source_artifact->getTracker());
        $project_data  = ProjectAdapter::build($source_artifact->getTracker()->getProject());

        return new ReplicationData($tracker_data, $source_changeset, $user, $artifact_data, $project_data);
    }
}
