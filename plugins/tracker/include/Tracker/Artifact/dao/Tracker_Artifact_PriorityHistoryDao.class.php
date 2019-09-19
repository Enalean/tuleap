<?php
/**
 * Copyright (c) Enalean SAS 2015. All rights reserved
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

/**
 * Manage artifacts priority history in database
 */
class Tracker_Artifact_PriorityHistoryDao extends DataAccessObject
{

    public function logPriorityChange($moved_artifact_id, $artifact_higher_id, $artifact_lower_id, $context_id, $project_id, $priority_updated_by, $priority_updated_on, $old_global_rank)
    {
        $moved_artifact_id   = $this->da->escapeInt($moved_artifact_id);
        $artifact_higher_id  = $this->da->escapeInt($artifact_higher_id);
        $artifact_lower_id   = $this->da->escapeInt($artifact_lower_id);
        $context_id          = $this->da->escapeInt($context_id);
        $project_id          = $this->da->escapeInt($project_id);
        $priority_updated_by = $this->da->escapeInt($priority_updated_by);
        $priority_updated_on = $this->da->escapeInt($priority_updated_on);
        $old_global_rank     = $this->da->escapeInt($old_global_rank);

        $sql = "INSERT INTO tracker_artifact_priority_history (moved_artifact_id, artifact_id_higher, artifact_id_lower, context, project_id, prioritized_by, prioritized_on, has_been_raised)
                SELECT $moved_artifact_id, $artifact_higher_id, $artifact_lower_id, $context_id, $project_id, $priority_updated_by, $priority_updated_on, ($old_global_rank > rank)
                FROM tracker_artifact_priority_rank
                WHERE artifact_id = $moved_artifact_id";

        $this->update($sql);
    }

    public function getArtifactPriorityHistory($artifact_id)
    {
        $artifact_id  = $this->da->escapeInt($artifact_id);

        $sql = "SELECT *
                FROM tracker_artifact_priority_history
                WHERE moved_artifact_id  = $artifact_id";

        return $this->retrieve($sql);
    }

    public function deletePriorityChangesHistory($artifact_id)
    {
        $artifact_id  = $this->da->escapeInt($artifact_id);

        $sql = "DELETE
                FROM tracker_artifact_priority_history
                WHERE moved_artifact_id  = $artifact_id
                  OR artifact_id_higher = $artifact_id
                  OR artifact_id_lower = $artifact_id";

        return $this->update($sql);
    }
}
