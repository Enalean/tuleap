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

    protected $tableName = 'disk_quota_exception';
    const REPOSITORY_ID        = 'request_id'; //PK
    const GROUP_ID             = 'group_id';
    const REQUESTER_ID         = 'requester_id';
    const REQUEST_SIZE         = 'requested_size';
    const EXCEPTION_MOTIVATION = 'exception_motivation';
    const REQUEST_STATUS       = 'request_status';
    const REQUEST_DATE         = 'request_date';

    /**
     * Constructor of the class
     *
     * @param DataAccess $da      Data access details
     *
     * @return void
     */
    function __construct(DataAccess $da) {
        parent::__construct($da);
    }

    public function getTable() {
        return $this->tableName;
    }

}

?>