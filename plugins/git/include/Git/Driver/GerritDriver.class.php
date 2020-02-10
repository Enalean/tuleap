<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

/**
 * I know how to speak to a Gerrit remote server
 */
interface Git_Driver_Gerrit
{

    public const CACHE_ACCOUNTS        = 'accounts';
    public const CACHE_GROUPS_INCLUDES = 'groups_byinclude';

    public const DEFAULT_PARENT_PROJECT = 'All-Projects';

    public const DELETEPROJECT_PLUGIN_NAME   = 'deleteproject';
    public const GERRIT_PLUGIN_ENABLED_VALUE = 'ENABLED';

    /**
     *
     * @param String $parent_project_name
     * @return String Gerrit project name
     */
    public function createProject(
        Git_RemoteServer_GerritServer $server,
        GitRepository $repository,
        $parent_project_name
    );

    /**
     *
     * @param String $admin_group_name
     * @return String Gerrit project name
     */
    public function createProjectWithPermissionsOnly(
        Git_RemoteServer_GerritServer $server,
        Project $project,
        $admin_group_name
    );

    /**
     *
     * @param string $project_name
     * @return bool true if the gerrit project exists, else return false
     */
    public function doesTheParentProjectExist(Git_RemoteServer_GerritServer $server, $project_name): bool;

    /**
     * @param string $project_name
     * @return bool true if the gerrit project exists, else return false
     *
     * @throws Git_Driver_Gerrit_Exception
     */
    public function doesTheProjectExist(Git_RemoteServer_GerritServer $server, $project_name): bool;

    /**
     *
     * @throws Git_Driver_Gerrit_Exception
     */
    public function ping(Git_RemoteServer_GerritServer $server);

    /**
     *
     * @return array the list of the parent project created in the gerrit server
     */
    public function listParentProjects(Git_RemoteServer_GerritServer $server);

    /**
     *
     * @param String $group_name
     * @param String $owner
     */
    public function createGroup(Git_RemoteServer_GerritServer $server, $group_name, $owner);

    public function getGroupUUID(Git_RemoteServer_GerritServer $server, $group_full_name);

    /**
     * Returns gerrit GROUP_ID for a user group
     *
     * Can throw Git_Driver_Gerrit_RemoteSSHCommandFailure exception
     *
     * @param type $group_full_name
     *
     * @return type
     */
    public function getGroupId(Git_RemoteServer_GerritServer $server, $group_full_name);

    public function doesTheGroupExist(Git_RemoteServer_GerritServer $server, $group_name);

    public function listGroups(Git_RemoteServer_GerritServer $server);

    /**
     *
     * @return array of (groupname => uuid)
     */
    public function getAllGroups(Git_RemoteServer_GerritServer $server);

    public function getGerritProjectName(GitRepository $repository);

    /**
     * Add a user to a user group on a given gerrit server
     *
     * Can throw Git_Driver_Gerrit_RemoteSSHCommandFailure exception
     */
    public function addUserToGroup(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $group_name);

    /**
     * Remove a user from a user group on a given gerrit server
     *
     * Can throw Git_Driver_Gerrit_RemoteSSHCommandFailure exception
     */
    public function removeUserFromGroup(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $group_name
    );

    /**
     * Remove all members from a user group on a given gerrit server
     *
     * Can throw Git_Driver_Gerrit_RemoteSSHCommandFailure exception
     *
     * @param String $group_name
     */
    public function removeAllGroupMembers(Git_RemoteServer_GerritServer $server, $group_name);

    /**
     * Add a user group as member of another user group on a given gerrit server
     *
     * Can throw Git_Driver_Gerrit_RemoteSSHCommandFailure exception
     *
     * @param String $group_name
     * @param String $included_group_name
     */
    public function addIncludedGroup(Git_RemoteServer_GerritServer $server, $group_name, $included_group_name);

    /**
     * Remove all user groups that are member of a user group on a given gerrit server
     *
     * Can throw Git_Driver_Gerrit_RemoteSSHCommandFailure exception
     *
     * @param String $group_name
     */
    public function removeAllIncludedGroups(Git_RemoteServer_GerritServer $server, $group_name);

    public function flushGerritCacheAccounts($server);

    /**
     *
     * @param string $ssh_key
     * @throws Git_Driver_Gerrit_Exception
     *
     */
    public function addSSHKeyToAccount(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $ssh_key);

    /**
     *
     * @param string $ssh_key
     * @throws Git_Driver_Gerrit_Exception
     */
    public function removeSSHKeyFromAccount(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $ssh_key
    );

    /**
     * Set the parent of a project
     * @param string $project_name
     * @param string $parent_project_name
     */
    public function setProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name, $parent_project_name);

    /**
     * Reset the parent of a project
     * @param string $project_name
     */
    public function resetProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name);

    /**
     * @return bool
     */
    public function isDeletePluginEnabled(Git_RemoteServer_GerritServer $server);

    /**
     * @param string $gerrit_project_full_name E.g. bugs or bugs/repository1
     *
     * @throws Git_Driver_Gerrit_Exception
     */
    public function deleteProject(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name);

    /**
     * @param string $gerrit_project_full_name
     *
     * @throws Git_Driver_Gerrit_Exception
     */
    public function makeGerritProjectReadOnly(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name);
}
