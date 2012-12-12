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

class Git_Driver_Gerrit_ProjectCreator {

    /** @var Git_Driver_Gerrit */
    private $driver;

    /** @var string */
    private $dir;

    /** @var Git_Driver_Gerrit_UserFinder */
    private $user_finder;

    private $gerrit_groups = array('contributors' => Git::PERM_READ,
                                   'integrators'  => Git::PERM_WRITE,
                                   'supermen'     => Git::PERM_WPLUS);

    
    public function __construct($dir, Git_Driver_Gerrit $driver, Git_Driver_Gerrit_UserFinder $user_finder) {
        $this->dir           = $dir;
        $this->driver        = $driver;
        $this->user_finder   = $user_finder;
    }

    public function createProject(Git_RemoteServer_GerritServer $gerrit_server, GitRepository $repository) {
        $gerrit_project = $this->driver->createProject($gerrit_server, $repository);

        foreach ($this->gerrit_groups as $group_name => $permission_level) {
            try {
                $user_list = $this->user_finder->getUsersForPermission($permission_level, $repository);
                $this->driver->createGroup($gerrit_server, $repository, $group_name, $user_list);
            } catch (Exception $e) {
                // Continue with the next group
                // Should we add a warning ?
            }
        }
        $this->initiatePermissions(
            $gerrit_server,
            $gerrit_server->getCloneSSHUrl($gerrit_project),
            $gerrit_project.'-contributors',
            $gerrit_project.'-integrators',
            $gerrit_project.'-supermen'
        );
        return $gerrit_project;
    }

    private function initiatePermissions(Git_RemoteServer_GerritServer $gerrit_server, $gerrit_project_url, $contributors, $integrators, $supermen) {
        $this->cloneGerritProjectConfig($gerrit_project_url);
        $this->addGroupToGroupFile($gerrit_server, $contributors);
        $this->addGroupToGroupFile($gerrit_server, $integrators);
        $this->addGroupToGroupFile($gerrit_server, $supermen);
        $this->addRegisteredUsersGroupToGroupFile();
        $this->addPermissionsToProjectConf($contributors, $integrators, $supermen);
        $this->pushToServer();
    }

    private function cloneGerritProjectConfig($gerrit_project_url) {
        $gerrit_project_url = escapeshellarg($gerrit_project_url);
        `mkdir $this->dir`;
        `cd $this->dir; git init`;
        `cd $this->dir; git remote add origin $gerrit_project_url`;
        `cd $this->dir; git pull origin refs/meta/config`;
        `cd $this->dir; git checkout FETCH_HEAD`;
    }

    private function addGroupToGroupFile(Git_RemoteServer_GerritServer $gerrit_server, $group) {
        $group_uuid = $this->driver->getGroupUUID($gerrit_server, $group);
        $this->addGroupDefinitionToGroupFile($group_uuid, $group);
    }

    private function addRegisteredUsersGroupToGroupFile() {
        $this->addGroupDefinitionToGroupFile('global:Registered-Users', 'Registered Users');
    }

    private function addGroupDefinitionToGroupFile($uuid, $group_name) {
        file_put_contents("$this->dir/groups", "$uuid\t$group_name", FILE_APPEND);
    }

    private function addPermissionsToProjectConf($contributors, $integrators, $supermen) {
        // TODO: if (it is a public project && RegisteredUsers = Read) {
        `cd $this->dir; git config -f project.config --add access.refs/heads/*.Read 'group Registered Users'`;
        // }
        `cd $this->dir; git config -f project.config --add access.refs/heads/*.Read 'group $contributors'`;
        `cd $this->dir; git config -f project.config --add access.refs/heads/*.create 'group $integrators'`;
        // TODO: complete this list of access rights
    }

    private function pushToServer() {
        `cd $this->dir; git add project.config groups`;
        `cd $this->dir; git commit -m 'Updated project config and access rights'`; //TODO: what about author name?
        `cd $this->dir; git push origin HEAD:refs/meta/config`;
    }
}

?>
