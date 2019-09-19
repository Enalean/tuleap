<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

class AgileDashboard_Milestone_MilestoneStatusCounter
{

    private $backlog_item_dao;
    private $artifact_dao;
    private $artifact_factory;

    public function __construct(
        AgileDashboard_BacklogItemDao $backlog_item_dao,
        Tracker_ArtifactDao $artifact_dao,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->backlog_item_dao = $backlog_item_dao;
        $this->artifact_dao     = $artifact_dao;
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * Returns a status array. E.g.
     *  array(
     *      Tracker_Artifact::STATUS_OPEN   => no_of_opne,
     *      Tracker_Artifact::STATUS_CLOSED => no_of_closed,
     *  )
     *
     * @return array
     */
    public function getStatus(PFUser $user, $milestone_artifact_id)
    {
        $status = array(
            Tracker_Artifact::STATUS_OPEN   => 0,
            Tracker_Artifact::STATUS_CLOSED => 0,
        );
        if ($milestone_artifact_id) {
            $this->getStatusForMilestoneArtifactId($user, $milestone_artifact_id, $status);
        }
        return $status;
    }

    private function getStatusForMilestoneArtifactId(PFUser $user, $milestone_artifact_id, array &$status)
    {
        $artifact_id_list = $this->getBacklogArtifactsUserCanView($user, $milestone_artifact_id);
        $this->countStatus($artifact_id_list, $status);
        if (count($artifact_id_list)) {
            $this->countStatus(
                $this->getChildrenUserCanView($user, $artifact_id_list),
                $status
            );
        }
    }

    private function countStatus(array $artifact_id_list, array &$status)
    {
        if (count($artifact_id_list)) {
            $artifact_status = $this->artifact_dao->getArtifactsStatusByIds($artifact_id_list);
            foreach ($artifact_status as $row) {
                $status[$row['status']]++;
            }
        }
    }

    private function getBacklogArtifactsUserCanView(PFUser $user, $milestone_artifact_id)
    {
        return $this->getIdsUserCanView(
            $user,
            $this->backlog_item_dao->getBacklogArtifacts($milestone_artifact_id)
        );
    }

    private function getChildrenUserCanView(PFUser $user, array $artifact_ids)
    {
        return $this->getIdsUserCanView(
            $user,
            $this->artifact_dao->getChildrenForArtifacts($artifact_ids)
        );
    }

    private function getIdsUserCanView(PFUser $user, LegacyDataAccessResultInterface $dar)
    {
        $artifact_ids = array();
        foreach ($dar as $row) {
            $artifact = $this->artifact_factory->getArtifactById($row['id']);
            if ($artifact && $artifact->userCanView($user)) {
                $artifact_ids[] = $row['id'];
            }
        }
        return $artifact_ids;
    }
}
