<?php
/**
 * Copyright (c) Enalean, 2012-present. All Rights Reserved.
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

namespace Tuleap\Project;

use PermissionsManager;
use PFUser;
use Project;
use ProjectUGroup;
use UserManager;

/**
 * This class returns ugroup in a literalized form (eg: 'gpig_project_member')
 */
class UGroupLiteralizer
{
    private const USER_STATUS = [
        PFUser::STATUS_RESTRICTED => 'site_restricted',
        PFUser::STATUS_ACTIVE     => 'site_active',
    ];

    private const UGROUPS_TEMPLATES = [
        ProjectUGroup::ANONYMOUS       => '@site_active @%s_project_members',
        ProjectUGroup::REGISTERED      => '@site_active @%s_project_members',
        ProjectUGroup::AUTHENTICATED   => '@site_active @%s_project_members @site_restricted',
        ProjectUGroup::PROJECT_MEMBERS => '@%s_project_members',
        ProjectUGroup::PROJECT_ADMIN   => '@%s_project_admin',
        ProjectUGroup::WIKI_ADMIN      => '@%s_wiki_admin',
    ];

    /**
     * Return User groups for a given user
     *
     * @param string $user_name
     *
     * @return array Ex: array('site_active', 'gpig1_project_members')
     */
    public function getUserGroupsForUserName($user_name)
    {
        $user = UserManager::instance()->getUserByUserName($user_name);
        if (! $user) {
            return [];
        }
        return $this->getUserGroupsForUser($user);
    }

    /**
     * Return User groups for a given user
     *
     *
     * @return array Ex: array('site_active', 'gpig1_project_members')
     */
    public function getUserGroupsForUser(PFUser $user)
    {
        if (! $this->isValidUser($user)) {
            return [];
        }
        return array_merge(
            [self::USER_STATUS[$user->getStatus()]],
            $this->getProjectUserGroupsForUser($user)
        );
    }

    public function getProjectUserGroupsForUser(PFUser $user): array
    {
        if (! $this->isValidUser($user)) {
            return [];
        }

        return array_merge(
            $this->getDynamicUGroups($user),
            $this->getStaticUGroups($user)
        );
    }

    public function getProjectUserGroupsIdsForUser(PFUser $user): array
    {
        if (! $this->isValidUser($user)) {
            return [];
        }

        return array_merge(
            $this->getDynamicUGroupsIds($user),
            $this->getStaticUGroupsIds($user)
        );
    }

    private function getDynamicUGroupsIds(PFUser $user): array
    {
        $groups_ids = [];
        foreach ($user->getProjects(true) as $project) {
            $group_id = $project['group_id'];
            $groups_ids[] = $group_id . '_' . ProjectUGroup::PROJECT_MEMBERS;

            if ($user->isMember($group_id, 'A')) {
                $groups_ids[] = $group_id . '_' . ProjectUGroup::PROJECT_ADMIN;
            }
        }

        return $groups_ids;
    }

    private function getStaticUGroupsIds(PFUser $user): array
    {
        $groups_ids = [];
        foreach ($user->getAllUgroups() as $ugroup) {
            $groups_ids[] = (string) $ugroup['ugroup_id'];
        }

        return $groups_ids;
    }

    /**
     * Return User groups for a given user
     *
     *
     * @return array Ex: array('site_active', 'gpig1_project_members')
     */
    public function getUserGroupsForUserWithArobase(PFUser $user)
    {
        $groups = $this->getUserGroupsForUser($user);

        return array_map(
            static function (string $value): string {
                return '@' . $value;
            },
            $groups
        );
    }

    /**
     * Append project dynamic ugroups of user
     *
     * @return array the array of user's ugroup
     */
    private function getDynamicUGroups(PFUser $user): array
    {
        $user_ugroups   = [];
        $user_projects = $user->getProjects(true);
        foreach ($user_projects as $user_project) {
            $project_name = strtolower($user_project['unix_group_name']);
            $group_id     = $user_project['group_id'];
            $user_ugroups[] = $this->ugroupIdToStringWithoutArobase(ProjectUGroup::PROJECT_MEMBERS, $project_name);
            if ($user->isMember($group_id, 'A')) {
                $user_ugroups[] = $this->ugroupIdToStringWithoutArobase(ProjectUGroup::PROJECT_ADMIN, $project_name);
            }
        }
        return $user_ugroups;
    }

    /**
     * Append project static ugroups of user
     *
     * @return array the array of user's ugroup
     */
    private function getStaticUGroups(PFUser $user)
    {
        $user_ugroups = [];
        $ugroups      = $user->getAllUgroups();
        foreach ($ugroups as $row) {
            $user_ugroups[] = 'ug_' . $row['ugroup_id'];
        }
        return $user_ugroups;
    }

    /**
     * @return bool true if the user is considered valid (active or restricted)
     */
    private function isValidUser(PFUser $user)
    {
        return isset(self::USER_STATUS[$user->getStatus()]);
    }

    /**
     * Convert ugroup id in a given project to a literal form
     *
     * @param int    $ugroup_id    The id of the ugroup
     * @param string $project_name The unix group name of the project
     *
     * @return string|null @ug_102 | @gpig_project_admin | @site_active @gpig_project_member | ... or null if not found
     */
    private function ugroupIdToString($ugroup_id, $project_name): ?string
    {
        $ugroup = null;
        if ($ugroup_id > 100) {
            $ugroup = '@ug_' . $ugroup_id;
        } elseif (isset(self::UGROUPS_TEMPLATES[$ugroup_id])) {
            $ugroup = sprintf(self::UGROUPS_TEMPLATES[$ugroup_id], $project_name);
        }
        return $ugroup;
    }

    /**
     * @see ugroupIdToString
     */
    private function ugroupIdToStringWithoutArobase(int $ugroup_id, string $project_name): string
    {
        return str_replace('@', '', $this->ugroupIdToString($ugroup_id, $project_name) ?? '');
    }

    /**
     * Return a list of groups with permissions of type $permissions_type
     * for the given object of a given project
     *
     * @param Project $project         The project
     * @param int $object_id The identifier of the object
     * @param string  $permission_type PLUGIN_GIT_READ | PLUGIN_DOCMAN_%
     *
     * @return array of groups converted to string
     */
    public function getUGroupsThatHaveGivenPermissionOnObject(Project $project, $object_id, $permission_type)
    {
        $ugroup_ids = $this->getUgroupIds($project, $object_id, $permission_type);
        return $this->ugroupIdsToString($ugroup_ids, $project);
    }

    public function getUgroupIds(Project $project, int $object_id, string $permission_type): array
    {
        return PermissionsManager::instance()->getAuthorizedUGroupIdsForProject($project, $object_id, $permission_type);
    }

    /**
     * @psalm-return list<string>
     */
    public function ugroupIdsToString(array $ugroup_ids, Project $project): array
    {
        $project_name = $project->getUnixName();
        $strings      = [];
        foreach ($ugroup_ids as $key => $ugroup_id) {
            foreach (explode(' ', $this->ugroupIdToString($ugroup_id, $project_name) ?? '') as $string) {
                $strings[] = $string;
            }
        }

        return array_values(array_unique(array_filter($strings)));
    }
}
