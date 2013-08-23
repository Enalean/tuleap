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

require_once dirname(__FILE__).'/../../bootstrap.php';

class Git_RemoteServer_GerritServerTest extends TuleapTestCase {

    public function itDoesNotNeedToCustomizeSSHConfigOfCodendiadmOrRoot() {
        $id                 = 1;
        $host               = 'le_host';
        $http_port          = 'le_http_port';
        $ssh_port           = 'le_ssh_port';
        $login              = 'le_login';
        $identity_file      = 'le_identity_file';
        $replication_key    = '';
        $use_ssl            = false;

        $server = new Git_RemoteServer_GerritServer($id, $host, $ssh_port, $http_port, $login, $identity_file, $replication_key, $use_ssl);

        $expected = 'ext::ssh -p le_ssh_port -i le_identity_file le_login@le_host %S le_project';
        $this->assertEqual($expected, $server->getCloneSSHUrl("le_project"));
    }

    public function itPrunesDefaultHTTPPortForAdminUrl() {
        $id                 = 1;
        $host               = 'le_host';
        $http_port          = '80';
        $ssh_port           = 'le_ssh_port';
        $login              = 'le_login';
        $identity_file      = 'le_identity_file';
        $replication_key    = '';
        $use_ssl            = false;

        $server = new Git_RemoteServer_GerritServer($id, $host, $ssh_port, $http_port, $login, $identity_file, $replication_key, $use_ssl);

        $this->assertEqual($server->getProjectAdminUrl('gerrit_project_name'), 'http://le_host/#/admin/projects/gerrit_project_name');
    }

    public function itUseTheCustomHTTPPortForAdminUrl() {
        $id                 = 1;
        $host               = 'le_host';
        $http_port          = '8080';
        $ssh_port           = 'le_ssh_port';
        $login              = 'le_login';
        $identity_file      = 'le_identity_file';
        $replication_key    = '';
        $use_ssl            = false;

        $server = new Git_RemoteServer_GerritServer($id, $host, $ssh_port, $http_port, $login, $identity_file, $replication_key, $use_ssl);

        $this->assertEqual($server->getProjectAdminUrl('gerrit_project_name'), 'http://le_host:8080/#/admin/projects/gerrit_project_name');
    }

    public function itGivesTheUrlToProjectRequests() {
        $id                 = 1;
        $host               = 'le_host';
        $http_port          = '8080';
        $ssh_port           = 'le_ssh_port';
        $login              = 'le_login';
        $identity_file      = 'le_identity_file';
        $replication_key    = '';
        $use_ssl            = false;

        $server = new Git_RemoteServer_GerritServer($id, $host, $ssh_port, $http_port, $login, $identity_file, $replication_key, $use_ssl);

        $this->assertEqual($server->getProjectUrl('gerrit_project_name'), 'http://le_host:8080/#/q/project:gerrit_project_name,n,z');
    }

     public function itGivesTheUrlWithHTTPSToProjectRequestsIfWeUseSSL() {
        $id                 = 1;
        $host               = 'le_host';
        $http_port          = '8080';
        $ssh_port           = 'le_ssh_port';
        $login              = 'le_login';
        $identity_file      = 'le_identity_file';
        $replication_key    = '';
        $use_ssl            = true;

        $server = new Git_RemoteServer_GerritServer($id, $host, $ssh_port, $http_port, $login, $identity_file, $replication_key, $use_ssl);

        $this->assertEqual($server->getProjectUrl('gerrit_project_name'), 'https://le_host:8080/#/q/project:gerrit_project_name,n,z');
    }

    public function itGivesTheReplicationKeyToProjectRequests() {
        $id                 = 1;
        $host               = 'le_host';
        $http_port          = '8080';
        $ssh_port           = 'le_ssh_port';
        $login              = 'le_login';
        $identity_file      = 'le_identity_file';
        $replication_key    = '';
        $use_ssl            = false;

        $server = new Git_RemoteServer_GerritServer($id, $host, $ssh_port, $http_port, $login, $identity_file, $replication_key, $use_ssl);

        $this->assertEqual($server->getReplicationKey('gerrit_project_name'), $replication_key);
    }
}

class Git_RemoteServer_GerritServer_EndUserCloneUrlTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $id                 = 1;
        $this->host         = 'le_host';
        $http_port          = '8080';
        $ssh_port           = '29418';
        $login              = 'le_login';
        $identity_file      = 'le_identity_file';
        $replication_key    = '';
        $use_ssl            = false;

        $this->server = new Git_RemoteServer_GerritServer($id, $this->host, $ssh_port, $http_port, $login, $identity_file, $replication_key, $use_ssl);

        $this->user = aUser()->build();
    }

    public function itGivesTheCloneUrlForTheEndUserWhoWantToCloneRepository() {
        $user = stub('Git_Driver_Gerrit_User')->getSshUserName()->returns('blurp');
        $this->assertEqual($this->server->getEndUserCloneUrl('gerrit_project_name', $user), 'ssh://blurp@le_host:29418/gerrit_project_name.git');
    }
}

?>
