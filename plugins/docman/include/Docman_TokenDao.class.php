<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


/**
 *  Data Access Object for Docman_Token
 */
class Docman_TokenDao extends DataAccessObject
{

    /**
    * Searches Docman_Token by Url
    * @return DataAccessResult
    */
    public function searchUrl($user_id, $token)
    {
        $sql = sprintf(
            "SELECT url FROM plugin_docman_tokens WHERE user_id = %s AND token = %s",
            $this->da->quoteSmart($user_id),
            $this->da->quoteSmart($token)
        );
        return $this->retrieve($sql);
    }

    /**
    * create a row in the table plugin_docman_tokens
    * @return true or id(auto_increment) if there is no error
    */
    public function create($user_id, $token, $url)
    {
        $sql = sprintf(
            "INSERT INTO plugin_docman_tokens (user_id, token, url, created_at) VALUES (%s, %s, %s, NOW())",
            $this->da->quoteSmart($user_id),
            $this->da->quoteSmart($token),
            $this->da->quoteSmart($url)
        );
        $inserted = $this->update($sql);

        return $inserted;
    }

    /**
    * delete a row in the table plugin_docman_tokens
    */
    public function delete($user_id, $token)
    {
        $sql = sprintf(
            "DELETE FROM plugin_docman_tokens WHERE (TO_DAYS(NOW()) - TO_DAYS(created_at)) > 1 OR (user_id = %s AND token = %s)",
            $this->da->quoteSmart($user_id),
            $this->da->quoteSmart($token)
        );
        $deleted = $this->update($sql);

        return $deleted;
    }
}
