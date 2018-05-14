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
use Tuleap\Tracker\Admin\ArtifactDeletion\ArtifactsDeletionConfig;
use Tuleap\Tracker\Admin\ArtifactsDeletion\UserDeletionRetriever;

class ArtifactsDeletionManager
{
    /**
     * @var ArtifactsDeletionConfig
     */
    private $config;
    /**
     * @var SystemEventDao
     */
    private $dao;
    /**
     * @var ArtifactDeletor
     */
    private $artifact_deletor;
    /**
     * @var UserDeletionRetriever
     */
    private $user_deletion_retriever;

    public function __construct(
        ArtifactsDeletionConfig $config,
        ArtifactsDeletionDAO $dao,
        ArtifactDeletor $artifact_deletor,
        UserDeletionRetriever $user_deletion_retriever
    ) {
        $this->config                  = $config;
        $this->dao                     = $dao;
        $this->artifact_deletor        = $artifact_deletor;
        $this->user_deletion_retriever = $user_deletion_retriever;
    }

    /**
     * @return int The remaining number of artifacts allowed to delete
     *
     * @throws ArtifactsDeletionLimitReachedException
     * @throws DeletionOfArtifactsIsNotAllowedException
     */
    public function deleteArtifact(
        Tracker_artifact $artifact,
        PFUser $user
    ) {
        $remaining_deletions = $this->getNumberOfArtifactsAllowedToDelete($user);

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
        Tracker_artifact $artifact,
        PFUser $user
    ) {
        $remaining_deletions = $this->getNumberOfArtifactsAllowedToDelete($user);

        $this->artifact_deletor->deleteWithoutTransaction($artifact, $user);
        $this->dao->recordDeletionForUser($user->getId(), time());

        return $remaining_deletions;
    }

    /**
     * @throws ArtifactsDeletionLimitReachedException
     * @throws DeletionOfArtifactsIsNotAllowedException
     */
    private function getNumberOfArtifactsAllowedToDelete(PFUser $user)
    {
        $limit = $this->config->getArtifactsDeletionLimit();
        if (!$limit) {
            throw new DeletionOfArtifactsIsNotAllowedException();
        }

        $nb_artifacts_deleted = $this->user_deletion_retriever->getNumberOfArtifactsDeletionsForUserInTimePeriod($user);

        if ($nb_artifacts_deleted >= (int)$limit) {
            throw new ArtifactsDeletionLimitReachedException();
        }

        return $limit - ($nb_artifacts_deleted + 1);
    }
}
