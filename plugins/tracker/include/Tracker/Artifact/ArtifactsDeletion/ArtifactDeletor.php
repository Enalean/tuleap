<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use ProjectHistoryDao;
use Tracker_Artifact;
use Tracker_ArtifactDao;

class ArtifactDeletor
{
    const PROJECT_HISTORY_ARTIFACT_DELETED = 'tracker_artifact_delete';

    /**
     * @var Tracker_ArtifactDao
     */
    private $dao;
    /**
     * @var ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var PendingArtifactRemovalDao
     */
    private $pending_artifact_removal_dao;
    /**
     * @var AsynchronousArtifactsDeletionActionsRunner
     */
    private $asynchronous_actions_runner;

    public function __construct(
        Tracker_ArtifactDao $dao,
        ProjectHistoryDao $project_history_dao,
        PendingArtifactRemovalDao $pending_artifact_removal_dao,
        AsynchronousArtifactsDeletionActionsRunner $asynchronous_actions_runner
    ) {
        $this->dao                          = $dao;
        $this->project_history_dao          = $project_history_dao;
        $this->pending_artifact_removal_dao = $pending_artifact_removal_dao;
        $this->asynchronous_actions_runner  = $asynchronous_actions_runner;
    }

    public function delete(Tracker_Artifact $artifact, PFUser $user)
    {
        $this->dao->startTransaction();
        $this->processDelete($artifact, $user);
        $this->dao->commit();
        $this->addProjectHistory($artifact);
    }

    public function deleteWithoutTransaction(Tracker_Artifact $artifact, PFUser $user)
    {
        $this->processDelete($artifact, $user);
        $this->addProjectHistory($artifact);
    }

    private function processDelete(Tracker_Artifact $artifact, PFUser $user)
    {
        $this->pending_artifact_removal_dao->addArtifactToPendingRemoval($artifact->getId());

        $this->asynchronous_actions_runner->executeArchiveAndArtifactDeletion($artifact, $user);

        $this->dao->delete($artifact->getId());
    }

    private function addProjectHistory(Tracker_Artifact $artifact)
    {
        $this->project_history_dao->groupAddHistory(
            self::PROJECT_HISTORY_ARTIFACT_DELETED,
            '#' . $artifact->getId() . ' tracker #' . $artifact->getTrackerId() . ' (' . $artifact->getTracker()->getName() . ')',
            $artifact->getTracker()->getGroupId()
        );
    }
}
