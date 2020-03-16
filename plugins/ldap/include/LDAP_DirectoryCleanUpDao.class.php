<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2014. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


class LDAP_DirectoryCleanUpDao extends DataAccessObject
{
    /**
     * Create forecast deletion date for suspended user
     *
     * @param Integer userId
     *
     * @param Integer deletionDate date on which suspended user will be deleted
     *
     * @return bool
     */
    public function createForecastDeletionDate($userId, $deletionDate)
    {
        $sql = 'INSERT INTO plugin_ldap_suspended_user (user_id, deletion_date)' .
               ' VALUES (' . $this->da->escapeInt($userId) . ',' . $this->da->escapeInt($deletionDate) . ')';
        return $this->update($sql);
    }

    /**
     * Update an ldap suspended user
     *
     * @param Integer userId
     *
     * @param Integer deletionDate
     *
     * @return bool
     */
    public function resetForecastDeletionDate($userId)
    {
        $sql = 'UPDATE plugin_ldap_suspended_user' .
               ' SET deletion_date = 0' .
               ' WHERE user_id=' . $this->da->escapeInt($userId);
        return $this->update($sql);
    }

    /**
     * Return all ldap suspended users who need to be deleted
     *
     * @param Integer deletionDate
     *
     * @return DataAccessResult
     */
    public function getAllSuspendedUsers($deletionDate)
    {
        $sql = 'SELECT user_id' .
               ' FROM plugin_ldap_suspended_user' .
               ' WHERE deletion_date <= ' . $this->da->escapeInt($deletionDate) .
               ' AND deletion_date <> 0';
        return $this->retrieve($sql);
    }

    /**
     * Return all ldap suspended users to be deleted tomorrow
     *
     * @return DataAccessResult
     */
    public function getUsersDeletedTomorrow()
    {
        $today      = strtotime('tomorrow midnight');
        $tomorrow   = strtotime('+1 day', $today);
        $sql        = 'SELECT user_id' .
                      ' FROM plugin_ldap_suspended_user' .
                      ' WHERE deletion_date BETWEEN ' . $this->da->escapeInt($today) .
                      ' AND ' . $this->da->escapeInt($tomorrow);
        $dataResult = $this->retrieve($sql);
        if ($dataResult->isError()) {
            return false;
        } else {
            return $dataResult;
        }
    }
}
