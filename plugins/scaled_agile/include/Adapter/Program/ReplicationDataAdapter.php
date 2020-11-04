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

namespace Tuleap\ScaledAgile\Adapter\Program;

use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\PendingArtifactChangesetNotFoundException;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\PendingArtifactCreationDao;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\PendingArtifactNotFoundException;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\PendingArtifactUserNotFoundException;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\ArtifactData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ScaledAgile\Adapter\ProjectDataAdapter;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use UserManager;

final class ReplicationDataAdapter
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var PendingArtifactCreationDao
     */
    private $pending_artifact_creation_dao;
    /**
     * @var \Tracker_Artifact_ChangesetFactory
     */
    private $changeset_factory;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        UserManager $user_manager,
        PendingArtifactCreationDao $pending_artifact_creation_dao,
        Tracker_Artifact_ChangesetFactory $changeset_factory
    ) {
        $this->artifact_factory              = $artifact_factory;
        $this->user_manager                  = $user_manager;
        $this->pending_artifact_creation_dao = $pending_artifact_creation_dao;
        $this->changeset_factory             = $changeset_factory;
    }

    public function buildFromArtifactAndUserId(int $artifact_id, int $user_id): ReplicationData
    {
        $pending_artifact = $this->pending_artifact_creation_dao->getPendingArtifactById(
            $artifact_id,
            $user_id
        );

        if ($pending_artifact === null) {
            throw new PendingArtifactNotFoundException($artifact_id, $user_id);
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

        return $this->build($source_artifact, $user, $source_changeset);
    }

    public static function build(
        \Tuleap\Tracker\Artifact\Artifact $source_artifact,
        \PFUser $user,
        \Tracker_Artifact_Changeset $source_changeset
    ): ReplicationData {
        $artifact_data = new ArtifactData((int) $source_artifact->getId(), (int) $source_artifact->getSubmittedOn());
        $tracker_data = TrackerDataAdapter::build($source_artifact->getTracker());
        $project_data = ProjectDataAdapter::build($source_artifact->getTracker()->getProject());

        return new ReplicationData($tracker_data, $source_changeset, $user, $artifact_data, $project_data);
    }
}
