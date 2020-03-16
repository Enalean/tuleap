<?php
/**
 * Copyright (c) Enalean, 2018-Present. All rights reserved
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

/**
 *  Data Access Object for Project history
 */
class ProjectHistoryDao extends DataAccessObject
{

    /**
     * Constructor of the class
     *
     * @param \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface $da
     *
     * @return void
     */
    public function __construct($da = null)
    {
        parent::__construct($da);
        $this->table_name = 'group_history';
    }

    /**
     * Returns an array containing project history elements and their count
     *
     * @param int $offset OFFSET keyword for the LIMIT clause
     * @param int $limit Number of results to be returned
     * @param int $groupId Project ID
     * @param String  $historyFilter Filtering statement
     *
     * @return Array
     */
    public function groupGetHistory($offset, $limit, $groupId = false, $historyFilter = null)
    {
        $sql = 'select SQL_CALC_FOUND_ROWS group_history.field_name,
              group_history.old_value,
              group_history.date,
              user.user_name 
              FROM ' . $this->table_name . ',user
              WHERE group_history.mod_by=user.user_id ';
        if ($historyFilter) {
            $sql .= $historyFilter;
        }
        $sql .= ' AND group_id=' . $this->da->escapeInt($groupId) . ' ORDER BY group_history.date DESC';
        if ($offset > 0 || $limit > 0) {
            $sql .= ' LIMIT ' . $this->da->escapeInt($offset) . ', ' . $this->da->escapeInt($limit);
        }
        return array('history' => $this->retrieve($sql), 'numrows' => $this->foundRows());
    }

    /**
     * handle the insertion of history for corresponding  parameters
     * $args is an array containing a list of parameters to use when
     * the message is to be displayed by the history.php script
     * The array is stored as a string at the end of the field_name
     * with the following format:
     * field_name %% [arg1, arg2...]
     *
     * @param String  $fieldName Event category
     * @param String  $oldValue  Event value
     * @param int $groupId Project ID
     * @param Array   $args      list of parameters used for message display
     *
     * @return DataAccessResult
     */
    public function groupAddHistory($fieldName, $oldValue, $groupId, $args = false)
    {
        if ($args) {
            $fieldName .= " %% " . implode("||", $args);
        }
        $userId = UserManager::instance()->getCurrentUser()->getId();
        if ($userId == 0) {
            $userId = 100;
        }
        $sql = 'insert into ' . $this->table_name . '(group_id,field_name,old_value,mod_by,date)
               VALUES (' . $this->da->escapeInt($groupId) . ' , ' . $this->da->quoteSmart($fieldName) . ', ' .
               $this->da->quoteSmart($oldValue) . ' , ' . $this->da->escapeInt($userId) . ' , ' . $this->da->escapeInt($_SERVER['REQUEST_TIME']) . ')';

        $this->retrieve($sql);
    }
}
