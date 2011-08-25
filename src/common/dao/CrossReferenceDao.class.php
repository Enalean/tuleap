<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
 
require_once('include/DataAccessObject.class.php');

class CrossReferenceDao extends DataAccessObject {
    
    public function __construct($da = null) {
        parent::__construct($da);
        $this->table_name = 'cross_references';
    }

    public function updateTargetKeyword($old_keyword, $keyword, $group_id) {
        $sql = sprintf("UPDATE $this->table_name SET target_keyword=%s WHERE target_keyword= %s and target_gid=%s",
                       $this->da->quoteSmart($keyword),
                       $this->da->quoteSmart($old_keyword),
                       $this->da->quoteSmart($group_id));        
        return $this->update($sql);
    }
    
    public function updateSourceKeyword($old_keyword, $keyword, $group_id) {
        $sql = sprintf("UPDATE $this->table_name SET source_keyword=%s WHERE source_keyword= %s and source_gid=%s",
                       $this->da->quoteSmart($keyword),
                       $this->da->quoteSmart($old_keyword),
                       $this->da->quoteSmart($group_id));       
        return $this->update($sql);
    }
}
?>
