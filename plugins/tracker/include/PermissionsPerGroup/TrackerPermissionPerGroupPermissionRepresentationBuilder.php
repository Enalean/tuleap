<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\PermissionsPerGroup;

use Project;
use ProjectUGroup;
use Tracker;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentationBuilder;
use UGroupManager;

class TrackerPermissionPerGroupPermissionRepresentationBuilder
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var PermissionPerGroupUGroupRepresentationBuilder
     */
    private $ugroup_builder;

    public function __construct(
        UGroupManager $ugroup_manager,
        PermissionPerGroupUGroupRepresentationBuilder $ugroup_builder
    ) {
        $this->ugroup_manager = $ugroup_manager;
        $this->ugroup_builder = $ugroup_builder;
    }

    public function build(Project $project, array $ugroups_permissions, $selected_ugroup_id = null)
    {
        $indexed_permissions = array();

        foreach ($ugroups_permissions as $ugroup_permission) {
            $permissions = $ugroup_permission['permissions'];
            $ugroups     = $ugroup_permission['ugroup'];
            $ugroup_id   = $ugroups['id'];

            if (count($permissions) === 0) {
                continue;
            }

            $permission_name = array_keys($permissions)[0];

            if (! array_key_exists($permission_name, $indexed_permissions)) {
                $indexed_permissions[$permission_name] = array();
            }

            array_push(
                $indexed_permissions[$permission_name],
                $ugroup_id
            );
        }

        $this->appendTrackerVIPs($project, $indexed_permissions);

        if ($selected_ugroup_id) {
            $indexed_permissions = $this->getPermissionsGrantedToSelectedUgroup(
                $selected_ugroup_id,
                $indexed_permissions
            );
        }

        return $this->getCollectionOfPermissionsRepresentations($indexed_permissions);
    }

    private function getCollectionOfPermissionsRepresentations(array $indexed_permissions)
    {
        $permissions_collection = array();

        foreach ($indexed_permissions as $permission_name => $granted_groups) {
            $permissions_collection[] = new TrackerPermissionPerGroupPermissionRepresentation(
                permission_get_name($permission_name),
                $this->getBadges($granted_groups)
            );
        }

        return $permissions_collection;
    }

    private function getBadges(array $granted_groups)
    {
        $badges = [];

        foreach ($granted_groups as $group_id) {
            $user_group = $this->ugroup_manager->getById($group_id);

            $badges[] = $this->ugroup_builder->build(
                $user_group->getProject(),
                $user_group->getId()
            );
        }

        return $badges;
    }

    private function getPermissionsGrantedToSelectedUgroup($ugroup_id, array $permissions_per_group)
    {
        $granted_permissions = [];

        foreach ($permissions_per_group as $permission => $granted_groups) {
            if (in_array($ugroup_id, $granted_groups)) {
                $granted_permissions[$permission] = $granted_groups;
            }
        }

        return $granted_permissions;
    }

    /**
     * "Please note that project administrators and tracker administrators are granted full access to the tracker."
     */
    private function appendTrackerVIPs(Project $project, array &$indexed_permissions)
    {
        if (! array_key_exists(Tracker::PERMISSION_FULL, $indexed_permissions)) {
            $indexed_permissions[Tracker::PERMISSION_FULL] = [];
        }

        $project_admins = $this->ugroup_manager->getProjectAdminsUGroup($project);

        array_unshift(
            $indexed_permissions[Tracker::PERMISSION_FULL],
            $project_admins->getId()
        );

        if (! array_key_exists(Tracker::PERMISSION_ADMIN, $indexed_permissions)) {
            return;
        }

        $tracker_VIPs = array_merge(
            $indexed_permissions[Tracker::PERMISSION_FULL],
            $indexed_permissions[Tracker::PERMISSION_ADMIN]
        );

        $indexed_permissions[Tracker::PERMISSION_FULL] = array_unique($tracker_VIPs);
    }
}
