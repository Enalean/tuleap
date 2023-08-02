<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DBConnection;
use Tuleap\User\UnixUserChecker;

/**
 *  Data Access Object for User
 */
class UserDao extends \Tuleap\DB\DataAccessObject
{
    private const NOT_VALID_UNIX_PASSWORD_HASH = 'no_password';

    /** @var PasswordHandler */
    private $password_handler;

    public function __construct(?DBConnection $db_connection = null)
    {
        parent::__construct($db_connection);
        $this->password_handler = PasswordHandlerFactory::getPasswordHandler();
    }

    public function searchAll(): array
    {
        $sql = "SELECT * FROM user";
        return $this->getDB()->run($sql);
    }

    /**
    * Searches User by Status (either one value or array)
    */
    public function searchByStatus($status): array
    {
        $where = \ParagonIE\EasyDB\EasyStatement::open();
        if (is_array($status)) {
            $where->in('status IN (?*)', $status);
        } else {
            $where->with('status = ?', $status);
        }
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM user WHERE $where";
        return (array) $this->getDB()->safeQuery($sql, $where->values());
    }

    /**
    * Searches User by UserId
    */
    public function searchByUserId($userId): ?array
    {
        return $this->getDB()->row('SELECT * FROM user WHERE user_id = ?', $userId) ?: null;
    }

    /**
    * Searches User by UserName
    */
    public function searchByUserName($userName): ?array
    {
        return $this->getDB()->row('SELECT * FROM user WHERE user_name = ?', $userName) ?: null;
    }

    /**
    * Searches User by Email
    */
    public function searchByEmail($email): array
    {
        return $this->getDB()->run('SELECT * FROM user WHERE email = ?', $email);
    }

    public function searchByEmailList(array $emails): array
    {
        if (empty($emails)) {
            return [];
        }
        $email_filter = \ParagonIE\EasyDB\EasyStatement::open()->in('user1.email IN (?*)', $emails);

        $sql = "SELECT user1.*
                FROM user AS user1
                LEFT JOIN user AS user2 ON (
                  user1.email = user2.email AND
                  user1.user_id > user2.user_id
                )
                WHERE
                  $email_filter AND
                  user2.user_id IS NULL";

        return (array) $this->getDB()->safeQuery($sql, $email_filter->values());
    }

    /**
     * Searches User by ldapid
     */
    public function searchByLdapId($ldap_id): array
    {
        return $this->getDB()->run('SELECT * FROM user WHERE ldap_id = ?', $ldap_id);
    }

    public function searchSSHKeys(): array
    {
        $sql = "SELECT *
                FROM user
                WHERE (status= 'A' OR status='R')
                  AND authorized_keys != ''
                  AND authorized_keys IS NOT NULL";
        return $this->getDB()->run($sql);
    }

    public function searchPaginatedSSHKeys($offset, $limit): array
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM user
                WHERE (status= 'A' OR status='R')
                  AND authorized_keys != ''
                  AND authorized_keys IS NOT NULL
                LIMIT ? OFFSET ?";

