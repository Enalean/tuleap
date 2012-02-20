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
    
    public function searchMatchingArtifacts($valueIdsList) {
        $sql = "
            SELECT artifact.id, CVT.value AS title
            FROM tracker_artifact AS artifact
            INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)
            " . $this->getSharedFieldsSqlFragment($valueIdsList) . "
            LEFT JOIN (
                tracker_changeset_value AS CV
                    INNER JOIN tracker_semantic_title       AS ST  ON (CV.field_id = ST.field_id)
                    INNER JOIN tracker_changeset_value_text AS CVT ON (CV.id       = CVT.changeset_value_id)
            ) ON (c.id = CV.changeset_id)
            WHERE artifact.use_artifact_permissions = 0
        ";
        return $this->retrieve($sql);
    }
    
    protected function getSharedFieldsSqlFragment($valueIdsList) {
        $fragmentNumber = 0;
        $sqlFragments   = array();
        foreach ($valueIdsList as $valueIds) {
            $sqlFragments[] = $this->getSharedFieldFragment($fragmentNumber++, $valueIds);
        }
        return implode(' ', $sqlFragments);
    }
    
    protected function getSharedFieldFragment($fragmentNumber, $valueIds) {
        $valueIds   = implode(',', $valueIds);
        
        // Table aliases
        $changeset_value      = "CV_$fragmentNumber";
        $changeset_value_list = "CVL_$fragmentNumber";
        
        $sqlFragment = "
            INNER JOIN tracker_changeset_value AS $changeset_value ON (
                $changeset_value.changeset_id = c.id 
            )
            INNER JOIN tracker_changeset_value_list AS $changeset_value_list ON (
                    $changeset_value_list.changeset_value_id = $changeset_value.id
                AND $changeset_value_list.bindvalue_id       IN ($valueIds)
            )
        ";
        
        return $sqlFragment;
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
        
        return $this->retrieve($sql);
    }

}
?>
