<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'common/dao/include/DataAccessObject.class.php';

class Openid_Dao extends DataAccessObject {

    public function searchUsersForConnexionString($connexion_string) {
        $connexion_string = $this->da->quoteSmart($connexion_string);

        $sql = "SELECT user.*
                FROM user
                JOIN plugin_openid_user_mapping
                    ON user.user_id=plugin_openid_user_mapping.user_id
                WHERE connexion_string=$connexion_string";

        return $this->retrieve($sql);
    }

    public function searchOpenidUrlsForUserId($user_id) {
        $user_id = $this->da->escapeInt($user_id);

        $sql = "SELECT connexion_string
                FROM plugin_openid_user_mapping
                WHERE user_id=$user_id";

        return $this->retrieve($sql);
    }

    public function addConnexionStringForUserId($connexion_string, $user_id) {
        $connexion_string = $this->da->quoteSmart($connexion_string);
        $user_id          = $this->da->escapeInt($user_id);

        $sql = "INSERT INTO plugin_openid_user_mapping (user_id, connexion_string)
                VALUES ($user_id, $connexion_string)";

        return $this->update($sql);
    }

    public function removeConnexionStringForUserId($connexion_string, $user_id) {
        $connexion_string = $this->da->quoteSmart($connexion_string);
        $user_id          = $this->da->escapeInt($user_id);

        $sql = "DELETE FROM plugin_openid_user_mapping
                WHERE user_id=$user_id
                AND connexion_string=$connexion_string";

        return $this->update($sql);
    }
}
