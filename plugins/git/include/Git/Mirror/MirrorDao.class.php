<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class Git_Mirror_MirrorDao extends DataAccessObject{

    /**
     * @return int | false
     */
    public function save($url, $ssh_key) {
        $url      = $this->da->quoteSmart($url);
        $ssh_key  = $this->da->quoteSmart($ssh_key);

        $sql = "INSERT INTO plugin_git_mirrors (url, ssh_key)
                VALUES($url, $ssh_key)";

        return $this->updateAndGetLastId($sql);
    }

    /**
     * @return DataAccessObject
     */
    public function fetchAll() {
        $sql = "SELECT * FROM plugin_git_mirrors";

        return $this->retrieve($sql);
    }
}
