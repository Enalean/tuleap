<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\BacklogItemDao;
use Tuleap\Tracker\Artifact\Artifact;

class AgileDashboard_Milestone_MilestoneStatusCounter
{
    private $backlog_item_dao;
    private $artifact_factory;

    public function __construct(
        BacklogItemDao $backlog_item_dao,
        private readonly \Tuleap\Tracker\Artifact\Dao\ArtifactDao $artifact_dao,
        Tracker_ArtifactFactory $artifact_factory,
    ) {
        $this->backlog_item_dao = $backlog_item_dao;
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
    public function getStatus(PFUser $user, ?int $milestone_artifact_id)
    {
        $status = [
            Artifact::STATUS_OPEN   => 0,
            Artifact::STATUS_CLOSED => 0,
        ];
        if ($milestone_artifact_id !== null) {
            $this->getStatusForMilestoneArtifactId($user, $milestone_artifact_id, $status);
        }
        return $status;
    }

    private function getStatusForMilestoneArtifactId(PFUser $user, int $milestone_artifact_id, array &$status): void
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

    private function countStatus(array $artifact_id_list, array &$status): void
    {
        if (count($artifact_id_list)) {
            $artifact_status = $this->artifact_dao->getArtifactsStatusByIds($artifact_id_list);
            foreach ($artifact_status as $row) {
                $status[$row['status']]++;
            }
        }
    }

    private function getBacklogArtifactsUserCanView(PFUser $user, int $milestone_artifact_id): array
    {
        return $this->getIdsUserCanView(
            $user,
            $this->backlog_item_dao->getBacklogArtifacts($milestone_artifact_id)
        );
    }

    private function getChildrenUserCanView(PFUser $user, array $artifact_ids): array
    {
        return $this->getIdsUserCanView(
            $user,
            $this->artifact_dao->getChildrenForArtifacts($artifact_ids)
        );
    }

    /**
     * @param array<array{id: int}> $artifacts_rows
     * @return int[]
     */
    private function getIdsUserCanView(PFUser $user, array $artifacts_rows): array
    {
        $artifact_ids = [];
        foreach ($artifacts_rows as $row) {
            $artifact = $this->artifact_factory->getArtifactById($row['id']);
            if ($artifact && $artifact->userCanView($user)) {
                $artifact_ids[] = $row['id'];
            }
        }
        return $artifact_ids;
    }
}
