<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class AgileDashboard_Milestone_MilestoneStatusCounter {

    private $backlog_item_dao;
    private $artifact_dao;

    public function __construct(AgileDashboard_BacklogItemDao $backlog_item_dao, Tracker_ArtifactDao $artifact_dao) {
        $this->backlog_item_dao = $backlog_item_dao;
        $this->artifact_dao     = $artifact_dao;
    }
    
    public function getStatus($milestone_artifact_id) {
        $status = array(
            Tracker_ArtifactDao::STATUS_OPEN   => 0,
            Tracker_ArtifactDao::STATUS_CLOSED => 0,
        );
        if ($milestone_artifact_id) {
            $this->getStatusForMilestoneArtifactId($milestone_artifact_id, $status);
        }
        return $status;
    }

    private function getStatusForMilestoneArtifactId($milestone_artifact_id, array &$status) {
        $first_level_result = $this->backlog_item_dao->getBacklogArtifacts($milestone_artifact_id);
        $artifact_id_list   = $this->countStatus($first_level_result, $status);
        if (count($artifact_id_list)) {
            $sub_level_result = $this->artifact_dao->getChildrenForArtifacts($artifact_id_list);
            $this->countStatus($sub_level_result, $status);
        }
    }

    private function countStatus($result, array &$status) {
        $artifact_id_list = array();
        if (count($result)) {
            foreach ($result as $row) {
                $artifact_id_list[] = $row['id'];
            }

            $artifact_status = $this->artifact_dao->getArtifactsStatusByIds($artifact_id_list);
            foreach ($artifact_status as $row) {
                $status[$row['status']]++;
            }
        }
        return $artifact_id_list;
        
    }
}
