<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

/**
 * Interact with plugin_ldap_user table in database
 *
 */
class LDAP_UserDao extends DataAccessObject
{
    /**
     *
     * @param int[] $user_ids
     *
     * @return LegacyDataAccessResultInterface
     * @throws DataAccessQueryException
     */
    public function searchLdapLoginFromUserIds(array $user_ids)
    {
        $user_ids = $this->da->escapeIntImplode($user_ids);

        $sql = "SELECT user_id, ldap_uid, user.status
                FROM plugin_ldap_user
                    INNER JOIN user USING (user_id)
                WHERE user_id IN ($user_ids)";

        return $this->retrieve($sql);
    }

    /**
     * Check if user has already logged in on Web platform
     *
     * @param int $userId Id of the user
     *
     * @return bool
     */
    public function alreadyLoggedInOnce($userId)
    {
        $sql = 'SELECT NULL' .
            ' FROM plugin_ldap_user ldap_u' .
            '   INNER JOIN user u USING (user_id)' .
            ' WHERE u.user_id = ' . $this->da->escapeInt($userId) .
            ' AND u.ldap_id != ""' .
            ' AND u.ldap_id IS NOT NULL' .
            ' AND login_confirmation_date = 0';

        $dar = $this->retrieve($sql);
        if ($dar && ! $dar->isError() && $dar->rowCount() == 1) {
            return false;
        } else {
            return true;
        }
    }

    public function hasLoginConfirmationDate(LDAP_User $user): bool
    {
        $sql = sprintf(
            'SELECT NULL FROM plugin_ldap_user WHERE user_id = %d AND login_confirmation_date != 0',
            $this->da->escapeInt($user->getId())
        );
        $dar = $this->retrieve($sql);
        if ($dar && ! $dar->isError()) {
            return count($dar) !== 0;
        }
        return false;
    }

    /**
     * Create new entry for LDAP user.
     *
     * @param int $userId Id of the user
     * @param int $date Date of creation (timestamp)
     *
     * @return bool
     */
    public function createLdapUser($userId, $date = 0, $ldap_uid = "")
    {
        $sql = 'INSERT INTO plugin_ldap_user' .
            '(user_id, login_confirmation_date, ldap_uid)' .
            ' VALUES ' .
            '(' . db_ei($userId) . ',' . db_ei($date) . ',"' . db_es($ldap_uid) . '")';
        return $this->update($sql);
    }

    /**
     * Record when user log on Codendi
     *
     * @param int $userId Id of the user
     * @param int $date Date of login (timestamp)
     *
     * @return bool
     */
    public function setLoginDate($userId, $date)
    {
        $sql     = 'UPDATE plugin_ldap_user' .
            ' SET login_confirmation_date = ' . db_ei($date) .
            ' WHERE user_id = ' . db_ei($userId);
        $updated = $this->update($sql);
        if (! $updated) {
            // Try to insert
            $updated = $this->createLdapUser($userId, $date);
        }
        return $updated;
    }

    /**
     * Check if a given name is not already a user name or a project name
     *
     * This should be in UserManager
     *
     * @param String $name Name to test
     *
     * @return bool
     */
    public function userNameIsAvailable($name)
    {
        $sql = 'SELECT user_name' .
            ' FROM user' .
            ' WHERE user_name LIKE ' . $this->da->quoteSmart($name, ['force_string']);
        if ($this->retrieve($sql)->rowCount() === 0) {
            $sql = 'SELECT group_id' .
                ' FROM `groups`' .
                ' WHERE unix_group_name LIKE ' . $this->da->quoteSmart($name, ['force_string']);
            if ($this->retrieve($sql)->rowCount() === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Update LDAP login of given user
     *
     * @param int $userId User ID to update
     * @param int $ldapUid LDAP login of the user
     *
     * @return bool
     */
    public function updateLdapUid($userId, $ldapUid)
    {
        $user_id  = $this->da->quoteSmart($userId);
        $ldap_uid = $this->da->quoteSmart($ldapUid, ['force_string']);

        $sql = "INSERT INTO plugin_ldap_user(user_id, ldap_uid) VALUES ($user_id, $ldap_uid)
                ON DUPLICATE KEY UPDATE ldap_uid = $ldap_uid";
        return $this->update($sql);
    }

    /**
     * Return number of active users
     */
    public function getNbrActiveUsers()
    {
        $sql = 'SELECT count(u.user_id) as count
        FROM user u
         JOIN plugin_ldap_user ldap_user ON (ldap_user.user_id = u.user_id)
        WHERE status IN ("A", "R")
        AND u.user_id > 101
        AND ldap_id IS NOT NULL
        AND ldap_id <> ""';
        return $this->retrieve($sql);
    }

    public function getActiveUsers()
    {
        $sql = 'SELECT u.user_id, user_name, email, ldap_id, status, realname, ldap_uid
        FROM user u
         JOIN plugin_ldap_user ldap_user ON (ldap_user.user_id = u.user_id)
        WHERE status IN ("A", "R")
        AND u.user_id > 101
        AND ldap_id IS NOT NULL
        AND ldap_id <> ""';
        return $this->retrieve($sql);
    }
}
