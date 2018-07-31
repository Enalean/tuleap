<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013, 2014. All rights reserved.
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

use Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector;

require_once dirname(__FILE__).'/../bootstrap.php';

class SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMPTest extends TuleapTestCase {
    /** @var SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP */
    private $event;
    /** @var Git_RemoteServer_GerritServerFactory */
    private $gerrit_server_factory;
    /** @var Git_Gitolite_SSHKeyDumper */
    private $ssh_key_dumper;

    public function setUp() {
        parent::setUp();
        $this->ssh_key_dumper = mock('Git_Gitolite_SSHKeyDumper');
        $this->gerrit_server_factory = mock('Git_RemoteServer_GerritServerFactory');
        $this->event = partial_mock('SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP', array('done', 'error'));
        $this->event->injectDependencies($this->gerrit_server_factory, $this->ssh_key_dumper);
    }

    public function itAddsKeyForAServer() {
        $gerrit_server_id = 7;
        $this->event->setParameters("$gerrit_server_id");

        expect($this->gerrit_server_factory)->getServerById($gerrit_server_id)->once();
        stub($this->gerrit_server_factory)->getServerById()->returns(mock('Git_RemoteServer_GerritServer'));

        $this->event->process();
    }

    public function itDumpsTheNewKeyForServer() {
        $gerrit_server_id = 7;
        $this->event->setParameters("$gerrit_server_id");

        $replication_key = 'ssh-rsa blablabla';
        $use_ssl         = false;
        $gerrit_version  = '2.5';
        $http_password   = 'ikshjdshg';
        $auth_type       = 'Digest';

        $gerrit_server = new Git_RemoteServer_GerritServer(
            $gerrit_server_id,
            '$host',
            '$ssh_port',
            '$http_port',
            '$login',
            '$identity_file',
            $replication_key,
            $use_ssl,
            $gerrit_version,
            $http_password,
            '',
            $auth_type
        );
        stub($this->gerrit_server_factory)->getServerById()->returns($gerrit_server);

        $key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $key->setGerritHostId($gerrit_server_id)->setValue($replication_key);
        expect($this->ssh_key_dumper)->dumpSSHKeys($key, new InvalidKeysCollector())->once();

        $this->event->process();
    }

    public function itDeleteCorrespondingKeyWhenNoServer() {
        $gerrit_server_id = 7;
        $this->event->setParameters("$gerrit_server_id");

        stub($this->gerrit_server_factory)->getServerById()->throws(new Git_RemoteServer_NotFoundException($gerrit_server_id));

        $key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $key->setGerritHostId($gerrit_server_id)->setValue('');
        expect($this->ssh_key_dumper)->dumpSSHKeys($key, new InvalidKeysCollector())->once();

        $this->event->process();
    }

    public function itMarkAsDoneWhenDumpWorks() {
        $this->event->setParameters("7");

        stub($this->gerrit_server_factory)->getServerById()->returns(mock('Git_RemoteServer_GerritServer'));
        stub($this->ssh_key_dumper)->dumpSSHKeys()->returns(true);

        expect($this->event)->done()->once();

        $this->event->process();
    }

    public function itMarkAsErrorWhenDumpDoesntWork() {
        $this->event->setParameters("7");

        stub($this->gerrit_server_factory)->getServerById()->returns(mock('Git_RemoteServer_GerritServer'));
        stub($this->ssh_key_dumper)->dumpSSHKeys()->returns(false);

        expect($this->event)->error()->once();

        $this->event->process();
    }
}