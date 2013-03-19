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
require_once GIT_BASE_DIR . '/GitRepository.class.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit/MembershipManager.class.php';
require_once 'UserFinder.class.php';

class Git_Driver_Gerrit_ProjectCreator {

    const GROUP_REPLICATION = 'replication';

    /** @var Git_Driver_Gerrit */
    private $driver;

    /** @var string */
    private $dir;

    /** @var Git_Driver_Gerrit_UserFinder */
    private $user_finder;

    /** @var UGroupManager */
    private $ugroup_manager;

    public function __construct($dir, Git_Driver_Gerrit $driver, Git_Driver_Gerrit_UserFinder $user_finder, UGroupManager $ugroup_manager) {
        $this->dir            = $dir;
        $this->driver         = $driver;
        $this->user_finder    = $user_finder;
        $this->ugroup_manager = $ugroup_manager;
    }

    public function createProject(Git_RemoteServer_GerritServer $gerrit_server, GitRepository $repository) {
        $gerrit_project = $this->driver->createProject($gerrit_server, $repository);

//        foreach (Git_Driver_Gerrit_MembershipManager::$GERRIT_GROUPS as $group_name => $permission_level) {
//            try {
//                $user_list = $this->user_finder->getUsersForPermission($permission_level, $repository);
//                $this->driver->createGroup($gerrit_server, $repository, $group_name, $user_list);
//            } catch (Exception $e) {
//                // Continue with the next group
//                // Should we add a warning ?
//            }
//        }

        $ugroups = $this->ugroup_manager->getUGroups($repository->getProject());
        foreach ($ugroups as $ugroup) {
            $this->driver->createGroup($gerrit_server, $repository, $repository->getProject()->getUnixName().'/'.$ugroup->getNormalizedName(), $ugroup->getMembers());
        }

        $this->initiatePermissions(
            $repository,
            $gerrit_server,
            $gerrit_server->getCloneSSHUrl($gerrit_project),
            $ugroups,
            Config::get('sys_default_domain') .'-'. self::GROUP_REPLICATION
        );
        
        $this->exportGitBranches($gerrit_server, $gerrit_project, $repository);

        return $gerrit_project;
    }

    private function exportGitBranches(Git_RemoteServer_GerritServer $gerrit_server, $gerrit_project, GitRepository $repository) {
        $gerrit_project_url = escapeshellarg($gerrit_server->getCloneSSHUrl($gerrit_project));
        $cmd                = "cd ".$repository->getFullPath()."; git push $gerrit_project_url refs/heads/*:refs/heads/*; git push $gerrit_project_url refs/tags/*:refs/tags/*";
        `$cmd`;
    }

    private function initiatePermissions(GitRepository $repository, Git_RemoteServer_GerritServer $gerrit_server, $gerrit_project_url, array $ugroups, $replication_group) {
        $this->cloneGerritProjectConfig($gerrit_server, $gerrit_project_url);
        foreach ($ugroups as $ugroup) {
            $this->addGroupToGroupFile($gerrit_server, $repository->getProject()->getUnixName().'/'.$ugroup->getNormalizedName());
        }
        $this->addGroupToGroupFile($gerrit_server, $replication_group);
        $this->addGroupToGroupFile($gerrit_server, 'Administrators');
        $this->addRegisteredUsersGroupToGroupFile();
        $this->addPermissionsToProjectConf($repository, $contributors, $integrators, $supermen, $owners, $replication_group);
        $this->pushToServer();
    }

    private function cloneGerritProjectConfig(Git_RemoteServer_GerritServer $gerrit_server, $gerrit_project_url) {
        $gerrit_project_url = escapeshellarg($gerrit_project_url);
        `mkdir $this->dir`;
        `cd $this->dir; git init`;
        $this->setUpCommitter($gerrit_server);
        `cd $this->dir; git remote add origin $gerrit_project_url`;
        `cd $this->dir; git pull origin refs/meta/config`;
        `cd $this->dir; git checkout FETCH_HEAD`;
    }

    private function setUpCommitter(Git_RemoteServer_GerritServer $gerrit_server) {
        $name  = escapeshellarg($gerrit_server->getLogin());
        $email = escapeshellarg('codendiadm@'. Config::get('sys_default_domain'));
        `cd $this->dir; git config --add user.name $name`;
        `cd $this->dir; git config --add user.email $email`;
    }

