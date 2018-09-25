<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\User\Password\Reset;

class DataAccessObject extends \DataAccessObject
{
    /**
     * @return int|false
     */
    public function create($user_id, $verifier, $current_time)
    {
        $user_id      = $this->da->escapeInt($user_id);
        $verifier     = $this->da->quoteSmart($verifier);
        $current_time = $this->da->escapeInt($current_time);

        $sql = "INSERT INTO user_lost_password(user_id, verifier, creation_date) VALUES($user_id, $verifier, $current_time)";

        return $this->updateAndGetLastId($sql);
    }

    /**
     * @return array|false
     */
    public function getTokenInformationById($id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "SELECT * FROM user_lost_password WHERE id = $id";

        return $this->retrieveFirstRow($sql);
    }

    /**
     * @return bool
     */
    public function deleteTokensByUserId($user_id)
    {
        $user_id = $this->da->escapeInt($user_id);

        $sql = "DELETE FROM user_lost_password WHERE user_id = $user_id";

        return $this->update($sql);
    }
}
