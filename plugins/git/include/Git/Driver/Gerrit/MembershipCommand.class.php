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
    private   $user_finder;
    protected $driver;

    public function __construct(Git_Driver_Gerrit $driver, Git_Driver_Gerrit_UserFinder $user_finder) {
        $this->driver      = $driver;
        $this->user_finder = $user_finder;
    }

    protected abstract function propagateToGerrit(Git_RemoteServer_GerritServer $server, User $user, $group_full_name);

    protected abstract function isUserConcernedByPermission(User $user, Project $project, $groups);

    public function process(Git_RemoteServer_GerritServer $server, User $user, Project $project, $repository) {
        $groups_full_names = $this->getConcernedGerritGroups($user, $project, $repository);

        foreach ($groups_full_names as $group_full_name) {
            $this->propagateToGerrit($server, $user, $group_full_name);
        }
    }

    protected function getConcernedGerritGroups(User $user, Project $project, GitRepositoryWithPermissions $repository_with_permissions) {
        $groups_full_names = array();

        foreach ($repository_with_permissions->getPermissions() as $tuleap_permission_type => $groups_with_permission) {
            if (count($groups_with_permission) > 0) {
                if ($this->isUserConcernedByPermission($user, $project, $groups_with_permission)) {
                    $groups_full_names[] = $this->getGerritGroupName($project, $repository_with_permissions->getRepository(), Git_Driver_Gerrit_MembershipManager::$PERMS_TO_GROUPS[$tuleap_permission_type]);
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

    private function getGerritGroupName(Project $project, GitRepository $repo, $group_name) {
        $project_name    = $project->getUnixName();
        $repository_name = $repo->getFullName();

        return "$project_name/$repository_name-$group_name";
    }
}
?>
