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

class AgileDashboard_SharedFieldDao extends DataAccessObject {
    
    public function searchSharedFieldIds($sourceOrTargetFieldIds) {
        $sourceOrTargetFieldIds = $this->da->escapeInt($sourceOrTargetFieldIds);
        
        $sql_original_ids = "
            SELECT original.id
            FROM tracker_field AS f
                INNER JOIN tracker_field AS original ON (f.original_field_id = original.id)
            WHERE f.id = $sourceOrTargetFieldIds
        ";
        
        $sql_target_ids = "
            SELECT target.id
            FROM tracker_field AS f
                INNER JOIN tracker_field AS original ON (   f.original_field_id = original.id
                                                         OR f.id                = original.id)
                INNER JOIN tracker_field AS target   ON (   original.id         = target.original_field_id)
            WHERE f.id = $sourceOrTargetFieldIds
        ";
        
        $sql = $sql_original_ids.' UNION '.$sql_target_ids;
        
        return $this->retrieve($sql);
    }

    public function searchSharedValueIds(array $sourceOrTargetValueIds) {
        $sourceOrTargetValueIds = array_filter($sourceOrTargetValueIds);
        if (count($sourceOrTargetValueIds) == 0) {
            return array();
        }
        $sourceOrTargetValueIds = implode(',', $sourceOrTargetValueIds);
        
        $sql_original_ids = "SELECT original.id
                FROM tracker_field_list_bind_static_value AS v
                    INNER JOIN tracker_field_list_bind_static_value AS original ON (v.original_value_id = original.id)
                WHERE v.id IN ($sourceOrTargetValueIds)";
        
        $sql_target_ids = "SELECT target.id
                FROM tracker_field_list_bind_static_value AS v
                     INNER JOIN tracker_field_list_bind_static_value AS original ON (v.original_value_id = original.id OR (v.id = original.id))
                     INNER JOIN tracker_field_list_bind_static_value AS target   ON (original.id         = target.original_value_id)
                WHERE v.id IN ($sourceOrTargetValueIds)";
        
        $sql = $sql_original_ids.' UNION '.$sql_target_ids;
        
        return $this->retrieve($sql);
    }
}

?>
