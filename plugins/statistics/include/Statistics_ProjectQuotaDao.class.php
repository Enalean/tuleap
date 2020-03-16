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

class Statistics_ProjectQuotaDao extends DataAccessObject
{

    protected $tableName       = 'plugin_statistics_disk_quota_exception';
    public const GROUP_ID             = 'group_id'; //PK
    public const REQUESTER_ID         = 'requester_id';
    public const REQUEST_SIZE         = 'requested_size';
    public const EXCEPTION_MOTIVATION = 'exception_motivation';
    public const REQUEST_DATE         = 'request_date';

    /**
     * Get the dao table name
     *
     * @return String
     */
    public function getTable()
    {
        return $this->tableName;
    }

    /**
     * This function add a disk quota exception in the database
     *
     * @param int $groupId Id of the project we want to add excpetion for its disk quota
     * @param int $requesterId Id of the user that performed the request
     * @param int $requestedSize New disk size we want to apply as quota
     * @param String  $exceptionMotivation A text that should justify a given exception request
     *
     * @return bool
     */
    public function addException($groupId, $requesterId, $requestedSize, $exceptionMotivation)
    {
        $groupId             = $this->da->escapeInt($groupId);
        $requesterId         = $this->da->escapeInt($requesterId);
        $requestedSize       = $this->da->escapeInt($requestedSize);
        $exceptionMotivation = $this->da->quoteSmart($exceptionMotivation);
        $requestDate         = $_SERVER['REQUEST_TIME'];
        $query               = "REPLACE INTO plugin_statistics_disk_quota_exception 
                                (group_id, requester_id, requested_size, exception_motivation, request_date)
                                VALUES
                                ($groupId, $requesterId, $requestedSize, $exceptionMotivation, $requestDate)";
        return $this->update($query);
    }

    /**
     * List all projects having custom quota
     *
     * @param Array  $list      List of projects Id corresponding to a filter
     * @param int    $offset    From where the result will be displayed.
     * @param int    $count     How many results are returned.
     * @param String $sort      Order result set according to this parameter
     * @param String $sortOrder Specifiy if the result set sort order is ascending or descending
     *
     * @return DataAccessResult
     */
    public function getAllCustomQuota($list, $offset, $count, $sort, $sortOrder)
    {
        $condition = '';
        $order     = '';
        $limit     = '';
        $list      = $this->da->escapeIntImplode($list);

        if (!empty($list)) {
            $condition = "WHERE " . self::GROUP_ID . " IN ($list)";
        }

        if (isset($offset) && isset($count)) {
            $limit = " LIMIT " . $this->da->escapeInt($offset) . ", " . $this->da->escapeInt($count);
        }

        if (isset($sort)) {
            $sortOrder = $sortOrder == 'DESC' ? 'DESC' : 'ASC';
            $order = 'ORDER BY ';
            switch ($sort) {
                case 'quota':
                    $order .= self::REQUEST_SIZE . ' ' . $sortOrder;
                    break;
                case 'date':
                    $order .= self::REQUEST_DATE . ' ' . $sortOrder;
                    break;
                default:
                    $order .= self::REQUEST_SIZE;
            }
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_statistics_disk_quota_exception
                $condition
                $order
                $limit";
        return $this->retrieve($sql);
    }

    /**
     * Get custom quota for a given project
     *
     * @param int $groupId Id of the project
     *
     * @return DataAccessResult
     */
    public function getProjectCustomQuota($groupId)
    {
        $groupId = $this->da->escapeInt($groupId);
        $sql = "SELECT *
                FROM " . $this->getTable() . "
                WHERE " . self::GROUP_ID . " = " . $groupId;
        return $this->retrieve($sql);
    }

    public function deleteCustomQuota($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $sql = "DELETE FROM " . $this->getTable() . "
                WHERE " . self::GROUP_ID . " = $project_id";
        return $this->update($sql);
    }
}
