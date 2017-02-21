<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

    /**
     * @var StandardPasswordHandler
     */
    private $password_handler;

    public function __construct(DataAccess $da = null)
    {
        parent::__construct($da);
        $this->password_handler = PasswordHandlerFactory::getPasswordHandler();
    }

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

    public function searchAllUnrestricted()
    {
        $sql = "SELECT plugin_git_remote_servers.*
                FROM plugin_git_remote_servers
                  LEFT JOIN plugin_git_restricted_gerrit_servers
                    ON (plugin_git_remote_servers.id = plugin_git_restricted_gerrit_servers.gerrit_server_id)
                WHERE gerrit_server_id IS NULL";

        return $this->retrieve($sql);
    }

    public function searchAllServersWithSSHKey()
    {
        $sql = 'SELECT * FROM plugin_git_remote_servers WHERE ssh_key IS NOT NULL OR ssh_key != ""';
        return $this->retrieve($sql);
    }

    public function searchAvailableServersForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT plugin_git_remote_servers.*
                FROM plugin_git_remote_servers
                  INNER JOIN plugin_git_restricted_gerrit_servers
                    ON (plugin_git_remote_servers.id = plugin_git_restricted_gerrit_servers.gerrit_server_id)
                  INNER JOIN plugin_git_restricted_gerrit_servers_allowed_projects
                    USING (gerrit_server_id)
                WHERE plugin_git_restricted_gerrit_servers_allowed_projects.project_id = $project_id

                UNION

                SELECT plugin_git_remote_servers.*
                FROM plugin_git_remote_servers
                  LEFT JOIN plugin_git_restricted_gerrit_servers
                    ON (plugin_git_remote_servers.id = plugin_git_restricted_gerrit_servers.gerrit_server_id)
                WHERE gerrit_server_id IS NULL";

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

    public function searchAllRemoteServersForUserId($user_id) {
        $sql = "SELECT DISTINCT pgrs.*
                FROM plugin_git_remote_servers pgrs
                    INNER JOIN plugin_git ON (remote_server_id = pgrs.id)
                    INNER JOIN user_group ON (user_group.group_id = plugin_git.project_id)
                    INNER JOIN user ON (user_group.user_id = user.user_id)
                WHERE user_group.user_id = $user_id
                    AND user.ldap_id IS NOT NULL
                    AND user.ldap_id != ''
                UNION
                SELECT DISTINCT pgrs.*
                FROM plugin_git_remote_servers pgrs
                    INNER JOIN plugin_git ON (remote_server_id = pgrs.id)
                    INNER JOIN ugroup ON (ugroup.group_id = plugin_git.project_id)
                    INNER JOIN ugroup_user ON (ugroup_user.ugroup_id = ugroup.ugroup_id)
                    INNER JOIN user ON (ugroup_user.user_id = user.user_id)
                WHERE ugroup_user.user_id = $user_id
                    AND user.ldap_id IS NOT NULL
                    AND user.ldap_id != ''";
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

    public function save(
        $id,
        $host,
        $ssh_port,
        $http_port,
        $login,
        $identity_file,
        $replication_key,
        $use_ssl,
        $gerrit_version,
        $http_password,
        $auth_type
    ) {
        $id                   = $this->da->escapeInt($id);
        $host                 = $this->da->quoteSmart($host);
        $ssh_port             = $this->da->escapeInt($ssh_port);
        $http_port            = $this->da->escapeInt($http_port, CODENDI_DB_NULL);
        $login                = $this->da->quoteSmart($login);
        $identity_file        = $this->da->quoteSmart($identity_file);
        $replication_key      = $this->da->quoteSmart($replication_key);
        $use_ssl              = $this->da->escapeInt($use_ssl);
        $gerrit_version       = $this->da->quoteSmart($gerrit_version);
        $http_password        = $this->da->quoteSmart($http_password);
        $auth_type            = $this->da->quoteSmart($auth_type);

        if ($id == 0) {
            $sql = "INSERT INTO plugin_git_remote_servers (
                id,
                host,
                ssh_port,
                http_port,
                login,
                identity_file,
                ssh_key,
                use_ssl,
                gerrit_version,
                http_password,
                replication_password,
                auth_type
            )
            VALUES (
                $id,
                $host,
                $ssh_port,
                $http_port,
                $login,
                $identity_file,
                $replication_key,
                $use_ssl,
                $gerrit_version,
                $http_password,
                '',
                $auth_type
            )";
        } else {
            $sql = "REPLACE INTO plugin_git_remote_servers (
                id,
                host,
                ssh_port,
                http_port,
                login,
                identity_file,
                ssh_key,
                use_ssl,
                gerrit_version,
                http_password,
                replication_password,
                auth_type
            )
            SELECT
                $id,
                $host,
                $ssh_port,
                $http_port,
                $login,
                $identity_file,
                $replication_key,
                $use_ssl,
                $gerrit_version,
                $http_password,
                replication_password,
                $auth_type
            FROM plugin_git_remote_servers
            WHERE id = $id";
        }

        return $this->updateAndGetLastId($sql);
    }

    public function delete($id) {
        $id  = $this->da->escapeInt($id);
        $sql = "DELETE FROM plugin_git_remote_servers
                where ID = $id";
        return $this->update($sql);
    }

    public function updateReplicationPassword($id, $replication_password)
    {
        $id                   = $this->da->escapeInt($id);
        $replication_password = $this->da->quoteSmart(
            $this->password_handler->computeHashPassword($replication_password)
        );

        $sql = "UPDATE plugin_git_remote_servers
                SET replication_password = $replication_password
                WHERE id = $id";

        return $this->update($sql);
    }
}
