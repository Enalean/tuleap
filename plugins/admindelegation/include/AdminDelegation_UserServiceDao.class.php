<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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
 */

class AdminDelegation_UserServiceDao extends DataAccessObject
{
    public function searchAllUsers()
    {
        $sql = 'SELECT * FROM plugin_admindelegation_service_user';
        return $this->retrieve($sql);
    }

    public function searchUser($userId)
    {
        $sql = 'SELECT service_id FROM plugin_admindelegation_service_user' .
               ' WHERE user_id = ' . $this->da->escapeInt($userId);
        return $this->retrieve($sql);
    }

    public function isUserGrantedForService($userId, $serviceId)
    {
        $sql = 'SELECT NULL FROM plugin_admindelegation_service_user' .
               ' WHERE user_id = ' . $this->da->escapeInt($userId) .
               ' AND service_id = ' . $this->da->escapeInt($serviceId);
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return all user granted for this service
     *
     * @param int $serviceId
     * @return DataAccessResult
     */
    public function searchAllUserService($serviceId)
    {
        $sql = 'SELECT user_id FROM plugin_admindelegation_service_user' .
               ' WHERE service_id = ' . $this->da->escapeInt($serviceId);
        return $this->retrieve($sql);
    }

    public function isUserGranted($userId)
    {
        $sql = 'SELECT NULL FROM plugin_admindelegation_service_user' .
               ' WHERE user_id = ' . $this->da->escapeInt($userId) .
               ' LIMIT 1';
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function addUserService($userId, $serviceId)
    {
        $sql = 'INSERT INTO plugin_admindelegation_service_user (service_id, user_id)' .
               ' VALUES (' . $this->da->escapeInt($serviceId) . ', ' . $this->da->escapeInt($userId) . ')';
        return $this->update($sql);
    }

    public function removeUser($userId)
    {
        $sql = 'DELETE FROM plugin_admindelegation_service_user' .
               ' WHERE user_id = ' . $this->da->escapeInt($userId);
        return $this->update($sql);
    }
}
