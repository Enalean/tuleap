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

class Git_RemoteServer_GerritServerFactory {

    /** @var Git_RemoteServer_Dao */
    private $dao;

    /** @var GitDao */
    private $git_dao;

    public function __construct(Git_RemoteServer_Dao $dao, GitDao $git_dao) {
        $this->dao     = $dao;
        $this->git_dao = $git_dao;
    }

    public function getServer(GitRepository $repository) {
        $id  = $repository->getRemoteServerId();
        return $this->getServerById($id);
    }

    public function getServerById($id) {
        $row = $this->dao->searchById($id)->getRow();
        if ($row) {
            return $this->instantiateFromRow($row);
        }
        throw new Git_RemoteServer_NotFoundException($id);
    }

    /**
     * @return array of Git_RemoteServer_GerritServer
     */
    public function getServers() {
        $servers = array();
        foreach ($this->dao->searchAll() as $row) {
            $servers[$row['id']] = $this->instantiateFromRow($row);
        }
        return $servers;
    }

    public function save(Git_RemoteServer_GerritServer $server) {
        $this->dao->save(
            $server->getId(),
            $server->getHost(),
            $server->getSSHPort(),
            $server->getHTTPPort(),
            $server->getLogin(),
            $server->getIdentityFile()
        );
    }

    public function delete(Git_RemoteServer_GerritServer $server) {
        if (! $this->isServerUsed($server)) {
            $this->dao->delete($server->getId());
        }
    }

    public function isServerUsed(Git_RemoteServer_GerritServer $server) {
        return $this->git_dao->isRemoteServerUsed($server->getId());
    }

    private function instantiateFromRow(array $row) {
        return new Git_RemoteServer_GerritServer($row['id'], $row['host'], $row['ssh_port'], $row['http_port'], $row['login'], $row['identity_file']);
    }

}
?>
