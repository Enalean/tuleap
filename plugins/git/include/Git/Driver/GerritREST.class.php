<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * I know how to speak to a Gerrit 2.8+ remote server
 */
class Git_Driver_GerritREST implements Git_Driver_Gerrit {

    public function createProject(
        Git_RemoteServer_GerritServer $server,
        GitRepository $repository,
        $parent_project_name
    ) {
        return;
    }

    public function createProjectWithPermissionsOnly(
        Git_RemoteServer_GerritServer $server,
        Project $project,
        $admin_group_name
     ){
        return;
    }

    public function doesTheParentProjectExist(Git_RemoteServer_GerritServer $server, $project_name ){
        return;
    }

    public function doesTheProjectExist(Git_RemoteServer_GerritServer $server, $project_name ){
        return;
    }

    public function ping(Git_RemoteServer_GerritServer $server ){
        return;
    }

    public function listParentProjects(Git_RemoteServer_GerritServer $server ){
        return;
    }

    public function createGroup(Git_RemoteServer_GerritServer $server, $group_name, $owner ){
        return;
    }

    public function getGroupUUID(Git_RemoteServer_GerritServer $server, $group_full_name ){
        return;
    }

    public function getGroupId(Git_RemoteServer_GerritServer $server, $group_full_name ){
        return;
    }

    public function doesTheGroupExist(Git_RemoteServer_GerritServer $server, $group_name ){
        return;
    }

    public function listGroups(Git_RemoteServer_GerritServer $server ){
        return;
    }

    public function listGroupsVerbose(Git_RemoteServer_GerritServer $server ){
        return;
    }

    public function getGerritProjectName(GitRepository $repository ){
        return;
    }

    public function addUserToGroup(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $group_name ){
        return;
    }

    public function removeUserFromGroup(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $group_name
     ){
        return;
    }

    public function removeAllGroupMembers(Git_RemoteServer_GerritServer $server, $group_name ){
        return;
    }

    public function addIncludedGroup(Git_RemoteServer_GerritServer $server, $group_name, $included_group_name ){
        return;
    }

    public function removeAllIncludedGroups(Git_RemoteServer_GerritServer $server, $group_name ){
        return;
    }

    public function flushGerritCacheAccounts($server ){
        return;
    }

    public function addSSHKeyToAccount(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $ssh_key ){
        return;
    }

    public function removeSSHKeyFromAccount(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $ssh_key
     ){
        return;
    }

    public function setProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name, $parent_project_name ){
        return;
    }

    public function resetProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name ){
        return;
    }

    public function isDeletePluginEnabled(Git_RemoteServer_GerritServer $server ){
        return;
    }

    public function deleteProject(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name ){
        return;
    }

    public function makeGerritProjectReadOnly(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name ){
        return;
    }
}