    private function addGroupToGroupFile(Git_RemoteServer_GerritServer $gerrit_server, $group) {
        $group_uuid = $this->driver->getGroupUUID($gerrit_server, $group);
        $this->addGroupDefinitionToGroupFile($group_uuid, $group);
    }

    private function addRegisteredUsersGroupToGroupFile() {
        $this->addGroupDefinitionToGroupFile('global:Registered-Users', 'Registered Users');
    }

    private function addGroupDefinitionToGroupFile($uuid, $group_name) {
        file_put_contents("$this->dir/groups", "$uuid\t$group_name\n", FILE_APPEND);
    }

    private function addPermissionsToProjectConf(GitRepository $repository, $contributors, $integrators, $supermen, $owners, $replication_group) {
        // https://groups.google.com/d/msg/repo-discuss/jTAY2ApcTGU/DPZz8k0ZoUMJ
        // Project owners are those who own refs/* within that project... which
        // means they can modify the permissions for any reference in the
        // project.
        $this->addToSection('refs', 'owner', "group $owners");
        $this->addToSection('refs', 'Read', "group $replication_group");

        if ($this->shouldAddRegisteredUsers($repository)) {
            $this->addToSection('refs/heads', 'Read', "group Registered Users");
        }
        $this->addToSection('refs/heads', 'Read', "group $contributors");
        $this->addToSection('refs/heads', 'Read', "group $integrators");
        $this->addToSection('refs/heads', 'create', "group $integrators");
        $this->addToSection('refs/heads', 'forgeAuthor', "group $integrators");
        $this->addToSection('refs/heads', 'label-Code-Review', "-2..+2 group $integrators");
        $this->addToSection('refs/heads', 'label-Code-Review', "-1..+1 group $contributors");
        $this->addToSection('refs/heads', 'label-Verified', "-1..+1 group $integrators");
        $this->addToSection('refs/heads', 'submit', "group $integrators");
        $this->addToSection('refs/heads', 'push', "group $integrators");
        $this->addToSection('refs/heads', 'push', "+force group $supermen");
        $this->addToSection('refs/heads', 'pushMerge', "group $integrators");

        $this->addToSection('refs/heads', 'create', "group Administrators");  // push initial ref
        $this->addToSection('refs/heads', 'forgeCommitter', "group Administrators"); // push initial ref

        $this->addToSection('refs/changes', 'push', "group $contributors");
        $this->addToSection('refs/changes', 'push', "group $integrators");
        $this->addToSection('refs/changes', 'push', "+force group $supermen");
        $this->addToSection('refs/changes', 'pushMerge', "group $integrators");

        $this->addToSection('refs/for/refs/heads', 'push', "group $contributors");
        $this->addToSection('refs/for/refs/heads', 'push', "group $integrators");
        $this->addToSection('refs/for/refs/heads', 'pushMerge', "group $integrators");

        // To be able to push merge commit on master, we need pushMerge on refs/for/*
        // http://code.google.com/p/gerrit/issues/detail?id=1072
        $this->addToSection('refs/for', 'pushMerge', "group Administrators"); // push initial ref

        $this->addToSection('refs/tags', 'read', "group $contributors");
        $this->addToSection('refs/tags', 'read', "group $integrators");
        $this->addToSection('refs/tags', 'pushTag', "group $integrators");

        $this->addToSection('refs/tags', 'pushTag', "group Administrators"); // push initial ref
        $this->addToSection('refs/tags', 'create', "group Administrators");  // push initial ref
        $this->addToSection('refs/tags', 'forgeCommitter', "group Administrators");  // push initial ref
    }

    private function shouldAddRegisteredUsers(GitRepository $repository) {
        return $repository->getProject()->isPublic() && $this->user_finder->areRegisteredUsersAllowedTo(Git::PERM_READ, $repository);
    }

    private function addToSection($section, $permission, $value) {
        `cd $this->dir; git config -f project.config --add access.$section/*.$permission '$value'`;
    }
    private function pushToServer() {
        `cd $this->dir; git add project.config groups`;
        `cd $this->dir; git commit -m 'Updated project config and access rights'`; //TODO: what about author name?
        `cd $this->dir; git push origin HEAD:refs/meta/config`;
    }

    public function removeTemporaryDirectory() {
        $backend = Backend::instance('System');
        $backend->recurseDeleteInDir($this->dir);
        rmdir($this->dir);
    }
}

?>
