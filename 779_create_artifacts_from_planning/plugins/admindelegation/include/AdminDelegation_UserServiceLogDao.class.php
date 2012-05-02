<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

require_once 'common/dao/include/DataAccessObject.class.php';

class AdminDelegation_UserServiceLogDao extends DataAccessObject {

    public function __construct(DataAccess $da) {
        parent::__construct($da);
    }

    public function searchLogs() {
        $sql = 'SELECT * FROM plugin_admindelegation_service_user_log';
        return $this->retrieve($sql);
    }
    
    public function addLog($action, $serviceId, $userId, $time) {
        $sql = 'INSERT INTO plugin_admindelegation_service_user_log (service_id, user_id, date, action)'.
               ' VALUES ('.$this->da->escapeInt($serviceId).','.
                           $this->da->escapeInt($userId).','.
                           'FROM_UNIXTIME('.$this->da->escapeInt($time).'),'.
                           $this->da->quoteSmart($action).
                           ')';
        return $this->update($sql);
    }
    
}

?>