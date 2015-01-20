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
class Tracker_Artifact_PriorityHistoryDao extends DataAccessObject {

    public function logPriorityChange($artifact_higher_id, $artifact_lower_id, $priority_updated_by, $priority_updated_on) {
        $artifact_higher_id  = $this->da->escapeInt($artifact_higher_id);
        $artifact_lower_id   = $this->da->escapeInt($artifact_lower_id);
        $priority_updated_by = $this->da->escapeInt($priority_updated_by);
        $priority_updated_on = $this->da->escapeInt($priority_updated_on);

        $sql = "INSERT INTO tracker_artifact_priority_history (artifact_id_higher, artifact_id_lower, prioritized_by, prioritized_on)
                VALUES ($artifact_higher_id, $artifact_lower_id, $priority_updated_by, $priority_updated_on)";

        $this->update($sql);
    }

}