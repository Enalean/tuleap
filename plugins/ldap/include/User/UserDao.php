<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\LDAP\User;

use LDAP_User;
use ParagonIE\EasyDB\EasyStatement;
use ParagonIE\EasyDB\Exception\MustBeNonEmpty;
use Tuleap\DB\DataAccessObject;

class UserDao extends DataAccessObject
{
    /**
     * @return array<array{user_id: int, ldap_uid: string, status: string}>
     */
    public function searchLdapLoginFromUserIds(array $user_ids): array
    {
        try {
            $user_ids_in_condition = EasyStatement::open()->in('?*', $user_ids);

            $sql = <<<SQL
                SELECT user_id, ldap_uid, user.status
                FROM plugin_ldap_user
                    INNER JOIN user USING (user_id)
                WHERE user_id IN ($user_ids_in_condition)
                SQL;

            return $this->getDB()->safeQuery($sql, $user_ids_in_condition->values());
        } catch (MustBeNonEmpty) {
            return [];
        }
    }

    public function alreadyLoggedInOnce(int $userId): bool
    {
        $sql = 'SELECT NULL' .
            ' FROM plugin_ldap_user ldap_u' .
            '   INNER JOIN user u USING (user_id)' .
            ' WHERE u.user_id = ?' .
            ' AND u.ldap_id != ""' .
            ' AND u.ldap_id IS NOT NULL' .
            ' AND login_confirmation_date = 0';

        $result = $this->getDB()->run($sql, $userId);
        if (count($result) === 1) {
            return false;
        }
        return true;
    }

    public function hasLoginConfirmationDate(LDAP_User $user): bool
    {
        $sql    = 'SELECT NULL FROM plugin_ldap_user WHERE user_id = ? AND login_confirmation_date != 0';
        $result = $this->getDB()->run($sql, $user->getId());
        return count($result) !== 0;
    }

    public function createLdapUser(int $user_id, int $date = 0, string $ldap_uid = ""): void
    {
        $sql = 'INSERT INTO plugin_ldap_user(user_id, login_confirmation_date, ldap_uid) VALUES (?, ?, ?)';

        $this->getDB()->run($sql, $user_id, $date, $ldap_uid);
    }

    public function setLoginDate(int $user_id, int $date): void
    {
        $sql    = 'SELECT NULL FROM plugin_ldap_user WHERE user_id = ?';
        $result = $this->getDB()->run($sql, $user_id);
        if (count($result) > 0) {
            $sql = 'UPDATE plugin_ldap_user SET login_confirmation_date = ? WHERE user_id = ?';
            $this->getDB()->run($sql, $date, $user_id);
        } else {
            $this->createLdapUser($user_id, $date);
        }
    }

    public function userNameIsAvailable(string $name): bool
    {
        $sql    = 'SELECT NULL FROM user where user_name LIKE ?';
        $result = $this->getDB()->run($sql, $name);
        if (count($result) !== 0) {
            return false;
        }
        $sql    = 'SELECT NULL FROM `groups` WHERE unix_group_name LIKE ?';
        $result = $this->getDB()->run($sql, $name);
        if (count($result) !== 0) {
            return false;
        }
        return true;
    }

    public function updateLdapUid(int $user_id, string $ldap_uid): void
    {
        $sql = "INSERT INTO plugin_ldap_user(user_id, ldap_uid) VALUES (?, ?) ON DUPLICATE KEY UPDATE ldap_uid = ?";
        $this->getDB()->run($sql, $user_id, $ldap_uid, $ldap_uid);
    }

    /**
     * Return number of active users
     */
    public function getNbrActiveUsers(): int
    {
        $sql = <<<SQL
            SELECT count(u.user_id) as count
            FROM user u
             JOIN plugin_ldap_user ldap_user ON (ldap_user.user_id = u.user_id)
            WHERE status IN ("A", "R")
            AND u.user_id > 101
            AND ldap_id IS NOT NULL
            AND ldap_id <> ""
            SQL;
        return $this->getDB()->cell($sql);
    }

    public function getActiveUsers(): array
    {
        $sql = <<<SQL
            SELECT u.user_id, user_name, email, ldap_id, status, realname, ldap_uid
            FROM user u
             JOIN plugin_ldap_user ldap_user ON (ldap_user.user_id = u.user_id)
            WHERE status IN ("A", "R")
            AND u.user_id > 101
            AND ldap_id IS NOT NULL
            AND ldap_id <> ""
            SQL;
        return $this->getDB()->run($sql);
    }
}
