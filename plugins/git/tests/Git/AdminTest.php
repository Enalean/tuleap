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

require_once dirname(__FILE__).'/../../include/constants.php';
require_once GIT_BASE_DIR.'/Git/Admin.class.php';

class Git_Admin_process_Test extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->request = aRequest()->build();
        $this->csrf    = mock('CSRFSynchronizerToken');
        $this->factory = mock('Git_RemoteServer_GerritServerFactory');
        $this->admin   = new Git_Admin($this->factory, $this->csrf);

        $this->request_new_server = array(
            'host'              => 'host',
            'port'              => '1234',
            'login'             => 'login',
            'identity_file'     => '/path/to/file',
            'replication_key'   => new Git_RemoteServer_Gerrit_ReplicationSSHKey(),
        );
        $this->request_update_existing_server = array(
            'host'              => 'g.example.com',
            'port'              => '1234',
            'login'             => 'new_login',
            'identity_file'     => '/path/to/file',
            'replication_key'   => new Git_RemoteServer_Gerrit_ReplicationSSHKey(),
        );
        $this->a_brand_new_server = new Git_RemoteServer_GerritServer(0, 'host', '1234', '80', 'login', '/path/to/file', new Git_RemoteServer_Gerrit_ReplicationSSHKey());
        $this->an_existing_server = new Git_RemoteServer_GerritServer(1, 'g.example.com', '1234', '80', 'login', '/path/to/file', new Git_RemoteServer_Gerrit_ReplicationSSHKey());

        stub($this->factory)->getServers()->returns(array(
            1 => $this->an_existing_server
        ));

        $this->request->set($this->csrf->getTokenName(), $this->csrf->getToken());
    }

    public function itDoesNotSaveAnythingIfTheRequestIsNotValid() {
        $this->request->set('gerrit_servers', false);
        expect($this->factory)->save()->never();
        $this->admin->process($this->request);
    }

    public function itChecksForForgedRequest() {
        $this->request->set('gerrit_servers', array(0 => $this->request_new_server));
        expect($this->csrf)->check()->once();
        $this->admin->process($this->request);
    }

    public function itSavesNewGerritServer() {
        $this->request->set('gerrit_servers', array(0 => $this->request_new_server));
        expect($this->factory)->save($this->a_brand_new_server)->once();
        $this->admin->process($this->request);
    }

    public function itUpdatesExistingGerritServer() {
        $this->request->set('gerrit_servers', array(1 => $this->request_update_existing_server));
        $expected = clone $this->an_existing_server;
        $expected->setLogin('new_login');
        expect($this->factory)->save($expected)->once();
        $this->admin->process($this->request);
    }

    public function itDeletesGerritServer() {
        $request_gerrit_servers = array(1 => $this->request_update_existing_server);
        $request_gerrit_servers[1]['delete'] = 1;
        $this->request->set('gerrit_servers', $request_gerrit_servers);
        expect($this->factory)->delete($this->an_existing_server)->once();
        expect($this->factory)->save($this->an_existing_server)->never();
        $this->admin->process($this->request);
    }
}
?>
