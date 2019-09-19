<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 *
 */

class Project_Service_ServiceUsageDao extends DataAccessObject
{

    /**
     * @param int $group_id
     *
     * @return DataAccessResult
     */
    public function getAllServicesUsage($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);

        $sql = "SELECT service_id, short_name, is_used
                FROM service
                WHERE group_id = $group_id
                ORDER BY rank";

        return $this->retrieve($sql);
    }

    /**
     * @param int $group_id
     * @param int $service_id
     *
     * @return bool
     */
    public function activateService($group_id, $service_id)
    {
        $group_id   = $this->da->escapeInt($group_id);
        $service_id = $this->da->escapeInt($service_id);

        $sql = "UPDATE service
                SET is_used = 1
                WHERE group_id = $group_id
                  AND service_id = $service_id";

        return $this->update($sql);
    }

    /**
     * @param int $group_id
     * @param int $service_id
     *
     * @return bool
     */
    public function deactivateService($group_id, $service_id)
    {
        $group_id   = $this->da->escapeInt($group_id);
        $service_id = $this->da->escapeInt($service_id);

        $sql = "UPDATE service
                SET is_used = 0
                WHERE group_id = $group_id
                  AND service_id = $service_id";

        return $this->update($sql);
    }

    /**
     * @param int $group_id
     * @param int $service_id
     *
     * @return DataAccessResult
     */
    public function getServiceUsage($group_id, $service_id)
    {
        $group_id   = $this->da->escapeInt($group_id);
        $service_id = $this->da->escapeInt($service_id);

        $sql = "SELECT service_id, short_name, is_used
                FROM service
                WHERE group_id = $group_id
                  AND service_id = $service_id";

        return $this->retrieve($sql);
    }
}
