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

class Git_GitoliteHousekeeping_GitoliteHousekeepingDao extends DataAccessObject {

    /** @return bool */
    public function isGitGcEnabled() {
        $sql = "SELECT allow_git_gc FROM plugin_git_housekeeping";

        $result = $this->retrieve($sql)->getRow();

        return (bool)$result['allow_git_gc'];
    }

    public function enableGitGc() {
        $sql = "UPDATE plugin_git_housekeeping SET allow_git_gc = 1";

        return $this->update($sql);
    }
}
