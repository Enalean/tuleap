<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

class Git_RemoteServer_Dao extends \Tuleap\DB\DataAccessObject
{

    /**
     * @var StandardPasswordHandler
     */
    private $password_handler;

    public function __construct()
    {
        parent::__construct();
        $this->password_handler = PasswordHandlerFactory::getPasswordHandler();
    }

    public function getById($id)
    {
        $sql = 'SELECT *
                FROM plugin_git_remote_servers
                WHERE id = ?';

        return $this->getDB()->row($sql, $id);
    }

    public function searchAll()
    {
        return $this->getDB()->run('SELECT * FROM plugin_git_remote_servers');
    }

    public function searchAllUnrestricted()
    {
        $sql = 'SELECT plugin_git_remote_servers.*
                FROM plugin_git_remote_servers
                  LEFT JOIN plugin_git_restricted_gerrit_servers
                    ON (plugin_git_remote_servers.id = plugin_git_restricted_gerrit_servers.gerrit_server_id)
                WHERE gerrit_server_id IS NULL';

        return $this->getDB()->run($sql);
    }

    public function searchAllServersWithSSHKey()
    {
        return $this->getDB()->run('SELECT * FROM plugin_git_remote_servers WHERE ssh_key IS NOT NULL AND ssh_key != ""');
    }

    public function searchAvailableServersForProject($project_id)
    {
        $sql = 'SELECT plugin_git_remote_servers.*
                FROM plugin_git_remote_servers
                  INNER JOIN plugin_git_restricted_gerrit_servers
                    ON (plugin_git_remote_servers.id = plugin_git_restricted_gerrit_servers.gerrit_server_id)
                  INNER JOIN plugin_git_restricted_gerrit_servers_allowed_projects
                    USING (gerrit_server_id)
                WHERE plugin_git_restricted_gerrit_servers_allowed_projects.project_id = ?

                UNION

                SELECT plugin_git_remote_servers.*
                FROM plugin_git_remote_servers
                  LEFT JOIN plugin_git_restricted_gerrit_servers
                    ON (plugin_git_remote_servers.id = plugin_git_restricted_gerrit_servers.gerrit_server_id)
                WHERE gerrit_server_id IS NULL';

        return $this->getDB()->run($sql, $project_id);
    }

    /**
     * This sql request returns for a given project all the servers
     * where its git repositories are migrated
     */
    public function searchAllByProjectId($project_id)
    {
        $sql = 'SELECT plugin_git_remote_servers.*
                FROM plugin_git_remote_servers INNER JOIN plugin_git
                    ON (plugin_git_remote_servers.id = plugin_git.remote_server_id
                        AND project_id = ?)';
        return $this->getDB()->run($sql, $project_id);
    }

    public function searchAllRemoteServersForUserId($user_id)
    {
        $sql = "SELECT DISTINCT pgrs.*
                FROM plugin_git_remote_servers pgrs
                    INNER JOIN plugin_git ON (remote_server_id = pgrs.id)
                    INNER JOIN user_group ON (user_group.group_id = plugin_git.project_id)
                    INNER JOIN user ON (user_group.user_id = user.user_id)
                WHERE user_group.user_id = ?
                    AND user.ldap_id IS NOT NULL
                    AND user.ldap_id != ''
                UNION
                SELECT DISTINCT pgrs.*
                FROM plugin_git_remote_servers pgrs
                    INNER JOIN plugin_git ON (remote_server_id = pgrs.id)
                    INNER JOIN ugroup ON (ugroup.group_id = plugin_git.project_id)
                    INNER JOIN ugroup_user ON (ugroup_user.ugroup_id = ugroup.ugroup_id)
                    INNER JOIN user ON (ugroup_user.user_id = user.user_id)
                WHERE ugroup_user.user_id = ?
                    AND user.ldap_id IS NOT NULL
                    AND user.ldap_id != ''";
        return $this->getDB()->run($sql, $user_id, $user_id);
    }

    public function searchAllByUGroupId($project_id, $ugroup_id)
    {
        $sql = 'SELECT plugin_git_remote_servers.*
                FROM plugin_git_remote_servers INNER JOIN plugin_git_remote_ugroups
                    ON (plugin_git_remote_servers.id = plugin_git_remote_ugroups.remote_server_id
                        AND group_id = ? AND ugroup_id = ?)';

        return $this->getDB()->run($sql, $project_id, $ugroup_id);
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
        if ($id == 0) {
            $this->getDB()->insert(
                'plugin_git_remote_servers',
                [
                    'host'                 => $host,
                    'ssh_port'             => $ssh_port,
                    'http_port'            => $http_port,
                    'login'                => $login,
                    'identity_file'        => $identity_file,
                    'ssh_key'              => $replication_key,
                    'use_ssl'              => $use_ssl,
                    'gerrit_version'       => $gerrit_version,
                    'http_password'        => $http_password,
                    'replication_password' => '',
                    'auth_type'            => $auth_type
                ]
            );
        } else {
            $this->getDB()->update(
                'plugin_git_remote_servers',
                [
                    'host'           => $host,
                    'ssh_port'       => $ssh_port,
                    'http_port'      => $http_port,
                    'login'          => $login,
                    'identity_file'  => $identity_file,
                    'ssh_key'        => $replication_key,
                    'use_ssl'        => $use_ssl,
                    'gerrit_version' => $gerrit_version,
                    'http_password'  => $http_password,
                    'auth_type'      => $auth_type
                ],
                ['id' => $id]
            );
        }

        return $this->getDB()->lastInsertId();
    }

    public function delete($id)
    {
        $this->getDB()->run('DELETE FROM plugin_git_remote_servers WHERE id = ?', $id);
    }

    public function updateReplicationPassword($id, $replication_password)
    {
        $password_hashed_replication_password = $this->password_handler->computeHashPassword($replication_password);

        $sql = 'UPDATE plugin_git_remote_servers
                SET replication_password = ?
                WHERE id = ?';

        $this->getDB()->run($sql, $password_hashed_replication_password, $id);
    }
}
