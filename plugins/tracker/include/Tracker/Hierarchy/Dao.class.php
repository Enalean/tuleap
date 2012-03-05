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

class Tracker_Hierarchy_Dao extends DataAccessObject {
    
    public function updateChildren($parent_id, array $child_ids) {
        $parent_id = $this->da->escapeInt($parent_id);
        $sql = "DELETE FROM tracker_hierarchy WHERE parent_id = $parent_id";
        $this->update($sql);
        
        foreach($child_ids as $child_id) {
            $child_id = $this->da->escapeInt($child_id);
            $insert_values[] = "($parent_id, $child_id)";
        }
        $sql = "INSERT INTO tracker_hierarchy(parent_id, child_id) VALUES ".implode(',', $insert_values);
        $this->update($sql);
    }
}

?>
