<?php
/*
 * Copyright (c) Enalean, 2011-2018. All Rights Reserved.
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

class SessionDao extends DataAccessObject
{

    public function create($user_id, $token, $ip_address, $current_time)
    {
        $user_id      = $this->getDa()->escapeInt($user_id);
        $token        = $this->getDa()->quoteSmart($token);
        $ip_address   = $this->getDa()->quoteSmart($ip_address);
        $current_time = $this->getDa()->escapeInt($current_time);

        $sql = "INSERT INTO session(user_id, session_hash, ip_addr, time)
                VALUES($user_id, $token, $ip_address, $current_time)";

        return $this->updateAndGetLastId($sql);
    }

    public function searchById($id, $current_time, $session_lifetime)
    {
        $id               = $this->getDa()->escapeInt($id);
        $current_time     = $this->getDa()->escapeInt($current_time);
        $session_lifetime = $this->getDa()->escapeInt($session_lifetime);

        $sql = "SELECT * FROM session WHERE id = $id AND time + $session_lifetime > $current_time";
        return $this->retrieveFirstRow($sql);
    }

    /**
     * @return int the number of active sessions
     */
    public function count($current_time, $session_lifetime)
    {
        $current_time     = $this->da->escapeInt($current_time);
        $session_lifetime = $this->da->escapeInt($session_lifetime);

        $row = $this->retrieve(
            "SELECT count(*) AS nb FROM session WHERE time + $session_lifetime > $current_time"
        )->getRow();
        return $row['nb'];
    }

    public function deleteSessionById($id)
    {
        $id  = $this->getDa()->escapeInt($id);
        $sql = "DELETE FROM session WHERE id = $id";
        return $this->update($sql);
    }

    public function deleteSessionByUserId($user_id)
    {
        $user_id = $this->getDa()->escapeInt($user_id);
        $sql     = "DELETE FROM session WHERE user_id = $user_id";
        return $this->update($sql);
    }

    public function deleteAllSessionsByUserIdButTheCurrentOne($user_id, $current_session_id)
    {
        $user_id            = $this->getDa()->escapeInt($user_id);
        $current_session_id = $this->getDa()->escapeInt($current_session_id);
        $sql     = "DELETE FROM session WHERE user_id = $user_id AND id != $current_session_id";
        return $this->update($sql);
    }

    /**
     * Purge the table
     *
     * @return bool true if success, false otherwise
     */
    public function deleteAll()
    {
        return $this->update("TRUNCATE TABLE session");
    }
}
