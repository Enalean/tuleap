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
    const REQUEST_ID           = 'request_id'; //PK
    const GROUP_ID             = 'group_id';
    const REQUESTER_ID         = 'requester_id';
    const REQUEST_SIZE         = 'requested_size';
    const EXCEPTION_MOTIVATION = 'exception_motivation';
    const REQUEST_STATUS       = 'request_status';
    const REQUEST_DATE         = 'request_date';

    const REQUEST_STATUS_NEW      = 10;
    const REQUEST_STATUS_ANALYZED = 20;
    const REQUEST_STATUS_APPROVED = 30;
    const REQUEST_STATUS_REJECTED = 40;

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
        $requestStatus       = self::REQUEST_STATUS_NEW;
        $requestDate         = time();
        $query               = "INSERT INTO ".$this->getTable()." (".self::GROUP_ID.",
                                            ".self::REQUESTER_ID.",
                                            ".self::REQUEST_SIZE.",
                                            ".self::EXCEPTION_MOTIVATION.",
                                            ".self::REQUEST_STATUS.",
                                            ".self::REQUEST_DATE."
                                            ) values (
                                            $groupId,
                                            $requesterId,
                                            $requestedSize,
                                            $exceptionMotivation,
                                            $requestStatus,
                                            $requestDate
                                            )";
        return $this->update($query);
    }

    /**
     * List all projects having custom quota
     *
     * @return DataAccessResult
     */
    public function getProjectsCustomQuota() {
        $sql = "SELECT q.".self::GROUP_ID.", g.group_name AS project, q.".self::REQUEST_SIZE."
                FROM ".$this->getTable()." q
                JOIN groups g ON (g.group_id = q.".self::GROUP_ID.")";
        return $this->retrieve($sql);
    }

}

?>