<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Tracker_Chart_Burndown_Data_LinkedArtifactsDao extends DataAccessObject {
    
    public function searchRemainingEffort($effort_field_id, $effort_field_type, $artifact_ids) {
        $sql = "SELECT c.artifact_id AS id, TO_DAYS(FROM_UNIXTIME(submitted_on)) - TO_DAYS(FROM_UNIXTIME(0)) as day, value
                    FROM tracker_changeset AS c 
                         INNER JOIN tracker_changeset_value AS cv ON(cv.changeset_id = c.id AND cv.field_id = " . $effort_field_id . ")";
        if ($effort_field_type == 'int') {
            $sql .= " INNER JOIN tracker_changeset_value_int AS cvi ON(cvi.changeset_value_id = cv.id)";
        } else {
            $sql .= " INNER JOIN tracker_changeset_value_float AS cvi ON(cvi.changeset_value_id = cv.id)";
        }
        $sql .= " WHERE c.artifact_id IN (" . implode(',', $artifact_ids) . ")";
        return $this->retrieve($sql);
    }
}
?>
