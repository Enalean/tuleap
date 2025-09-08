<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\Driver\Gerrit;

use Git_Driver_Gerrit;
use Git_Driver_Gerrit_User;
use Git_RemoteServer_Gerrit_ProjectNameBuilder;
use Git_RemoteServer_GerritServer;
use GitRepository;
use Project;

final class GerritUnsupportedVersionDriver implements Git_Driver_Gerrit
{
    #[\Override]
    public function createProject(
        Git_RemoteServer_GerritServer $server,
        GitRepository $repository,
        $parent_project_name,
    ) {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function createProjectWithPermissionsOnly(
        Git_RemoteServer_GerritServer $server,
        Project $project,
        $admin_group_name,
    ) {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function doesTheParentProjectExist(Git_RemoteServer_GerritServer $server, $project_name): bool
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function doesTheProjectExist(Git_RemoteServer_GerritServer $server, $project_name): bool
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function ping(Git_RemoteServer_GerritServer $server)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function listParentProjects(Git_RemoteServer_GerritServer $server)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function createGroup(Git_RemoteServer_GerritServer $server, $group_name, $owner)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function getGroupUUID(Git_RemoteServer_GerritServer $server, $group_full_name)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function getGroupId(Git_RemoteServer_GerritServer $server, $group_full_name)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function doesTheGroupExist(Git_RemoteServer_GerritServer $server, $group_name)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function listGroups(Git_RemoteServer_GerritServer $server)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function getAllGroups(Git_RemoteServer_GerritServer $server)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function getGerritProjectName(GitRepository $repository)
    {
        $name_builder = new Git_RemoteServer_Gerrit_ProjectNameBuilder();

        return $name_builder->getGerritProjectName($repository);
    }

    #[\Override]
    public function addUserToGroup(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $group_name)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function removeUserFromGroup(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $group_name,
    ) {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function removeAllGroupMembers(Git_RemoteServer_GerritServer $server, $group_name)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function addIncludedGroup(Git_RemoteServer_GerritServer $server, $group_name, $included_group_name)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function removeAllIncludedGroups(Git_RemoteServer_GerritServer $server, $group_name)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function flushGerritCacheAccounts($server)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function addSSHKeyToAccount(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, string $ssh_key): void
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function removeSSHKeyFromAccount(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $ssh_key,
    ) {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function setProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name, $parent_project_name)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function resetProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function isDeletePluginEnabled(Git_RemoteServer_GerritServer $server)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function deleteProject(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name)
    {
        throw new UnsupportedGerritVersionException();
    }

    #[\Override]
    public function makeGerritProjectReadOnly(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name)
    {
        throw new UnsupportedGerritVersionException();
    }
}
