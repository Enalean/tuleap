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

require_once 'GerritServer.class.php';
require_once 'NotFoundException.class.php';
require_once 'Dao.class.php';
require_once GIT_BASE_DIR .'/GitRepository.class.php';
require_once GIT_BASE_DIR .'/Git/RemoteServer/Gerrit/ReplicationSSHKeyFactory.class.php';

class Git_RemoteServer_GerritServerFactory {

    /** @var Git_RemoteServer_Dao */
    private $dao;

    /** @var GitDao */
    private $git_dao;

    /** @var Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory */
    private $replication_key_factory;

    /**
     *
     * @param Git_RemoteServer_Dao $dao
     * @param GitDao $git_dao
     * @param Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory $replication_key_factory
     */
    public function __construct(Git_RemoteServer_Dao $dao, GitDao $git_dao, Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory $replication_key_factory) {
        $this->dao     = $dao;
        $this->git_dao = $git_dao;
        $this->replication_key_factory = $replication_key_factory;
    }

    /**
     *
     * @param GitRepository $repository
     * @return Git_RemoteServer_GerritServer
     */
    public function getServer(GitRepository $repository) {
        $id  = $repository->getRemoteServerId();
        return $this->getServerById($id);
    }

    /**
     *
     * @param int $id
     * @return Git_RemoteServer_GerritServer
     * @throws Git_RemoteServer_NotFoundException
     */
    public function getServerById($id) {
        $row = $this->dao->searchById($id)->getRow();
        if ($row) {
            return $this->instantiateFromRow($row);
        }
        throw new Git_RemoteServer_NotFoundException($id);
    }

    /**
     * @return Git_RemoteServer_GerritServer[]
     */
    public function getServers() {
        $servers = array();
        foreach ($this->dao->searchAll() as $row) {
            $servers[$row['id']] = $this->instantiateFromRow($row);
        }
        return $servers;
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $server
     */
    public function save(Git_RemoteServer_GerritServer $server) {
        $this->dao->save(
            $server->getId(),
            $server->getHost(),
            $server->getSSHPort(),
            $server->getHTTPPort(),
            $server->getLogin(),
            $server->getIdentityFile()
        );
        
        $this->replication_key_factory->save($server->getReplicationKey());
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $server
     */
    public function delete(Git_RemoteServer_GerritServer $server) {
        if (! $this->isServerUsed($server)) {
            $this->dao->delete($server->getId());
        }
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $server
     * @return bool
     */
    public function isServerUsed(Git_RemoteServer_GerritServer $server) {
        return $this->git_dao->isRemoteServerUsed($server->getId());
    }

    /**
     *
     * @param array $row
     * @return \Git_RemoteServer_GerritServer
     */
    private function instantiateFromRow(array $row) {
        $replictaion_key_value = $this->replication_key_factory->fetchForGerritServerId($row['id']);

        return new Git_RemoteServer_GerritServer(
            $row['id'],
            $row['host'],
            $row['ssh_port'],
            $row['http_port'],
            $row['login'],
            $row['identity_file'],
            $replictaion_key_value
        );
    }

}
?>
