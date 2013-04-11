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
require_once 'common/dao/include/DataAccessObject.class.php';
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

    /**
     * This sql request returns for a given project all the servers
     * where its git repositories are migrated
     */
    public function searchAllByProjectId($project_id) {
        $project_id = $this->da->escapeInt($project_id);
        $sql = "SELECT plugin_git_remote_servers.*
                FROM plugin_git_remote_servers INNER JOIN plugin_git
                    ON (plugin_git_remote_servers.id = plugin_git.remote_server_id
                        AND project_id = $project_id)";
        return $this->retrieve($sql);
    }

    public function searchAllByUGroupId($project_id, $ugroup_id) {
        $project_id = $this->da->escapeInt($project_id);
        $ugroup_id  = $this->da->escapeInt($ugroup_id);
        $sql = "SELECT plugin_git_remote_servers.*
                FROM plugin_git_remote_servers INNER JOIN plugin_git_remote_ugroups
                    ON (plugin_git_remote_servers.id = plugin_git_remote_ugroups.remote_server_id
                        AND group_id = $project_id AND ugroup_id = $ugroup_id)";
        return $this->retrieve($sql);
    }

    public function save($id, $host, $ssh_port, $http_port, $login, $identity_file, $replication_key) {
        $id              = $this->da->escapeInt($id);
        $host            = $this->da->quoteSmart($host);
        $ssh_port        = $this->da->escapeInt($ssh_port);
        $http_port       = $this->da->escapeInt($http_port);
        $login           = $this->da->quoteSmart($login);
        $identity_file   = $this->da->quoteSmart($identity_file);
        $replication_key = $this->da->quoteSmart($replication_key);
        $sql = "REPLACE INTO plugin_git_remote_servers (id, host, ssh_port, http_port, login, identity_file, ssh_key)
                VALUES ($id, $host, $ssh_port, $http_port, $login, $identity_file, $replication_key)";

        return $this->updateAndGetLastId($sql);
    }

    public function delete($id) {
        $id  = $this->da->escapeInt($id);
        $sql = "DELETE FROM plugin_git_remote_servers
                where ID = $id";
        return $this->update($sql);
    }
}
?>
