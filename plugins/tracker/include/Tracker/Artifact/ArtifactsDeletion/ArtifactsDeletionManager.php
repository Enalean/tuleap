<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
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
use SystemEventDao;
use Tracker_Artifact;

class ArtifactsDeletionManager
{
    /**
     * @var SystemEventDao
     */
    private $dao;
    /**
     * @var ArtifactDeletor
     */
    private $artifact_deletor;
    /**
     * @var ArtifactDeletionLimitRetriever
     */
    private $deletion_limit_retriever;

    public function __construct(
        ArtifactsDeletionDAO $dao,
        ArtifactDeletor $artifact_deletor,
        ArtifactDeletionLimitRetriever $deletion_limit_retriever
    ) {
        $this->dao                     = $dao;
        $this->artifact_deletor        = $artifact_deletor;
        $this->deletion_limit_retriever = $deletion_limit_retriever;
    }

    /**
     * @return int The remaining number of artifacts allowed to delete
     *
     * @throws ArtifactsDeletionLimitReachedException
     * @throws DeletionOfArtifactsIsNotAllowedException
     */
    public function deleteArtifact(
        Tracker_Artifact $artifact,
        PFUser $user
    ) {
        $remaining_deletions = $this->deletion_limit_retriever->getNumberOfArtifactsAllowedToDelete($user) - 1;

        $this->artifact_deletor->delete($artifact, $user);
        $this->dao->recordDeletionForUser($user->getId(), time());

        return $remaining_deletions;
    }

    /**
     * @return int The remaining number of artifacts allowed to delete
     *
     * @throws ArtifactsDeletionLimitReachedException
     * @throws DeletionOfArtifactsIsNotAllowedException
     */
    public function deleteArtifactBeforeMoveOperation(
        Tracker_Artifact $artifact,
        PFUser $user
    ) {
        $remaining_deletions = $this->deletion_limit_retriever->getNumberOfArtifactsAllowedToDelete($user) - 1;

        $this->artifact_deletor->deleteWithoutTransaction($artifact, $user);
        $this->dao->recordDeletionForUser($user->getId(), time());

        return $remaining_deletions;
    }
}
