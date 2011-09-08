<?php
/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for Project history
 */

class ProjectHistoryDao extends DataAccessObject {

    public function __construct($da) {
        parent::__construct($da);
        $this->table_name = 'group_history';
    }

    public function groupGetHistory ($offset, $limit, $group_id=false, $history_filter=null) {
        $sql='select SQL_CALC_FOUND_ROWS group_history.field_name,
              group_history.old_value,
              group_history.date,
              user.user_name 
              FROM '.$this->table_name.',user
              WHERE group_history.mod_by=user.user_id ';
        if ($history_filter) {
            $sql .= $history_filter;
        }
        $sql.=' AND group_id='.$this->da->escapeInt($group_id).' ORDER BY group_history.date DESC';
        if ($offset > 0 || $limit > 0) {
            $sql .= ' LIMIT '.$this->da->escapeInt($offset).', '.$this->da->escapeInt($limit);
        }
        return array('history' => $this->retrieve($sql), 'numrows' => $this->foundRows());
}

}
?>