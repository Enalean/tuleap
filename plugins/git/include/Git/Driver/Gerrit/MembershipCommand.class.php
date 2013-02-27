<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

abstract class Git_Driver_Gerrit_MembershipCommand {
    private   $permissions_manager;
    protected $driver;

    public function __construct(Git_Driver_Gerrit $driver, PermissionsManager $permissions_manager) {
        $this->driver              = $driver;
        $this->permissions_manager = $permissions_manager;
    }

    public abstract function process(Git_RemoteServer_GerritServer $server, User $user, Project $project, GitRepository $repository);
    
    protected abstract function isStuff(User $user, Project $project, $groups);

    protected function getConcernedGerritGroups(User $user, Project $project, GitRepository $repository) {
        $groups_full_names = array();

        foreach (Git_Driver_Gerrit_MembershipManager::$GERRIT_GROUPS as $group_name => $permission) {
            $groups_with_permission = $this->getUgroupsWithPermission($repository, $permission);
            if (count($groups_with_permission) > 0) {
                if ($this->isStuff($user, $project, $groups_with_permission)) {
                    $groups_full_names[] = $this->getGerritGroupName($project, $repository, $group_name);
                }
            }
        }

        return $groups_full_names;
    }

    protected function isUserInGroups($user, $project, $group_list) {
        $user_groups = $user->getUgroups($project->getID(), null);
        foreach ($user_groups as $user_group) {
            if (in_array($user_group, $group_list)) {
                return true;
            }
        }

        return false;

    }

    private function getUgroupsWithPermission(GitRepository $repository, $permission) {
        $dar_ugroups = $this->permissions_manager->getUgroupIdByObjectIdAndPermissionType($repository->getId(), $permission);
        $ugroups     = array();

        foreach ($dar_ugroups as $row) {
            $ugroups[]     = $row['ugroup_id'];
        }

        return $ugroups;
    }

    private function getGerritGroupName(Project $project, GitRepository $repo, $group_name) {
        $project_name    = $project->getUnixName();
        $repository_name = $repo->getFullName();

        return "$project_name/$repository_name-$group_name";
    }
}
?>
