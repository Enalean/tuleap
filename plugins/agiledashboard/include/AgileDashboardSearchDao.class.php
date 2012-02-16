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
    
    public function searchMatchingArtifacts($trackerIds, $fieldIds, $valueIds) {
        $trackerIds = implode(',', $trackerIds);
        $fieldIds   = implode(',', $fieldIds);
        $valueIds   = implode(',', $valueIds);
        echo $sql = "SELECT artifact.id, CVT.value AS title
                FROM tracker_artifact AS artifact
                     INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id) 
                     
                     INNER JOIN tracker_changeset_value AS value ON (
                            value.changeset_id = c.id 
                        AND value.field_id IN ($fieldIds)
                     )
                     INNER JOIN tracker_changeset_value_list AS value_list ON (
                                value_list.changeset_value_id = value.id 
                                AND value_list.bindvalue_id IN ($valueIds)
                     )
                     
                     LEFT JOIN (                         -- For the /title/ if any
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
                    ) ON (c.id = CV2.changeset_id)
                
                WHERE artifact.tracker_id IN ($trackerIds)  AND (artifact.use_artifact_permissions = 0)";
        return $this->retrieve($sql);
    }
    
    function searchSharedValueIds($sourceOrTargetValueIds) {
        $sourceOrTargetValueIds = implode(',', $sourceOrTargetValueIds);
        $sql = "SELECT target.id
                FROM tracker_field_list_bind_static_value AS v
                     INNER JOIN tracker_field_list_bind_static_value AS original ON (v.original_value_id = original.id OR (v.id = original.id))
                     INNER JOIN tracker_field_list_bind_static_value AS target ON (original.id = target.original_value_id)
                WHERE v.id IN ($sourceOrTargetValueIds)";
        return $this->retrieve($sql);
    }

}
?>
