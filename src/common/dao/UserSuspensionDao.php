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
use Tuleap\User\UserSuspensionLogger;

class UserSuspensionDao extends DataAccessObject
{
    /**
     * @var UserSuspensionLogger
     */
    private $userSuspensionLogger;

    public function __construct(UserSuspensionLogger $userSuspensionLogger)
    {
        parent::__construct(null);
        $this->userSuspensionLogger = $userSuspensionLogger;
    }

    /**
     * Gets the user_id and last_access_date of idle users
     *
     * @param DateTimeImmutable $start_date
     * @param DateTimeImmutable $end_date
     *
     * @return array
     */
    public function getIdleAccounts(DateTimeImmutable $start_date, DateTimeImmutable $end_date)
    {
        $start_date_timestamp = $start_date->getTimestamp();
        $end_date_timestamp = $end_date->getTimestamp();

        $sql  = 'SELECT  user.user_id, last_access_date FROM user ' .
        ' INNER JOIN user_access AS access  ON user.user_id=access.user_id' .
        ' WHERE (user.status != "D" AND user.status != "S") AND ' .
        ' (access.last_access_date != 0 AND access.last_access_date BETWEEN ? AND ?)';
        return $this->getDB()->run($sql, $start_date_timestamp, $end_date_timestamp);
    }

    /**
     * @param int $user_id
     */
    private function suspendAccount(int $user_id)
    {
        $sql = 'UPDATE user SET status = "S", unix_status = "S"' .
            ' WHERE status != "D" AND user.user_id =  ? ';
        return $this->getDB()->run($sql, $user_id);
    }

    /**
     * Suspend user account according to given date
     *
     * @param DateTimeImmutable $date
     */
    public function suspendExpiredAccounts(DateTimeImmutable $date)
    {
        $timestamp = $date->getTimestamp();
        $sql = 'UPDATE user SET status = "S", unix_status = "S"' .
            ' WHERE ( status != "D" AND expiry_date != 0'.
            ' AND expiry_date <  ? )';
        return $this->getDB()->run($sql, $timestamp);
    }

    /**
     * Suspend account of users who didn't access the platform after given date
     *
     * @param DateTimeImmutable $date
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
     *
     * @return array
     */
    private function returnNotProjectMembers()
    {
        $sql = 'SELECT user_id FROM user LEFT JOIN user_group USING(user_id) WHERE group_id IS NULL and status in ("A","R")';
        return $this->getDB()->run($sql);
    }

    /**
     * Return the last date of being removed from the last project
     * @param $user_id
     * @return array
     */
    private function delayForBeingNotProjectMembers(int $user_id)
    {
        $req = 'SELECT date from group_history where field_name = "removed_user" and old_value REGEXP ? order by date desc LIMIT 1';
        $param = '[(]' . $this->getDB()->escapeLikeValue((string) $user_id) .'[)]$';
        return $this->getDB()->run($req, $param);
    }

    /**
     * Return 1 row if delay allowed to  be subscribed without belonging to any project has expired
     * else 0 row
     * @param int $user_id
     * @param DateTimeImmutable $date
     * @return array
     */
    private function delayForBeingSubscribed(int $user_id, DateTimeImmutable $date)
    {
        //Return delay for being subscribed and not being added to any project
        $timestamp = $date->getTimestamp();
        $select = 'SELECT NULL from user where user_id = ? and add_date < ? ';
        return $this->getDB()->run($select, $user_id, $timestamp);
    }

    /**
     * Suspend account of user who is no more member of any project
     * @param DateTimeImmutable $date
     *
     * @return void
     */
    public function suspendUserNotProjectMembers(DateTimeImmutable $date) : void
    {
        $timestamp = $date->getTimestamp();
        $logger = $this->userSuspensionLogger;
        $dar    = $this->returnNotProjectMembers();
        if ($dar) {
            //we should verify the delay for it user has been no more belonging to any project
            foreach ($dar as $row) {
                $user_id = (int)$row['user_id'];
                $logger->debug("Checking user #$user_id");
                //we split the treatment in two methods to distinguish between 0 row returned
                //by the fact that there is no "removed user" entry for this user_id and the case
                //where it is the result of comparing the date
                $res = $this->delayForBeingNotProjectMembers($user_id);
                if (count($res) == 0) {
                    $logger->debug("User #$user_id never project member");
                    //Verify add_date
                    $result = $this->delayForBeingSubscribed($user_id, $date);
                    if ($result) {
                        $this->suspendUser($user_id);
                    } else {
                        $logger->debug("User #$user_id not in delay, continue");
                        continue;
                    }
                } else {
                    //verify if delay has not expired yet
                    $rowLastRemove = $res[0];
                    if ($rowLastRemove['date'] > $timestamp) {
                        $logger->debug("User #$user_id not in delay, continue");
                        continue;
                    } else {
                        $this->suspendUser($user_id);
                    }
                }
            }
        }
        return;
    }

    private function suspendUser(int $user_id)
    {
        $logger = $this->userSuspensionLogger;
        $logger->debug("User #$user_id will be suspended");
        $this->suspendAccount($user_id);

        if (! $this->verifySuspension($user_id)) {
            $logger->error("Error while suspending user #$user_id");
        }
    }

    private function verifySuspension(int $user_id)
    {
        $sql = "SELECT user.status FROM user WHERE user.user_id = ? ";
        $res = $this->getDB()->run($sql, $user_id);
        if ($res[0]['status'] == 'S') {
            return true;
        } else {
            return false;
        }
    }
}
