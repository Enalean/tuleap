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

require_once dirname(__FILE__).'/../../../include/constants.php';
require_once GIT_BASE_DIR.'/Git/RemoteServer/GerritServer.class.php';

class Git_RemoteServer_GerritServerTest extends TuleapTestCase {

    public function itDoesNotNeedToCustomizeSSHConfigOfCodendiadmOrRoot() {
        $id            = 1;
        $host          = 'le_host';
        $http_port     = 'le_http_port';
        $ssh_port      = 'le_ssh_port';
        $login         = 'le_login';
        $identity_file = 'le_identity_file';
        $server = new Git_RemoteServer_GerritServer($id, $host, $ssh_port, $http_port, $login, $identity_file);

        $expected = 'ext::ssh -p le_ssh_port -i le_identity_file le_login@le_host %S le_project';
        $this->assertEqual($expected, $server->getCloneSSHUrl("le_project"));
    }

    public function itPrunesDefaultHTTPPortForAdminUrl() {
        $id            = 1;
        $host          = 'le_host';
        $http_port     = '80';
        $ssh_port      = 'le_ssh_port';
        $login         = 'le_login';
        $identity_file = 'le_identity_file';
        $server = new Git_RemoteServer_GerritServer($id, $host, $ssh_port, $http_port, $login, $identity_file);

        $this->assertEqual($server->getProjectAdminUrl('gerrit_project_name'), 'http://le_host/#/admin/projects/gerrit_project_name');
    }

    public function itUseTheCustomHTTPPortForAdminUrl() {
        $id            = 1;
        $host          = 'le_host';
        $http_port     = '8080';
        $ssh_port      = 'le_ssh_port';
        $login         = 'le_login';
        $identity_file = 'le_identity_file';
        $server = new Git_RemoteServer_GerritServer($id, $host, $ssh_port, $http_port, $login, $identity_file);

        $this->assertEqual($server->getProjectAdminUrl('gerrit_project_name'), 'http://le_host:8080/#/admin/projects/gerrit_project_name');
    }

    public function itGivesTheUrlToProjectRequests() {
        $id            = 1;
        $host          = 'le_host';
        $http_port     = '8080';
        $ssh_port      = 'le_ssh_port';
        $login         = 'le_login';
        $identity_file = 'le_identity_file';
        $server = new Git_RemoteServer_GerritServer($id, $host, $ssh_port, $http_port, $login, $identity_file);

        $this->assertEqual($server->getProjectUrl('gerrit_project_name'), 'http://le_host:8080/#/q/project:gerrit_project_name,n,z');
    }
}
?>
