<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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
 * DAO class for Project Quota
 */

class Statistics_ProjectQuotaDao extends DataAccessObject {

    protected $tableName       = 'plugin_statistics_disk_quota_exception';
    const GROUP_ID             = 'group_id'; //PK
    const REQUESTER_ID         = 'requester_id';
    const REQUEST_SIZE         = 'requested_size';
    const EXCEPTION_MOTIVATION = 'exception_motivation';
    const REQUEST_DATE         = 'request_date';

    /**
     * Get the dao table name
     *
     * @return String
     */
    public function getTable() {
        return $this->tableName;
    }

    /**
     * This function add a disk quota exception in the database
     *
     * @param Integer $groupId             Id of the project we want to add excpetion for its disk quota
     * @param Integer $requesterId         Id of the user that performed the request
     * @param Integer $requestedSize       New disk size we want to apply as quota
     * @param String  $exceptionMotivation A text that should justify a given exception request
     *
     * @return Boolean
     */
    public function addException($groupId, $requesterId, $requestedSize, $exceptionMotivation) {
        $groupId             = $this->da->escapeInt($groupId);
        $requesterId         = $this->da->escapeInt($requesterId);
        $requestedSize       = $this->da->escapeInt($requestedSize);
        $exceptionMotivation = $this->da->quoteSmart($exceptionMotivation);
        $requestDate         = $_SERVER['REQUEST_TIME'];
        $query               = "REPLACE INTO ".$this->getTable()." (".self::GROUP_ID.",
                                             ".self::REQUESTER_ID.",
                                             ".self::REQUEST_SIZE.",
                                             ".self::EXCEPTION_MOTIVATION.",
                                             ".self::REQUEST_DATE."
                                             ) values (
                                             $groupId,
                                             $requesterId,
                                             $requestedSize,
                                             $exceptionMotivation,
                                             $requestDate
                                             )";
        return $this->update($query);
    }

    /**
     * List all projects having custom quota
     *
     * @param Array $list List of projects Id corresponding to a filter
     * @param int $offset From where the result will be displayed.
     * @param int $count  How many results are returned.
     *
     * @return DataAccessResult
     */
    public function getAllCustomQuota($list = array(), $offset = null, $count = null, $sort = null, $sortSens = null) {
        $condition = '';
        $order     = '';
        if (!empty($list)) {
            $condition = "WHERE ".self::GROUP_ID." IN (".join(', ', $list).")";
        }
        if (isset($offset) && isset($count)) {
            $limit = " LIMIT ".$this->da->escapeInt($offset).", ".$this->da->escapeInt($count);
        } else {
            $limit = '';
        }
        if (isset($sort)) {
            switch ($sort) {
                case 'quota':
                    if (!empty($sortSens)) {
                        $order = "ORDER BY ".self::REQUEST_SIZE." ".$sortSens;
                    } else {
                         $order = "ORDER BY ".self::REQUEST_SIZE;
                    }
                break;
                case 'date':
                    if (!empty($sortSens)) {
                        $order = "ORDER BY ".self::REQUEST_DATE." ".$sortSens;
                    } else {
                         $order = "ORDER BY ".self::REQUEST_DATE;
                    }
                break;
                default:
                    $order = "ORDER BY ".self::REQUEST_SIZE;
            }
        }
        $sql = "SELECT *
                FROM ".$this->getTable()."
                ".$condition.$order.$limit;
        return $this->retrieve($sql);
    }

    /**
     * Get custom quota for a given project
     *
     * @param Integer $groupId Id of the project
     *
     * @return DataAccessResult
     */
    public function getProjectCustomQuota($groupId) {
        $groupId = $this->da->escapeInt($groupId);
        $sql = "SELECT *
                FROM ".$this->getTable()."
                WHERE ".self::GROUP_ID." = ".$groupId;
        return $this->retrieve($sql);
    }

    /**
     * Delete Custom quota for the given projects
     *
     * @param Array $projects List of Id of projects
     *
     * @return Boolean
     */
    public function deleteCustomQuota($projects) {
        $sql = "DELETE FROM ".$this->getTable()."
                WHERE ".self::GROUP_ID." IN (".join(', ', $projects).")";
        return $this->update($sql);
    }

}
?>