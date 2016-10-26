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
        $this->request             = aRequest()->build();
        $this->csrf                = mock('CSRFSynchronizerToken');
        $this->factory             = mock('Git_RemoteServer_GerritServerFactory');
        $this->admin_page_renderer = mock('Tuleap\Admin\AdminPageRenderer');
        $this->admin               = new Git_AdminGerritController(
            $this->csrf,
            $this->factory,
            $this->admin_page_renderer
        );

        $this->request_update_existing_server = array(
            'host'                 => 'g.example.com',
            'ssh_port'             => '1234',
            'http_port'            => '80',
            'login'                => 'new_login',
            'identity_file'        => '/path/to/file',
            'replication_key'      => 'replication_key',
            'use_ssl'              => 0,
            'gerrit_version'       => '2.5',
            'http_password'        => 'azerty',
            'replication_password' => 'replication_password',
            'auth_type'            => 'Digest'
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
            0,
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
        $this->request->set('action', 'add-gerrit-server');
    }

    public function itDoesNotSaveAnythingIfTheRequestIsNotValid() {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('server', false);
        expect($this->factory)->save()->never();
        $this->admin->process($this->request);
    }

    public function itDoesNotSaveAServerIfNoDataIsGiven() {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('host', '');
        $this->request->set('ssh_port', '');
        $this->request->set('http_port', '');
        $this->request->set('login', '');
        $this->request->set('identity_file', '');
        $this->request->set('replication_key', '');
        $this->request->set('use_ssl', '');
        $this->request->set('gerrit_version', '2.5');
        $this->request->set('http_password', '');
        $this->request->set('replication_password', '');
        $this->request->set('auth_type', 'Digest');
        expect($this->factory)->save()->never();
        $this->admin->process($this->request);
    }

    public function itDoesNotSaveAServerIfItsHostIsEmpty() {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('host', '');
        $this->request->set('ssh_port', '1234');
        $this->request->set('http_port', '80');
        $this->request->set('login', 'new_login');
        $this->request->set('identity_file', '/path/to/file');
        $this->request->set('replication_key', '');
        $this->request->set('use_ssl', 0);
        $this->request->set('gerrit_version', '2.5');
        $this->request->set('http_password', 'azerty');
        $this->request->set('replication_password', 'azerty');
        $this->request->set('auth_type', 'Digest');
        expect($this->factory)->save()->never();
        $this->admin->process($this->request);
    }

    public function itNotSavesAServerIfItsHostIsNotEmptyAndAllOtherDataAreEmpty() {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('host', 'awesome_host');
        $this->request->set('ssh_port', '');
        $this->request->set('http_port', '');
        $this->request->set('login', '');
        $this->request->set('identity_file', '');
        $this->request->set('replication_key', '');
        $this->request->set('use_ssl', '');
        $this->request->set('gerrit_version', '');
        $this->request->set('http_password', '');
        $this->request->set('replication_password', '');
        $this->request->set('auth_type', '');
        expect($this->factory)->save()->never();
        $this->admin->process($this->request);
    }

    public function itCheckWithCSRFIfTheRequestIsForged() {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('host', 'host');
        $this->request->set('ssh_port', '1234');
        $this->request->set('http_port', '80');
        $this->request->set('login', 'new_login');
        $this->request->set('identity_file', '/path/to/file');
        $this->request->set('replication_key', 'replication_key');
        $this->request->set('use_ssl', 1);
        $this->request->set('gerrit_version', '2.5');
        $this->request->set('http_password', 'azerty');
        $this->request->set('replication_password', 'azerty');
        $this->request->set('auth_type', 'Digest');
        expect($this->csrf)->check()->once();
        $this->admin->process($this->request);
    }

    public function itSavesNewGerritServer() {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('host', 'host');
        $this->request->set('ssh_port', '1234');
        $this->request->set('http_port', '80');
        $this->request->set('login', 'new_login');
        $this->request->set('identity_file', '/path/to/file');
        $this->request->set('replication_key', 'replication_key');
        $this->request->set('use_ssl', 1);
        $this->request->set('gerrit_version', '2.5');
        $this->request->set('http_password', 'azerty');
        $this->request->set('replication_password', 'azerty');
        $this->request->set('auth_type', 'Digest');
        expect($this->factory)->save($this->a_brand_new_server)->once();
        $this->admin->process($this->request);
    }

    public function itRedirectsAfterSave() {
        $this->request->set('action', 'add-gerrit-server');
        expect($GLOBALS['Response'])->redirect()->once();
        $this->admin->process($this->request);
    }

    public function itUpdatesExistingGerritServer() {
        $this->request->set('action', 'gerrit-servers');
        $this->request->set('gerrit_servers', array(1 => $this->request_update_existing_server));
        $expected = clone $this->an_existing_server;
        $expected->setLogin('new_login');
        expect($this->factory)->save($expected)->once();
        $this->admin->process($this->request);
    }

    public function itDeletesGerritServer() {
        $request_gerrit_servers = array(1 => $this->request_update_existing_server);
        $request_gerrit_servers[1]['delete'] = 1;
        $this->request->set('action', 'gerrit-servers');
        $this->request->set('gerrit_servers', $request_gerrit_servers);
        expect($this->factory)->delete($this->an_existing_server)->once();
        expect($this->factory)->save($this->an_existing_server)->never();
        $this->admin->process($this->request);
    }
}
