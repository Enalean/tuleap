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

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for Service 
 */
class ServiceDao extends DataAccessObject {
    /**
    * Searches Service by Server Id 
    * @return DataAccessResult
    */
    function searchByServerId($server_id) {
        $sql = sprintf("SELECT * FROM service WHERE server_id = %s ORDER BY group_id, rank",
                $this->da->quoteSmart($server_id));
        return $this->retrieve($sql);
    }

    /**
    * Return active projects that use a specific service
    * WARNING: this returns all fields of all projects (might be big)
    * @return DataAccessResult
    */
    function searchActiveUnixGroupByUsedService($service_short_name) {
        $sql = sprintf("SELECT * FROM groups, service WHERE groups.group_id=service.group_id AND service.short_name=%s AND service.is_used='1' AND groups.status='A'",
                $this->da->quoteSmart($service_short_name));
        return $this->retrieve($sql);
    }
}


?>