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

class Tracker_CrossSearch_SharedFieldDao extends DataAccessObject {
    
    /**
     * @todo: this query doesn't work when $source_or_target_field_ids is the original field.
     * 
     * Given the SharedField criteria in UI is built with the reference field
     * Then the reference value is not retreived by the following query.
     * 
     * Field 1084 (ref)
     * Field 1107 (original_id = 1084)
     * Field 1120 (original_id = 1084)
     * 
     * $source_or_target_field_ids = 1084 -> [ 1107, 1120 ]
     * $source_or_target_field_ids = 1107 -> [ 1084, 1107, 1120 ]
     * $source_or_target_field_ids = 1120 -> [ 1084, 1107, 1120 ]
     * 
     * NB: same apply for values method
     */
    public function searchSharedFieldIds($source_or_target_field_ids) {
        $source_or_target_field_ids = $this->da->escapeInt($source_or_target_field_ids);
        
        $sql_original_ids = "
            SELECT original.id
            FROM tracker_field AS f
                    INNER JOIN tracker_field AS original ON (f.original_field_id = original.id)
            WHERE f.id = $source_or_target_field_ids
        ";
        
        $sql_target_ids = "
            SELECT target.id
            FROM tracker_field AS f
                INNER JOIN tracker_field AS original ON (
                    f.original_field_id = original.id
                    OR f.id             = original.id
                )
                INNER JOIN tracker_field AS target ON (
                    original.id = target.original_field_id
                )
            WHERE f.id = $source_or_target_field_ids
        ";
        
        $sql = $sql_original_ids.' UNION '.$sql_target_ids;
        //echo '<pre>'.print_r($sql, true).'</pre>';
        return $this->retrieve($sql);
    }

    public function searchSharedValueIds(array $source_or_target_value_ids) {
        $source_or_target_value_ids = array_filter($source_or_target_value_ids);
        
        if (count($source_or_target_value_ids) == 0) { return array(); }
        
        $source_or_target_value_ids = implode(',', $source_or_target_value_ids);
        
        $sql_original_ids = "SELECT original.id
                FROM tracker_field_list_bind_static_value AS v
                    INNER JOIN tracker_field_list_bind_static_value AS original ON (v.original_value_id = original.id)
                WHERE v.id IN ($source_or_target_value_ids)";
        
        $sql_target_ids = "SELECT target.id
                FROM tracker_field_list_bind_static_value AS v
                     INNER JOIN tracker_field_list_bind_static_value AS original ON (v.original_value_id = original.id OR (v.id = original.id))
                     INNER JOIN tracker_field_list_bind_static_value AS target   ON (original.id         = target.original_value_id)
                WHERE v.id IN ($source_or_target_value_ids)";
        
        $sql = $sql_original_ids.' UNION '.$sql_target_ids;
        
        return $this->retrieve($sql);
    }
}

?>
