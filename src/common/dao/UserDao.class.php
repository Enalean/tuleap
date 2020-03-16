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

/**
 *  Data Access Object for User
 */
class UserDao extends DataAccessObject
{
    private const NOT_VALID_UNIX_PASSWORD_HASH = 'no_password';

    /** @var PasswordHandler */
    private $password_handler;

    public function __construct($da = null)
    {
        parent::__construct($da);
        $this->password_handler = PasswordHandlerFactory::getPasswordHandler();
    }

    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    public function searchAll()
    {
        $sql = "SELECT * FROM user";
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Status (either one value or array)
    * @return DataAccessResult
    */
    public function searchByStatus($status)
    {
        if (is_array($status)) {
            $where_status = $this->da->quoteSmartImplode(" OR status = ", $status);
        } else {
            $where_status = $this->da->quoteSmart($status);
        }
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM user WHERE status = $where_status";
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UserId
    * @return DataAccessResult
    */
    public function searchByUserId($userId)
    {
        $sql = sprintf(
            "SELECT * FROM user WHERE user_id = %s",
            $this->da->quoteSmart($userId)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches User by UserName
    * @return DataAccessResult
    */
    public function searchByUserName($userName)
    {
        $sql = sprintf(
            "SELECT * FROM user WHERE user_name = %s",
            $this->da->quoteSmart($userName)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches User by Email
    * @return DataAccessResult
    */
    public function searchByEmail($email)
    {
        $sql = sprintf(
            "SELECT * FROM user WHERE email = %s",
            $this->da->quoteSmart($email)
        );
        return $this->retrieve($sql);
    }

    public function searchByEmailList(array $emails)
    {
        if (empty($emails)) {
            return new DataAccessResultEmpty();
        }
        $emails_escaped = $this->da->quoteSmartImplode(',', $emails);

        $sql = "SELECT user1.*
                FROM user AS user1
                LEFT JOIN user AS user2 ON (
                  user1.email = user2.email AND
                  user1.user_id > user2.user_id
                )
                WHERE
                  user1.email IN ($emails_escaped) AND
                  user2.user_id IS NULL";

        return $this->retrieve($sql);
    }

    /**
     * Searches User by ldapid
     * @return DataAccessResult
     */
    public function searchByLdapId($ldap_id)
    {
        $sql = sprintf(
            "SELECT * FROM user WHERE ldap_id = %s",
            $this->da->quoteSmart($ldap_id)
        );
        return $this->retrieve($sql);
    }

    public function searchSSHKeys()
    {
        $sql = "SELECT *
                FROM user
                WHERE (status= 'A' OR status='R')
                  AND authorized_keys != ''
                  AND authorized_keys IS NOT NULL";
        return $this->retrieve($sql);
    }

    public function searchPaginatedSSHKeys($offset, $limit)
    {
        $offset = $this->da->escapeInt($offset);
        $limit  = $this->da->escapeInt($limit);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM user
                WHERE (status= 'A' OR status='R')
                  AND authorized_keys != ''
                  AND authorized_keys IS NOT NULL
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    /**
     * Search user by confirm hash
     *
     * @param String $hash
     *
     * @return DataAccessResult
     */
    public function searchByConfirmHash($hash)
    {
        $sql = 'SELECT * FROM user WHERE confirm_hash=' . $this->da->quoteSmart($hash);
        return $this->retrieve($sql);
    }

    /**
     * create a row in the table user
     * @param $user_name
     * @param $email
     * @param $user_pw
     * @param $realname
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
     * @return true or id(auto_increment) if there is no error
     */
    public function create($user_name, $email, $user_pw, $realname, $register_purpose, $status, $shell, $unix_status, $unix_uid, $unix_box, $ldap_id, $add_date, $confirm_hash, $mail_siteupdates, $mail_va, $sticky_login, $authorized_keys, $email_new, $timezone, $language_id, $expiry_date, $last_pwd_update)
    {
        $columns = array();
        $values  = array();

        if ($user_name !== null) {
            $columns[] = 'user_name';
            $values[]  = $user_name;
        }
        if ($email !== null) {
            $columns[] = 'email';
            $values[]  = $email;
        }
        if ($user_pw !== null) {
            $columns[] = 'password';
            $values[]  = $this->password_handler->computeHashPassword($user_pw);

            if (ForgeConfig::areUnixUsersAvailableOnSystem()) {
                $columns[] = 'unix_pw';
                $values[]  = $this->password_handler->computeUnixPassword($user_pw);
            } else {
                $columns[] = 'unix_pw';
                $values[]  = self::NOT_VALID_UNIX_PASSWORD_HASH;
            }
        }
        if ($realname !== null) {
            $columns[] = 'realname';
            $values[]  = $realname;
        }
        if ($register_purpose !== null) {
            $columns[] = 'register_purpose';
            $values[]  = $register_purpose;
        }
        if ($status !== null) {
            $columns[] = 'status';
            $values[]  = $status;
        }
        if ($shell !== null) {
            $columns[] = 'shell';
            $values[]  = $shell;
        }
        if ($unix_status !== null) {
            $columns[] = 'unix_status';
            $values[]  = $unix_status;
        }
        if ($unix_uid !== null) {
            $columns[] = 'unix_uid';
            $values[]  = $unix_uid;
        }
        if ($unix_box !== null) {
            $columns[] = 'unix_box';
            $values[]  = $unix_box;
        }
        if ($ldap_id !== null) {
            $columns[] = 'ldap_id';
            $values[]  = $ldap_id;
        }
        if ($add_date !== null) {
            $columns[] = 'add_date';
            $values[]  = $add_date;
        }
        if ($confirm_hash !== null) {
            $columns[] = 'confirm_hash';
            $values[]  = $confirm_hash;
        }
        if ($mail_siteupdates !== null) {
            $columns[] = 'mail_siteupdates';
            $values[]  = $mail_siteupdates;
        }
        if ($mail_va !== null) {
            $columns[] = 'mail_va';
            $values[]  = $mail_va;
        }
        if ($sticky_login !== null) {
            $columns[] = 'sticky_login';
            $values[]  = $sticky_login;
        }
        if ($authorized_keys !== null) {
            $columns[] = 'authorized_keys';
            $values[]  = $authorized_keys;
        }
        if ($email_new !== null) {
            $columns[] = 'email_new';
            $values[]  = $email_new;
        }
        if ($timezone !== null) {
            $columns[] = 'timezone';
            $values[]  = $timezone;
        }
        if ($language_id !== null) {
            $columns[] = 'language_id';
            $values[]  = $language_id;
        }
        if ($expiry_date !== null) {
            $columns[] = 'expiry_date';
            $values[]  = $expiry_date;
        }
        if ($last_pwd_update !== null) {
            $columns[] = 'last_pwd_update';
            $values[]  = $last_pwd_update;
        }

        $sql = 'INSERT INTO user (' . implode(',', $columns) . ') VALUES (' . $this->da->quoteSmartImplode(',', $values) . ')';
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar = $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
                $sql = 'INSERT INTO user_access (user_id) VALUES (' . $this->da->quoteSmart($inserted) . ')';
                $this->update($sql);
            } else {
                $inserted = $dar->isError();
            }
        }
        return $inserted;
    }

    public function updateByRow(array $user)
    {
        $stmt = array();
        if (isset($user['clear_password'])) {
            $stmt[] = 'password=' . $this->da->quoteSmart($this->password_handler->computeHashPassword($user['clear_password']));
            /*
             * Legacy column that was used to store password hashed with MD5
             * We need to keep it for old instances with non migrated accounts yet
             */
            $stmt[] = 'user_pw=""';
            if (ForgeConfig::areUnixUsersAvailableOnSystem()) {
                $stmt[] = 'unix_pw=' . $this->da->quoteSmart($this->password_handler->computeUnixPassword($user['clear_password']));
            } else {
                $stmt[] = 'unix_pw=' . $this->da->quoteSmart(self::NOT_VALID_UNIX_PASSWORD_HASH);
            }
            $stmt[] = 'last_pwd_update=' . $_SERVER['REQUEST_TIME'];
            unset($user['clear_password']);
        }
        $dar = $this->searchByUserId($user['user_id']);
        if ($dar && !$dar->isError()) {
            $current = $dar->current();
            foreach ($user as $field => $value) {
                if ($field != 'user_id' && $value != $current[$field] && $value !== null) {
                    $stmt[] = $field . ' = ' . $this->da->quoteSmart($value);
                }
            }
            if (count($stmt) > 0) {
                $sql = 'UPDATE user SET ' . implode(', ', $stmt) . ' WHERE user_id = ' . db_ei($user['user_id']);
                return $this->update($sql);
            }
        }
        return false;
    }

    /**
     * Assign to given user the next available unix_uid
     *
     * @param int $userId User ID
     *
     * @return bool
     */
    public function assignNextUnixUid($userId)
    {
        $sql = 'UPDATE user, (SELECT MAX(unix_uid)+1 AS max_uid FROM user) AS R' .
               ' SET unix_uid = max_uid' .
               ' WHERE user_id = ' . $this->da->quoteSmart($userId);
        if ($this->update($sql)) {
            $sql = 'SELECT unix_uid FROM user WHERE user_id = ' . $this->da->quoteSmart($userId);
            $dar = $this->retrieve($sql);
            if ($dar && !$dar->isError()) {
                $row = $dar->current();
                return $row['unix_uid'];
            }
        }
        return false;
    }

    /**
    * Searches User status by Email
    * @return DataAccessResult
    */
    public function searchStatusByEmail($email)
    {
        //ST: with LDAP user_name can be an email
        $sql = sprintf(
            "SELECT realname, email, status FROM user WHERE (user_name=%s OR email = %s)",
            $this->da->quoteSmart($email),
            $this->da->quoteSmart($email)
        );
        return $this->retrieve($sql);
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
    public function storeLoginSuccess($user_id, $time)
    {
        $sql = 'UPDATE user_access
                SET nb_auth_failure = 0,
                    prev_auth_success = last_auth_success,
                    last_auth_success = ' . $this->da->escapeInt($time) . ',
                    last_access_date =' . $this->da->escapeInt($time) . '
                WHERE user_id = ' . $this->da->escapeInt($user_id);
        $this->update($sql);
    }

     /**
     * Don't log access if already accessed in the past 6 hours (scalability+privacy)
     * @param  $user_id Integer
     * @param  $time    Integer
     * @return bool
     */
    public function storeLastAccessDate($user_id, $time)
    {
        $user_id = $this->da->escapeInt($user_id);
        $time    = $this->da->escapeInt($time);

        $sql = "UPDATE user_access
                SET last_access_date = $time
                WHERE user_id = $user_id
                  AND last_access_date < $time";

        return $this->update($sql);
    }

    /**
     * Store login failure.
     *
     * Store last log-on failure and increment the number of failure. If the there
     * was no bad attemps since the last successful login (ie. 'last_auth_success'
     * newer than 'last_auth_failure') the counter is reset to 1.
     */
    public function storeLoginFailure($login, $time)
    {
        $sql = "UPDATE user_access
                SET nb_auth_failure = IF(last_auth_success >= last_auth_failure, 1, nb_auth_failure + 1),
                last_auth_failure = " . $this->da->escapeInt($time) . "
                WHERE user_id = (SELECT user_id from user WHERE user_name = " . $this->da->quoteSmart($login) . ")";
        $this->update($sql);
    }

    /**
     * Search active users with realname or user_name like the variable.
     *
     * You can limit the number of results.
     * This is used by "search users as you type"
     */
    public function searchUserNameLike($name, $limit = 0, int $offset = 0)
    {
        $name = $this->getDa()->quoteLikeValueSurround($name);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *" .
            " FROM user" .
            " WHERE (realname LIKE $name" .
            " OR user_name LIKE $name)" .
            " AND status IN ('A', 'R')";
        $sql .= "ORDER BY realname";
        if ($limit > 0) {
            $sql .= " LIMIT " . db_ei($limit);

            if ($offset > 0) {
                $sql .= ' OFFSET ' . db_ei($offset);
            }
        }

        return $this->retrieve($sql);
    }

    /**
     * Return the result of  'FOUND_ROWS()' SQL method for the last query.
     *
     * @return string|false
     */
    public function foundRows()
    {
        $sql = "SELECT FOUND_ROWS() as nb;";
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError()) {
            $row = $dar->getRow();
            return $row['nb'];
        } else {
            return false;
        }
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
    public function renameUser($user, $newName)
    {
        if (! TrackerV3::instance()->available()) {
            return true;
        }

        $sqlArtcc = ' UPDATE artifact_cc SET email =' . $this->da->quoteSmart($newName) .
                     ' WHERE email = ' . $this->da->quoteSmart($user->getUserName());
        if ($this->update($sqlArtcc)) {
            $sqlSel = 'SELECT addresses, id FROM artifact_global_notification
                       WHERE addresses LIKE ' . $this->getDa()->quoteLikeValueSurround($user->getUserName());

            $dar = $this->retrieve($sqlSel);
            if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
                $res = true;
                foreach ($dar as $row) {
                    $row['addresses'] = $this->replaceStringInList($row['addresses'], $user->getUserName(), $newName);
                    $sqlArtgn = 'UPDATE artifact_global_notification SET addresses = ' . $this->da->quoteSmart($row['addresses']) . '
                                 WHERE id = ' . $this->da->escapeInt($row['id']);
                    $res = $res & $this->update($sqlArtgn);
                }
                return $res;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * return array of all users or users matching the pattern if $pattern is not empty and order
     * the result according to the clicked header and the order of sort
     *
     * @param int $group_id
     * @param string $pattern
     * @param int $offset
     * @param int $limit
     * @param string $sort_header
     * @param string $sort_order
     * @param array $status_values
     *
     * @psalm-return array{
     *      users: DataAccessResult|false,
     *      numrows: string|false
     * }
     * @return array
     */
    public function listAllUsers($group_id, $pattern, $offset, $limit, $sort_header, $sort_order, $status_values)
    {
        $group_id = $this->da->escapeInt($group_id);
        $offset   = $this->da->escapeInt($offset);
        $limit    = $this->da->escapeInt($limit);
        $stmLimit = "";
        if ($limit != 0) {
            $stmLimit .= ' LIMIT ' . $offset . ', ' . $limit;
        }
        $where  = "";
        $status = "";
        if (!empty($status_values)) {
            $status = $this->da->quoteSmartImplode(',', $status_values);
        }
        if ($pattern) {
            $pattern = $this->getDa()->quoteLikeValueSurround($pattern);
            $where = "WHERE (
                    user.user_name LIKE $pattern
                    OR user.user_id LIKE $pattern
                    OR user.realname LIKE $pattern
                    OR user.email LIKE $pattern)";
            if ($status != "") {
                $where .= ' AND (status IN (' . $status . '))';
            }
        } elseif ($status != "") {
            $where .= ' WHERE status IN (' . $status . ')';
        }

        $from = 'FROM user';
        if ($group_id) {
            $from .= " INNER JOIN user_group ON (user.user_id = user_group.user_id AND user_group.group_id = $group_id)";
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS user.*, admin_of.nb AS admin_of, member_of.nb AS member_of, user_access.last_access_date
                $from
                INNER JOIN user_access ON (user_access.user_id = user.user_id)
                LEFT JOIN (
                    SELECT count(admin_flags) as nb, user_id
                    FROM user_group
                    INNER JOIN groups USING(group_id)
                    WHERE groups.status = 'A' AND admin_flags='A'
                    GROUP BY user_id
                ) as admin_of ON (admin_of.user_id = user.user_id)
                LEFT JOIN (
                    SELECT count(group_id) as nb, user_id
                    FROM user_group
                    INNER JOIN groups USING(group_id)
                    WHERE groups.status = 'A'
                    GROUP BY user_id
                ) as member_of ON (member_of.user_id = user.user_id)
            $where
            ORDER BY " . $sort_header . " " . $sort_order . $stmLimit;

        return array(
            'users'   => $this->retrieve($sql),
            'numrows' => $this->foundRows()
        );
    }

    /**
     * Return the access information for a given user
     *
     * @param int $userId
     *
     * @return Array
     */
    public function getUserAccessInfo($userId)
    {
        $sql = 'SELECT * FROM user_access WHERE user_id = ' . $this->da->escapeInt($userId);
        $dar  = $this->retrieve($sql);
        if ($dar && !$dar->isError()) {
            $row = $dar->getRow();
            return $row;
        } else {
            return false;
        }
    }

    /**
     * Get the list of letters with which user names begin (silly isn't it?)
     *
     * @return DataAccessResult
     */
    public function firstUsernamesLetters()
    {
        $sql = "SELECT DISTINCT UPPER(LEFT(user.email,1)) as capital
                FROM user
                WHERE status in ('A', 'R')
                UNION
                SELECT DISTINCT UPPER(LEFT(user.realname,1)) as capital
                FROM user
                WHERE status in ('A', 'R')
                UNION
                SELECT DISTINCT UPPER(LEFT(user.user_name,1)) as capital
                FROM user
                WHERE status in ('A', 'R')
                ORDER BY capital";
        return $this->retrieve($sql);
    }

    public function searchGlobalPaginated($words, $exact, $offset, $limit)
    {
        $offset = $this->da->escapeInt($offset);
        $limit  = $this->da->escapeInt($limit);
        if ($exact === true) {
            $user_name = $this->searchExactMatch($words);
            $realname  = $this->searchExactMatch($words);
        } else {
            $user_name = $this->searchExplodeMatch('user_name', $words);
            $realname  = $this->searchExplodeMatch('realname', $words);
        }

        $sql = "SELECT *
                FROM user
                WHERE (
                    (user_name LIKE $user_name) OR (realname LIKE $realname)
                ) AND status IN ('A', 'R')
                ORDER BY user_name
                LIMIT $offset, $limit";

        return $this->retrieve($sql);
    }

    public function countAllUsers()
    {
        $sql = "SELECT count(*) AS nb FROM user";

        $row = $this->retrieve($sql)->getRow();

        return $row['nb'];
    }

    public function countAllAliveUsers()
    {
        $sql = "SELECT count(*) AS nb FROM user WHERE status IN ('A', 'R')";

        $row = $this->retrieve($sql)->getRow();

        return $row['nb'];
    }

    public function countAliveUsersRegisteredBefore($timestamp)
    {
        $timestamp = $this->da->escapeInt($timestamp);

        $sql = "SELECT count(*) AS nb FROM user WHERE add_date >= $timestamp AND status IN ('A', 'R')";

        $row = $this->retrieve($sql)->getRow();

        return $row['nb'];
    }

    public function removeConfirmHash($confirm_hash)
    {
        $confirm_hash = $this->da->quoteSmart($confirm_hash);
        $sql = "UPDATE user SET confirm_hash = null WHERE confirm_hash=$confirm_hash";
        return $this->update($sql);
    }
}
