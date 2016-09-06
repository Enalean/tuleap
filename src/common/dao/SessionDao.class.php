<?php
/*
 * Copyright (c) Enalean, 2011-2016. All Rights Reserved.
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

class SessionDao extends DataAccessObject {
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

    /**
     * Purge the table
     *
     * @return bool true if success, false otherwise
     */
    public function deleteAll() {
        return $this->update("TRUNCATE TABLE session");
    }
}
