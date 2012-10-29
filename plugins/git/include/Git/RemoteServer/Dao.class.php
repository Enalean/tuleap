<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Git_RemoteServer_Dao extends DataAccessObject {

    public function searchById($id) {
        $id = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM plugin_git_remote_servers
                WHERE id = $id";
        return $this->retrieve($sql);
    }

    public function searchAll() {
        $sql = "SELECT * FROM plugin_git_remote_servers";
        return $this->retrieve($sql);
    }

    public function save($id, $host, $port, $login, $identity_file) {
        $id            = $this->da->escapeInt($id);
        $host          = $this->da->quoteSmart($host);
        $port          = $this->da->escapeInt($port);
        $login         = $this->da->quoteSmart($login);
        $identity_file = $this->da->quoteSmart($identity_file);
        $sql = "REPLACE INTO plugin_git_remote_servers (id, host, port, login, identity_file)
                VALUES ($id, $host, $port, $login, $identity_file)";
        return $this->update($sql);
    }

    public function delete($id) {
        $id  = $this->da->escapeInt($id);
        $sql = "DELETE FROM plugin_git_remote_servers
                where ID = $id";
        return $this->update($sql);
    }
}
?>
