<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Rest_TokenDao extends DataAccessObject
{

    public function addTokenForUserId($user_id, $token, $current_timestamp)
    {
        $current_timestamp = $this->da->escapeInt($current_timestamp);
        $user_id           = $this->da->escapeInt($user_id);
        $token             = $this->da->quoteSmart($token);

        $sql = "INSERT INTO rest_authentication_token (token, user_id, created_on)
                VALUES ($token, $user_id, $current_timestamp)";

        return $this->update($sql);
    }

    public function deleteToken($token)
    {
        $token = $this->da->quoteSmart($token);

        $sql = "DELETE FROM rest_authentication_token
                WHERE token = $token";

        return $this->update($sql);
    }


    public function deleteTokensOlderThan($date_timestamp)
    {
        $date_timestamp = $this->da->escapeInt($date_timestamp);

        $sql = "DELETE FROM rest_authentication_token
                WHERE created_on < $date_timestamp";

        return $this->update($sql);
    }

    public function deleteAllTokensForUser($user_id)
    {
        $user_id = $this->da->escapeInt($user_id);

        $sql = "DELETE FROM rest_authentication_token
                WHERE user_id = $user_id";

        return $this->update($sql);
    }

    public function getTokensForUserId($user_id)
    {
        $user_id = $this->da->escapeInt($user_id);

        $sql = "SELECT token, user_id
                FROM rest_authentication_token
                WHERE user_id = $user_id";

        return $this->retrieve($sql);
    }

    public function checkTokenExistenceForUserId($user_id, $token)
    {
        $user_id = $this->da->escapeInt($user_id);
        $token   = $this->da->quoteSmart($token);

        $sql = "SELECT NULL
                FROM rest_authentication_token
                WHERE user_id = $user_id
                  AND token = $token
                LIMIT 1";

        return $this->retrieve($sql);
    }
}
