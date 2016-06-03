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
        $sql = sprintf("SELECT * FROM groups, service
                WHERE groups.group_id=service.group_id AND service.short_name=%s AND service.is_used='1' AND groups.status='A'",
                $this->da->quoteSmart($service_short_name));
        return $this->retrieve($sql);
    }

    public function searchByProjectIdAndShortNames($project_id, $allowed_shortnames) {
        $project_id         = $this->da->escapeInt($project_id);
        $allowed_shortnames = $this->da->quoteSmartImplode(',', $allowed_shortnames);

        $sql = "SELECT *
                FROM service
                WHERE group_id = $project_id
                    AND short_name IN ($allowed_shortnames)
                ORDER BY rank";

        return $this->retrieve($sql);
    }

    public function isServiceAvailableAtSiteLevelByShortName($name) {
        $name = $this->da->quoteSmart($name);
        $sql  = "SELECT NULL
                 FROM service
                 WHERE group_id = 100
                     AND is_active = 1
                     AND short_name = $name
                 LIMIT 1";
        $dar = $this->retrieve($sql);
        return $dar->rowCount() === 1;
    }

    public function updateServiceUsage($project_id, $short_name, $is_used) {
        $project_id = $this->da->escapeInt($project_id);
        $short_name = $this->da->quoteSmart($short_name);
        $is_used    = $this->da->escapeInt($is_used);

        $sql = "UPDATE service
                SET is_used = $is_used
                WHERE short_name = $short_name
                AND group_id = $project_id";
        return $this->update($sql);
    }
}