        return $this->getDB()->run($sql, $limit, $offset);
    }

    /**
     * Search user by confirm hash
     *
     * @param String $hash
     */
    public function searchByConfirmHash($hash): ?array
    {
        return $this->getDB()->row('SELECT * FROM user WHERE confirm_hash = ?', $hash) ?: null;
    }

    /**
     * create a row in the table user
     * @param $user_name
     * @param $email
     * @param $register_purpose
     * @param $status
     * @param $shell
     * @param $unix_status
     * @param $unix_uid
     * @param $unix_box
     * @param $ldap_id
     * @param $add_date
     * @param $confirm_hash
     * @param $mail_siteupdates
     * @param $mail_va
     * @param $sticky_login
     * @param $authorized_keys
     * @param $email_new
     * @param $timezone
     * @param $language_id
     * @param $expiry_date
     * @param $last_pwd_update
     */
    public function create($user_name, $email, ?ConcealedString $user_pw, ?string $realname, $register_purpose, $status, $shell, $unix_status, $unix_uid, $unix_box, $ldap_id, $add_date, $confirm_hash, $mail_siteupdates, $mail_va, $sticky_login, $authorized_keys, $email_new, $timezone, $language_id, $expiry_date, $last_pwd_update): int
    {
        $fields = [];

        if ($user_name !== null) {
            $fields['user_name'] = $user_name;
        }
        if ($email !== null) {
            $fields['email'] = $email;
        }
        if ($user_pw !== null) {
            $fields['password'] = $this->password_handler->computeHashPassword($user_pw);

            if (UnixUserChecker::doesPlatformAllowUnixUserAndIsUserNameValid($user_name)) {
                $fields['unix_pw'] = $this->password_handler->computeUnixPassword($user_pw);
            } else {
                $fields['unix_pw'] = self::NOT_VALID_UNIX_PASSWORD_HASH;
            }
        }
        $fields['realname'] = $realname ?? '';
        if ($register_purpose !== null) {
            $fields['register_purpose'] = $register_purpose;
        }
        if ($status !== null) {
            $fields['status'] = $status;
        }
        if ($shell !== null) {
            $fields['shell'] = $shell;
        }
        if ($unix_status !== null) {
            $fields['unix_status'] = $unix_status;
        }
        if ($unix_uid !== null) {
            $fields['unix_uid'] = $unix_uid;
        }
        if ($unix_box !== null) {
            $fields['unix_box'] = $unix_box;
        }
        if ($ldap_id !== null) {
            $fields['ldap_id'] = $ldap_id;
        }
        if ($add_date !== null) {
            $fields['add_date'] = $add_date;
        }
        if ($confirm_hash !== null) {
            $fields['confirm_hash'] = $confirm_hash;
        }
        if ($mail_siteupdates !== null) {
            $fields['mail_siteupdates'] = $mail_siteupdates;
        }
        if ($mail_va !== null) {
            $fields['mail_va'] = $mail_va;
        }
        if ($sticky_login !== null) {
            $fields['sticky_login'] = $sticky_login;
        }
        if ($authorized_keys !== null) {
            $fields['authorized_keys'] = $authorized_keys;
        }
        if ($email_new !== null) {
            $fields['email_new'] = $email_new;
        }
        if ($timezone !== null) {
            $fields['timezone'] = $timezone;
        }
        if ($language_id !== null) {
            $fields['language_id'] = $language_id;
        }
        if ($expiry_date !== null) {
            $fields['expiry_date'] = $expiry_date;
        }
        if ($last_pwd_update !== null) {
            $fields['last_pwd_update'] = $last_pwd_update;
        }

        return $this->getDB()->tryFlatTransaction(
            static function (\ParagonIE\EasyDB\EasyDB $db) use ($fields): int {
                $id = $db->insertReturnId('user', $fields);
                $db->run('INSERT INTO user_access (user_id) VALUES (?)', $id);

                return (int) $id;
            }
        );
    }

    public function updateByRow(array $user): bool
    {
        return $this->getDB()->tryFlatTransaction(
            function (\ParagonIE\EasyDB\EasyDB $db) use ($user): bool {
                $user_db_row_current = $this->searchByUserId($user['user_id']);
                if ($user_db_row_current === null) {
                    return false;
                }

                $values = [];

                if (isset($user['clear_password'])) {
                    $values['password'] = $this->password_handler->computeHashPassword($user['clear_password']);
                    /*
                     * Legacy column that was used to store password hashed with MD5
                     * We need to keep it for old instances with non migrated accounts yet
                     */
                    $values['unix_pw'] = self::NOT_VALID_UNIX_PASSWORD_HASH;
                    if (UnixUserChecker::doesPlatformAllowUnixUserAndIsUserNameValid($user["user_name"])) {
                        $values['unix_pw'] = $this->password_handler->computeUnixPassword($user['clear_password']);
                    }
                    unset($user['clear_password']);
                    $values['last_pwd_update'] = $_SERVER['REQUEST_TIME'];
                }

                foreach ($user as $field => $value) {
                    if ($field !== 'user_id' && $value != $user_db_row_current[$field] && $value !== null) {
                        $values[$field] = $value;
                    }
                }

                $db->update('user', $values, ['user_id' => $user['user_id']]);
                return true;
            }
        );
    }

    /**
     * Assign to given user the next available unix_uid
     *
     * @param int $userId User ID
     */
    public function assignNextUnixUid($userId): int
    {
        $sql = 'UPDATE user, (SELECT MAX(unix_uid)+1 AS max_uid FROM user) AS R' .
               ' SET unix_uid = max_uid' .
               ' WHERE user_id = ?';
        $this->getDB()->run($sql, $userId);
        return $this->getDB()->single('SELECT unix_uid FROM user WHERE user_id = ?', [$userId]);
    }

    /**
     * Store login success.
     *
     * Store last log-on success timestamp in 'last_auth_success' field and backup
     * the previous value in 'prev_auth_success'. In order to keep the failure
     * counter coherent, if the 'last_auth_success' is newer than the
     * 'last_auth_failure' it means that there was no bad attempts since the last
     * log-on and 'nb_auth_failure' can be reset to zero.
     *
     * @todo: define a global time object that would give the same time to all
     * actions on an execution.
     */
    public function storeLoginSuccess($user_id, $time): bool
    {
        return $this->getDB()->tryFlatTransaction(function (\ParagonIE\EasyDB\EasyDB $db) use ($user_id, $time): bool {
            $this->flagUserAsFirstTimerIfItIsTheirFirstLogin((int) $user_id);
            $sql = 'UPDATE user_access
                SET nb_auth_failure = 0,
                    prev_auth_success = last_auth_success,
                    last_auth_success = ?,
                    last_access_date = ?
                WHERE user_id = ?';
            $this->getDB()->run($sql, $time, $time, $user_id);
            return 1 === $this->getDB()->cell("SELECT is_first_timer FROM user WHERE user_id = ?", $user_id);
        });
    }

     /**
     * Don't log access if already accessed in the past 6 hours (scalability+privacy)
     * @param  $user_id Integer
     * @param  $time    Integer
     */
    public function storeLastAccessDate($user_id, $time): void
    {
        $sql = "UPDATE user_access
                SET last_access_date = ?
                WHERE user_id = ?
                  AND last_access_date < ?";

        $this->getDB()->run($sql, $time, $user_id, $time);
    }

    /**
     * Store login failure.
     *
     * Store last log-on failure and increment the number of failure. If the there
     * was no bad attemps since the last successful login (ie. 'last_auth_success'
     * newer than 'last_auth_failure') the counter is reset to 1.
     */
    public function storeLoginFailure($login, $time): void
    {
        $sql = "UPDATE user_access
                SET nb_auth_failure = IF(last_auth_success >= last_auth_failure, 1, nb_auth_failure + 1),
                last_auth_failure = ?
                WHERE user_id = (SELECT user_id from user WHERE user_name = ?)";
        $this->getDB()->run($sql, $time, $login);
    }

    /**
     * Search active users with realname or user_name like the variable.
     *
     * You can limit the number of results.
     * This is used by "search users as you type"
     */
    public function searchUserNameLike($name, $limit = 0, int $offset = 0): array
    {
        $name = '%' . $this->getDB()->escapeLikeValue($name) . '%';

        $sql        = "SELECT SQL_CALC_FOUND_ROWS *" .
            " FROM user" .
            " WHERE (realname LIKE ?" .
            " OR user_name LIKE ?)" .
            " AND status IN ('A', 'R')";
        $sql       .= "ORDER BY realname ";
        $limit_stmt = null;
        if ($limit > 0) {
            $limit_stmt = \ParagonIE\EasyDB\EasyStatement::open();
            if ($offset > 0) {
                $limit_stmt->with('LIMIT ? OFFSET ?', $limit, $offset);
            } else {
                $limit_stmt->with('LIMIT ?', $limit);
            }
        }

        if ($limit_stmt === null) {
            return $this->getDB()->run($sql, $name, $name);
        }

        return $this->getDB()->run($sql . $limit_stmt, $name, $name, ...$limit_stmt->values());
    }

    /**
     * Replace all occurences of $search in $subject replaced by $replace
     *
     * This method takes into account strings separted by coma.
     * We assume that we search about tazmani, it may be saved in addresses field like this:
     * (1)tazmani,mickey
     * (2)mickey,tazmani
     * (3)mickey,tazmani,minnie
     * (4)tazmani
     *
     * @param String $subject The original string
     * @param String $search  The value to being searched for
     * @param String $replace The replacement value that replaces found search values
     *
     * @return String
     */
    public function replaceStringInList($subject, $search, $replace)
    {
        $tokens = explode(',', $subject);
        foreach ($tokens as $k => $str) {
            $tokens[$k] = preg_replace('%^(\s*)' . preg_quote($search, '%') . '(\s*)$%', '$1' . $replace . '$2', $str);
        }
        return implode(',', $tokens);
    }

    /* Update user name in fields may be involved when renaming user
     *
     * @param User   $user
     * @param String $newName
     * @return Boolean
     */
    public function renameUser($user, $newName): bool
    {
        if (! TrackerV3::instance()->available()) {
            return true;
        }

        $sqlArtcc = ' UPDATE artifact_cc SET email = ? WHERE email = ?';
        $this->getDB()->run($sqlArtcc, $newName, $user->getUserName());

        $sqlSel = 'SELECT addresses, id FROM artifact_global_notification
                       WHERE addresses LIKE ?';

        foreach ($this->getDB()->run($sqlSel, '%' . $this->getDB()->escapeLikeValue($user->getUserName()) . '%') as $row) {
            $row['addresses'] = $this->replaceStringInList($row['addresses'], $user->getUserName(), $newName);
            $sqlArtgn         = 'UPDATE artifact_global_notification SET addresses = ? WHERE id = ?';
            $this->getDB()->run($sqlArtgn, $row['addresses'], $row['id']);
        }

        return true;
    }

    /**
     * return array of all users or users matching the pattern if $pattern is not empty and order
     * the result according to the clicked header and the order of sort
     *
     * @param int $group_id
     * @param string $pattern
     * @param int $offset
     * @param int $limit
     * @param array $status_values
     *
     * @psalm-param "user_name"|"realname"|"status" $sort_header
     * @psalm-param "ASC"|"DESC" $sort_order
     *
     * @psalm-return array{
     *      users: array,
     *      numrows: int
     * }
     * @return array
     */
    public function listAllUsers($group_id, $pattern, $offset, $limit, string $sort_header, string $sort_order, $status_values): array
    {
        $stmt_limit = null;
        if ($limit != 0) {
            $stmt_limit = \ParagonIE\EasyDB\EasyStatement::open()->with('LIMIT ?, ?', $offset, $limit);
        }
        $where_stmt = null;
        if ($pattern) {
            $pattern    =  '%' . $this->getDB()->escapeLikeValue($pattern) . '%';
            $where_stmt = \ParagonIE\EasyDB\EasyStatement::open()->with(
                'WHERE (user.user_name LIKE ? OR user.user_id LIKE ? OR user.realname LIKE ? OR user.email LIKE ?)',
                $pattern,
                $pattern,
                $pattern,
                $pattern
            );
            if (count($status_values) > 0) {
                $where_stmt->andIn('status IN (?*)', $status_values);
            }
        } elseif (count($status_values) > 0) {
            $where_stmt = ParagonIE\EasyDB\EasyStatement::open()->in('WHERE status IN (?*)', $status_values);
        }

        $from             = 'FROM user';
        $from_join_values = [];
        if ($group_id) {
            $from              .= " INNER JOIN user_group ON (user.user_id = user_group.user_id AND user_group.group_id = ?)";
            $from_join_values[] = $group_id;
        }

        $where        = '';
        $where_values = [];
        if ($where_stmt !== null) {
            $where        = $where_stmt->sql();
            $where_values = array_values($where_stmt->values());
        }
        $limit        = '';
        $limit_values = [];
        if ($stmt_limit !== null) {
            $limit        = $stmt_limit->sql();
            $limit_values = array_values($stmt_limit->values());
        }
        $sql = "SELECT SQL_CALC_FOUND_ROWS user.*, admin_of.nb AS admin_of, member_of.nb AS member_of, user_access.last_access_date
                $from
                INNER JOIN user_access ON (user_access.user_id = user.user_id)
                LEFT JOIN (
                    SELECT count(admin_flags) as nb, user_id
                    FROM user_group
                    INNER JOIN `groups` USING(group_id)
                    WHERE `groups`.status = 'A' AND admin_flags='A'
                    GROUP BY user_id
                ) as admin_of ON (admin_of.user_id = user.user_id)
                LEFT JOIN (
                    SELECT count(group_id) as nb, user_id
                    FROM user_group
                    INNER JOIN `groups` USING(group_id)
                    WHERE `groups`.status = 'A'
                    GROUP BY user_id
                ) as member_of ON (member_of.user_id = user.user_id)
            $where
            ORDER BY " . $sort_header . " " . $sort_order . ' ' . $limit;

        return [
            'users'   => (array) $this->getDB()->safeQuery($sql, [...$from_join_values, ...$where_values, ...$limit_values]),
            'numrows' => $this->foundRows(),
        ];
    }

    /**
     * Return the access information for a given user
     *
     * @param int $userId
     */
    public function getUserAccessInfo($userId): array
    {
        $sql = 'SELECT * FROM user_access WHERE user_id = ?';

        return $this->getDB()->row($sql, $userId);
    }

    public function searchGlobalPaginated($words, $exact, $offset, $limit): array
    {
        $username_stmt = \ParagonIE\EasyDB\EasyStatement::open();
        $realname_stmt = \ParagonIE\EasyDB\EasyStatement::open();
        if ($exact === true) {
            $username_stmt->with('user_name LIKE ?', '%' . $this->getDB()->escapeLikeValue($words) . '%');
            $realname_stmt->with('realname LIKE ?', '%' . $this->getDB()->escapeLikeValue($words) . '%');
        } else {
            foreach (explode(' ', $words) as $word) {
                $username_stmt->orWith('user_name LIKE ?', '%' . $this->getDB()->escapeLikeValue($word) . '%');
                $realname_stmt->orWith('realname LIKE ?', '%' . $this->getDB()->escapeLikeValue($word) . '%');
            }
        }

        $sql = "SELECT *
                FROM user
                WHERE (
                    ($username_stmt) OR ($realname_stmt)
                ) AND status IN ('A', 'R')
                ORDER BY user_name
                LIMIT ?, ?";

        return (array) $this->getDB()->safeQuery(
            $sql,
            [...array_values($username_stmt->values()), ...array_values($realname_stmt->values()), $offset, $limit]
        );
    }

    public function countAllUsers(): int
    {
        $sql = "SELECT count(*) AS nb FROM user";

        return $this->getDB()->single($sql);
    }

    public function countAllAliveUsers(): int
    {
        $sql = "SELECT count(*) AS nb FROM user WHERE status IN ('A', 'R')";

        return $this->getDB()->single($sql);
    }

    public function countAliveUsersRegisteredBefore($timestamp): int
    {
        $sql = "SELECT count(*) AS nb FROM user WHERE add_date >= ? AND status IN ('A', 'R')";

        return $this->getDB()->single($sql, [$timestamp]);
    }

    public function removeConfirmHash($confirm_hash): void
    {
        $sql = "UPDATE user SET confirm_hash = null WHERE confirm_hash=?";
        $this->getDB()->run($sql, $confirm_hash);
    }

    public function searchUsersWithDefaultAvatar(): array
    {
        return $this->getDB()->run('SELECT * FROM user WHERE has_custom_avatar = FALSE');
    }

    public function updatePendingExpiredUsersToDeleted(int $current_time, int $pending_account_lifetime): void
    {
        if ($pending_account_lifetime <= 0) {
            return;
        }

        $this->getDB()->run(
            'UPDATE user SET status="D" WHERE status="P" AND add_date + ? < ?',
            $pending_account_lifetime,
            $current_time,
        );
    }

    public function userWillNotBeAnymoreAFirstTimer(int $user_id): void
    {
        $this->getDB()->update('user', ['is_first_timer' => false], ['user_id' => $user_id]);
    }

    private function flagUserAsFirstTimerIfItIsTheirFirstLogin(int $user_id): void
    {
        $this->getDB()->run(
            'UPDATE user LEFT JOIN user_access USING (user_id)
            SET is_first_timer = true
            WHERE user.user_id = ? AND (user_access.user_id IS NULL OR user_access.last_auth_success = 0)',
            $user_id
        );
    }

    public function switchPasswordlessOnlyAuth(int $user_id, bool $passwordless_only): void
    {
        $this->getDB()->run(
            'UPDATE user
            SET passwordless_only = ?
            WHERE user_id = ?',
            $passwordless_only,
            $user_id
        );
    }

    public function isPasswordlessOnlyAuth(int $user_id): bool
    {
        $row = $this->getDB()->row('SELECT passwordless_only FROM user WHERE user_id = ?', $user_id);

        if ($row) {
            return $row['passwordless_only'];
        }

        return false;
    }
}
