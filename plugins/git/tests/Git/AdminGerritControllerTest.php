<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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

require_once dirname(__FILE__).'/../bootstrap.php';

class Git_Admin_process_Test extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->request = aRequest()->build();
        $this->csrf    = mock('CSRFSynchronizerToken');
        $this->factory = mock('Git_RemoteServer_GerritServerFactory');
        $this->admin   = new Git_AdminGerritController($this->csrf, $this->factory);

        $this->request_new_server = array(
            'host'                 => 'host',
            'port'                 => '1234',
            'login'                => 'login',
            'identity_file'        => '/path/to/file',
            'replication_key'      => '',
            'use_ssl'              => 0,
            'gerrit_version'       => '2.5',
            'http_password'        => 'azerty',
            'replication_password' => ''
        );

        $this->request_update_existing_server = array(
            'host'                 => 'g.example.com',
            'port'                 => '1234',
            'login'                => 'new_login',
            'identity_file'        => '/path/to/file',
            'replication_key'      => '',
            'use_ssl'              => 0,
            'gerrit_version'       => '2.5',
            'http_password'        => 'azerty',
            'replication_password' => ''
        );

        $this->request_new_server_with_no_data = array(
            'host'                 => '',
            'port'                 => '',
            'login'                => '',
            'identity_file'        => '',
            'replication_key'      => '',
            'use_ssl'              => '',
            'gerrit_version'       => '2.5',
            'http_password'        => '',
            'replication_password' => ''
        );

        $this->request_update_existing_server_with_host_and_empty_data = array(
            'host'                 => 'awesome_host',
            'port'                 => '',
            'login'                => '',
            'identity_file'        => '',
            'replication_key'      => '',
            'use_ssl'              => '',
            'gerrit_version'       => '',
            'http_password'        => '',
            'replication_password' => ''
        );

        $this->request_update_existing_server_with_empty_host = array(
            'host'                 => '',
            'port'                 => '1234',
            'login'                => 'new_login',
            'identity_file'        => '/path/to/file',
            'replication_key'      => '',
            'use_ssl'              => 0,
            'gerrit_version'       => '2.5',
            'http_password'        => 'azerty',
            'replication_password' => ''
        );

        $this->a_brand_new_server = new Git_RemoteServer_GerritServer(
            0,
            'host',
            '1234',
            '80',
            'login',
            '/path/to/file',
            '',
            0,
            '2.5',
            'azerty',
            '',
            ''
        );

        $this->an_existing_server = new Git_RemoteServer_GerritServer(
            1,
            'g.example.com',
            '1234',
            '80',
            'login',
            '/path/to/file',
            '',
            0,
            '2.5',
            'azerty',
            '',
            ''
        );

        stub($this->factory)->getServers()->returns(array(
            1 => $this->an_existing_server
        ));

        $this->request->set($this->csrf->getTokenName(), $this->csrf->getToken());
        $this->request->set('action', 'gerrit-servers');
    }

    public function itDoesNotSaveAnythingIfTheRequestIsNotValid() {
        $this->request->set('gerrit_servers', false);
        expect($this->factory)->save()->never();
        $this->admin->process($this->request);
    }

    public function itDoesNotSaveAServerIfNoDataIsGiven() {
        $this->request->set('gerrit_servers', array(1 => $this->request_new_server_with_no_data));
        expect($this->factory)->save()->never();
        $this->admin->process($this->request);
    }

    public function itDoesNotSaveAServerIfItsHostIsEmpty() {
        $this->request->set('gerrit_servers', array(1 => $this->request_update_existing_server_with_empty_host));
        expect($this->factory)->save()->never();
        $this->admin->process($this->request);
    }

    public function itSavesAServerIfItsHostIsNotEmptyAndAllOtherDataAreEmpty() {
        $this->request->set('gerrit_servers', array(1 => $this->request_update_existing_server_with_host_and_empty_data));
        expect($this->factory)->save()->once();
        $this->admin->process($this->request);
    }

    public function itCheckWithCSRFIfTheRequestIsForged() {
        $this->request->set('gerrit_servers', array(0 => $this->request_new_server));
        expect($this->csrf)->check()->once();
        $this->admin->process($this->request);
    }

    public function itSavesNewGerritServer() {
        $this->request->set('gerrit_servers', array(0 => $this->request_new_server));
        expect($this->factory)->save($this->a_brand_new_server)->once();
        $this->admin->process($this->request);
    }

    public function itRedirectsAfterSave() {
        $this->request->set('gerrit_servers', array(0 => $this->request_new_server));
        expect($GLOBALS['Response'])->redirect()->once();
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
