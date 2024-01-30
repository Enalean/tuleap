<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use PFUser;
use Tracker;
use Tuleap\Tracker\Artifact\Artifact;

class ArtifactsDeletionManager
{
    public function __construct(
        private readonly ArtifactsDeletionDAO $dao,
        private readonly ArtifactDeletor $artifact_deletor,
        private readonly ArtifactDeletionLimitRetriever $deletion_limit_retriever,
    ) {
    }

    /**
     * @return int The remaining number of artifacts allowed to delete
     *
     * @throws ArtifactsDeletionLimitReachedException
     * @throws DeletionOfArtifactsIsNotAllowedException
     */
    public function deleteArtifact(
        Artifact $artifact,
        PFUser $user,
    ): int {
        $remaining_deletions = $this->deletion_limit_retriever->getNumberOfArtifactsAllowedToDelete($user) - 1;

        $project_id = (int) $artifact->getTracker()->getGroupId();
        $this->artifact_deletor->delete($artifact, $user, DeletionContext::regularDeletion($project_id));
        $this->dao->recordDeletionForUser($user->getId(), time());

        return $remaining_deletions;
    }

    /**
     * @throws ArtifactsDeletionLimitReachedException
     * @throws DeletionOfArtifactsIsNotAllowedException
     */
    public function deleteArtifactBeforeMoveOperation(
        Artifact $artifact,
        PFUser $user,
        Tracker $destination_tracker,
    ): void {
        $this->artifact_deletor->deleteWithoutTransaction(
            $artifact,
            $user,
            DeletionContext::moveContext((int) $artifact->getTracker()->getGroupId(), (int) $destination_tracker->getGroupId())
        );
    }
}
