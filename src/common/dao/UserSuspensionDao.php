<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Dao;

use DateTimeImmutable;
use Tuleap\DB\DataAccessObject;

class UserSuspensionDao extends DataAccessObject
{

    /**
     * Gets the user_id and last_access_date of idle users
     */
    public function getIdleAccounts(DateTimeImmutable $start_date, DateTimeImmutable $end_date): array
    {
        $start_date_timestamp = $start_date->getTimestamp();
        $end_date_timestamp = $end_date->getTimestamp();

        $sql  = 'SELECT  user.user_id, last_access_date FROM user ' .
        ' INNER JOIN user_access AS access  ON user.user_id=access.user_id' .
        ' WHERE (user.status != "D" AND user.status != "S") AND ' .
        ' (access.last_access_date != 0 AND access.last_access_date BETWEEN ? AND ?)';
        return $this->getDB()->run($sql, $start_date_timestamp, $end_date_timestamp);
    }

    public function suspendAccount(int $user_id)
    {
        $sql = 'UPDATE user SET status = "S", unix_status = "S"' .
            ' WHERE status != "D" AND user.user_id =  ? ';
        return $this->getDB()->run($sql, $user_id);
    }

    /**
     * Suspend user account according to given date
     */
    public function suspendExpiredAccounts(DateTimeImmutable $date)
    {
        $timestamp = $date->getTimestamp();
        $sql = 'UPDATE user SET status = "S", unix_status = "S"' .
            ' WHERE ( status != "D" AND expiry_date != 0' .
            ' AND expiry_date <  ? )';
        return $this->getDB()->run($sql, $timestamp);
    }

    /**
     * Suspend account of users who didn't access the platform after given date
     */
    public function suspendInactiveAccounts(DateTimeImmutable $date)
    {
        $timestamp = $date->getTimestamp();
        $sql  = 'UPDATE user AS user' .
            ' INNER JOIN user_access AS access ON user.user_id=access.user_id' .
            ' SET user.status = "S", user.unix_status = "S"' .
            ' WHERE user.status != "D" AND (' .
            '(access.last_access_date = 0 AND user.add_date < ? ) OR ' .
            '(access.last_access_date != 0 AND access.last_access_date < ? ))';
        return $this->getDB()->run($sql, $timestamp, $timestamp);
    }

    /**
     * Return list of user_id that are not member of any project
     */
    public function returnNotProjectMembers(): array
    {
        $sql = 'SELECT user_id FROM user LEFT JOIN user_group USING(user_id) WHERE group_id IS NULL and status in ("A","R")';
        return $this->getDB()->run($sql);
    }

    /**
     * Return the last date of being removed from the last project
     */
    public function delayForBeingNotProjectMembers(int $user_id): array
    {
        $req = 'SELECT date from group_history where field_name = "removed_user" and old_value REGEXP ? order by date desc LIMIT 1';
        $param = '[(]' . $this->getDB()->escapeLikeValue((string) $user_id) . '[)]$';
        return $this->getDB()->run($req, $param);
    }

    /**
     * Return 1 row if delay allowed to  be subscribed without belonging to any project has expired
     * else 0 row
     */
    public function delayForBeingSubscribed(int $user_id, DateTimeImmutable $date): array
    {
        //Return delay for being subscribed and not being added to any project
        $timestamp = $date->getTimestamp();
        $select = 'SELECT NULL from user where user_id = ? and add_date < ? ';
        return $this->getDB()->run($select, $user_id, $timestamp);
    }

    public function verifySuspension(int $user_id): bool
    {
        $sql = "SELECT user.status FROM user WHERE user.user_id = ? ";
        $res = $this->getDB()->run($sql, $user_id);
        if ($res[0]['status'] == 'S') {
            return true;
        } else {
            return false;
        }
    }


    public function getUsersWithoutConnectionOrAccessBetweenDates(DateTimeImmutable $start_date, DateTimeImmutable $end_date): array
    {
        $start_date_timestamp = $start_date->getTimestamp();
        $end_date_timestamp = $end_date->getTimestamp();

        $sql  = 'SELECT user.user_id, last_access_date FROM user' .
            ' INNER JOIN user_access AS access ON user.user_id=access.user_id' .
            ' WHERE (user.status != "S" AND user.status != "D" AND (' .
            '(access.last_access_date = 0 AND user.add_date BETWEEN ? AND ? ) OR ' .
            '(access.last_access_date != 0 AND access.last_access_date BETWEEN ? AND ?)))';

        return $this->getDB()->run(
            $sql,
            $start_date_timestamp,
            $end_date_timestamp,
            $start_date_timestamp,
            $end_date_timestamp
        );
    }
}
