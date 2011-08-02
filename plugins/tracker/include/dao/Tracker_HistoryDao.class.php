<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('common/dao/include/DataAccessObject.class.php');

class Tracker_HistoryDao extends DataAccessObject {
    
    function searchById($id) {
        $id      = $this->da->escapeInt($id);
        $sql = "SELECT c.id AS changeset_id, 
                       c.submitted_by AS submitted_by, 
                       c.submitted_on AS submitted_on, 
                       c.email AS email, 
                       0 AS is_followup_comment,
                       v.field_id AS field_id, 
                       v.id AS value_id, 
                       NULL AS body, 
                       v.has_changed AS has_changed
                FROM tracker_changeset AS c 
                     INNER JOIN tracker_changeset_value AS v 
                        ON(c.id = v.changeset_id)
                WHERE artifact_id = $id
                
                UNION
                
                SELECT c.id AS changeset_id, 
                       f.submitted_by AS submitted_by, 
                       f.submitted_on AS submitted_on, 
                       c.email AS email, 
                       1 AS is_followup_comment,
                       f.id AS field_id, 
                       NULL AS value_id, 
                       f.body AS body, 
                       f.parent_id AS has_changed
                FROM tracker_changeset AS c 
                     INNER JOIN tracker_changeset_comment AS f 
                        ON(c.id = f.changeset_id)
                WHERE artifact_id = $id
                
                ORDER BY submitted_on, changeset_id";
        return $this->retrieve($sql);
    }
}
?>
