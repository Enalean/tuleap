<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
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

namespace Tuleap\Tracker;

use Project;
use ProjectUGroup;
use Tracker;
use TrackerFactory;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use UGroupManager;

class ProjectAdminPermissionPerGroupPresenterBuilder
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var PermissionPerGroupUGroupFormatter
     */
    private $badge_formatter;

    public function __construct(
        UGroupManager $ugroup_manager,
        TrackerFactory $tracker_factory,
        PermissionPerGroupUGroupFormatter $badge_formatter
    ) {
        $this->ugroup_manager  = $ugroup_manager;
        $this->tracker_factory = $tracker_factory;
        $this->badge_formatter = $badge_formatter;
    }

    public function buildPresenter(Project $project, $ugroup_id = null)
    {
        $permissions = $this->getPermissions($project, $ugroup_id);
        $ugroup      = ($ugroup_id)
            ? $this->ugroup_manager->getById($ugroup_id)
            : null;

        return new PermissionPerGroupPanePresenter(
            $permissions,
            $ugroup
        );
    }

    private function getPermissions(Project $project, $ugroup_id = null)
    {
        $trackers_list = $this->tracker_factory->getTrackersByGroupId($project->group_id);
        $permissions   = array();

        foreach ($trackers_list as $tracker) {
            $ugroups_permissions = plugin_tracker_permission_get_tracker_ugroups_permissions(
                $tracker->getGroupId(),
                $tracker->getId()
            );

            $permissions_per_group = $this->indexGroupsByPermission($project, $ugroups_permissions, $ugroup_id);

            if (count($permissions_per_group) === 0) {
                continue;
            }
            $permissions[] = array(
                "tracker"     => $tracker->getName(),
                "quick_link"  => $this->getTrackerAdminQuickLink($tracker),
                "permissions" => $permissions_per_group
            );
        }

        return $permissions;
    }

    private function indexGroupsByPermission(Project $project, array $ugroups_permissions, $selected_ugroup_id = null)
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

        return $this->getCollectionOfPermissions($indexed_permissions);
    }

    private function getCollectionOfPermissions(array $indexed_permissions)
    {
        $permissions_collection = array();

        foreach ($indexed_permissions as $permission_name => $granted_groups) {
            $permissions_collection[] = array(
                'permission_name' => permission_get_name($permission_name),
                'granted_groups'  => $this->getBadges($granted_groups)
            );
        }

        return $permissions_collection;
    }

    private function getBadges(array $granted_groups)
    {
        $badges = [];

        foreach ($granted_groups as $group_id) {
            $user_group = $this->ugroup_manager->getById($group_id);

            $badges[] = $this->badge_formatter->formatGroup(
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
                $granted_permissions[ $permission ] = $granted_groups;
            }
        }

        return $granted_permissions;
    }

    private function getTrackerAdminQuickLink(Tracker $tracker)
    {
        $query_parameters = http_build_query([
            "tracker" => $tracker->getId(),
            "func"    => "admin-perms-tracker"
        ]);

        return "/plugins/tracker/?" . $query_parameters;
    }

    /**
     * "Please note that project administrators and tracker administrators are granted full access to the tracker."
     */
    private function appendTrackerVIPs(Project $project, array & $indexed_permissions)
    {
        if (! array_key_exists(Tracker::PERMISSION_FULL, $indexed_permissions)) {
            $indexed_permissions[ Tracker::PERMISSION_FULL ] = [];
        }

        $project_admins = $this->ugroup_manager->getUGroup($project, ProjectUGroup::PROJECT_ADMIN);

        array_unshift(
            $indexed_permissions[ Tracker::PERMISSION_FULL ],
            $project_admins->getId()
        );

        if (! array_key_exists(Tracker::PERMISSION_ADMIN, $indexed_permissions)) {
            return;
        }

        $tracker_VIPs = array_merge(
            $indexed_permissions[ Tracker::PERMISSION_FULL ],
            $indexed_permissions[ Tracker::PERMISSION_ADMIN ]
        );

        $indexed_permissions[ Tracker::PERMISSION_FULL ] = array_unique($tracker_VIPs);
    }
}
