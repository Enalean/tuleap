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

require_once 'common/dao/include/DataAccessObject.class.php';

class AgileDashboardSearchDao extends DataAccessObject {
    
    public function searchMatchingArtifacts($valueIds) {
        $valueIds   = implode(',', $valueIds);
        $sql = "SELECT ta.id, CVT.value AS title
                FROM (
                    SELECT *
                    FROM tracker_changeset_value_list
                    WHERE bindvalue_id IN ($valueIds)
                ) AS tcvl
                INNER JOIN tracker_changeset_value AS tcv ON tcvl.changeset_value_id = tcv.id
                INNER JOIN tracker_changeset       AS tc  ON tcv.changeset_id        = tc.id
                INNER JOIN tracker_artifact        AS ta  ON tc.artifact_id          = ta.id

                LEFT JOIN (
                    tracker_changeset_value AS CV2
                    INNER JOIN tracker_semantic_title       AS ST  ON (CV2.field_id = ST.field_id)
                    INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id       = CVT.changeset_value_id)
                ) ON (tc.id = CV2.changeset_id)
        
                WHERE ta.use_artifact_permissions = 0";
        echo "<pre>$sql</pre>";
        return $this->retrieve($sql);
    }
    
    function searchSharedValueIds($sourceOrTargetValueIds) {
        $sourceOrTargetValueIds = implode(',', $sourceOrTargetValueIds);
        
        $sql_original_ids = "SELECT original.id
                FROM tracker_field_list_bind_static_value AS v
                    INNER JOIN tracker_field_list_bind_static_value AS original ON (v.original_value_id = original.id)
                WHERE v.id IN ($sourceOrTargetValueIds)";
        
        $sql_target_ids = "SELECT target.id
                FROM tracker_field_list_bind_static_value AS v
                     INNER JOIN tracker_field_list_bind_static_value AS original ON (v.original_value_id = original.id OR (v.id = original.id))
                     INNER JOIN tracker_field_list_bind_static_value AS target ON (original.id = target.original_value_id)
                WHERE v.id IN ($sourceOrTargetValueIds)";
        
        $sql = $sql_original_ids.' UNION '.$sql_target_ids;
        
        echo "<pre>$sql</pre>";
        return $this->retrieve($sql);
    }

}
?>
