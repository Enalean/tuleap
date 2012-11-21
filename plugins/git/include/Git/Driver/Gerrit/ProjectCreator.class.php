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

require_once GIT_BASE_DIR . '/Git/Driver/Gerrit/RemoteSSHCommand.class.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit.class.php';

class Git_Gerrit_Driver_ProjectCreator {

    /** @var Git_Driver_Gerrit */
    private $driver;

    /** @var Git_RemoteServer_GerritServer */
    private $gerrit_server;

    /** @var string */
    private $dir;

    public function __construct($dir, Git_Driver_Gerrit $driver, Git_RemoteServer_GerritServer $server) {
        $this->dir           = $dir;
        $this->driver        = $driver;
        $this->gerrit_server = $server;
    }

    private function cloneGerritProjectConfig($gerrit_project_url) {
        // TODO: remove this hard-coded 'firefox'
        `mkdir $this->dir/firefox; cd $this->dir/firefox`;
        `cd $this->dir/firefox; git init`;
        `cd $this->dir/firefox; git pull $gerrit_project_url refs/meta/config`;
        `cd $this->dir/firefox; git checkout FETCH_HEAD`;
    }

    public function initiatePermissions($gerrit_project_url, $contributors, $integrators, $supermen) {
        $this->cloneGerritProjectConfig($gerrit_project_url);
        $this->addGroupToGroupFile($contributors);
        $this->addGroupToGroupFile($integrators);
        $this->addGroupToGroupFile($supermen);
        $this->addPermissionsToProjectConf($contributors, $integrators, $supermen);
        $this->pushToServer();
    }

    private function addGroupToGroupFile($group) {
        $group_uuid = $this->driver->getGroupUUID($this->gerrit_server, $group);
        file_put_contents("$this->dir/firefox/groups", "$group_uuid\t$group", FILE_APPEND);
    }

    private function addPermissionsToProjectConf($contributors, $integrators, $supermen) {
        // TODO: if (it is a public project && RegisteredUsers = Read) {
        `cd $this->dir/firefox; git config -f project.config --add access.refs/heads/*.Read 'group Registered Users'`;
        // }
        `cd $this->dir/firefox; git config -f project.config --add access.refs/heads/*.Read 'group $contributors'`;
        `cd $this->dir/firefox; git config -f project.config --add access.refs/heads/*.create 'group $integrators'`;
        // TODO: complete this list of access rights
    }

    private function pushToServer() {
        `cd $this->dir/firefox; git add project.config groups`;
        `cd $this->dir/firefox; git commit -m 'Updated project config'`;
    }
}

?>
