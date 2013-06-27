<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP extends SystemEvent {
    const NAME = 'GIT_GERRIT_ADMIN_KEY_DUMP';

    /** @var Git_RemoteServer_GerritServerFactory */
    private $gerrit_server_factory;
    /** @var Git_Gitolite_SSHKeyDumper */
    private $ssh_key_dumper;

    public function injectDependencies(Git_RemoteServer_GerritServerFactory $gerrit_server_factory, Git_Gitolite_SSHKeyDumper $ssh_key_dumper) {
        $this->gerrit_server_factory = $gerrit_server_factory;
        $this->ssh_key_dumper        = $ssh_key_dumper;
    }

    private function getServerId() {
        return intval($this->getParameter(0));
    }

    private function getServer() {
        try {
            return $this->gerrit_server_factory->getServerById($this->getServerId());
        } catch (Git_RemoteServer_NotFoundException $e) {
            return new Git_RemoteServer_GerritServer($this->getServerId(), '',  '', '', '',  '', '');
        }
    }

    public function process() {
        $server = $this->getServer();
        $replication_key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $replication_key
            ->setGerritHostId($server->getId())
            ->setValue($server->getReplicationKey());
        if ($this->ssh_key_dumper->dumpSSHKeys($replication_key)) {
            $this->done();
        } else {
            $this->error('Impossible to dump replication ssh key for Gerrit server '.$server->getId());
        }
    }

    public function verbalizeParameters($with_link) {
        if ($with_link) {
            $server = $this->getServer();
            return 'Update SSH replication key of gerrit server '.$server->getHost().' (Id: '.$server->getId().')';
        } else {
            return $this->getServerId();
        }
    }
}

?>
