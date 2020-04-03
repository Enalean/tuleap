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

namespace Tuleap\ProFTPd\PermissionsPerGroup;

use Project;
use ProjectUGroup;
use Tuleap\ProFTPd\Admin\PermissionsManager;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use UGroupManager;

class ProftpdPermissionsPerGroupPresenterBuilder
{
    /**
     * @var PermissionsManager
     */
    private $permission_manager;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var PermissionPerGroupUGroupFormatter
     */
    private $ugroup_formatter;

    public function __construct(
        PermissionsManager $permission_manager,
        UGroupManager $ugroup_manager,
        PermissionPerGroupUGroupFormatter $ugroup_formatter
    ) {
        $this->permission_manager = $permission_manager;
        $this->ugroup_manager     = $ugroup_manager;
        $this->ugroup_formatter   = $ugroup_formatter;
    }

    public function build(Project $project, $selected_ugroup_id = null)
    {
        $permissions = $this->getPermissions($project, $selected_ugroup_id);

        return new PermissionPerGroupPanePresenter(
            $permissions,
            $this->ugroup_manager->getUGroup(
                $project,
                $selected_ugroup_id
            )
        );
    }

    private function getPermissions(Project $project, $selected_ugroup_id)
    {
        return array_values(
            array_filter([
                $this->extractReadersPermissions($project, $selected_ugroup_id),
                $this->extractWritersPermissions($project, $selected_ugroup_id)
            ])
        );
    }

    private function extractReadersPermissions(Project $project, $selected_ugroup_id)
    {
        $readers_ugroups_ids = $this->removeNobodyUgroupFromUgroups([
            ProjectUGroup::PROJECT_ADMIN,
            $this->permission_manager->getSelectUGroupFor($project, PermissionsManager::PERM_READ),
            $this->permission_manager->getSelectUGroupFor($project, PermissionsManager::PERM_WRITE)
        ]);

        if (
            $selected_ugroup_id
            && ! in_array($selected_ugroup_id, $readers_ugroups_ids)
        ) {
            return [];
        }

        $granted_groups = $this->ugroup_formatter->getFormattedUGroups($project, $readers_ugroups_ids);

        return [
            "name"             => dgettext('tuleap-proftpd', 'Readers'),
            "admin_quick_link" => $this->getAdminQuickLink($project),
            "groups"           => $granted_groups
        ];
    }

    private function extractWritersPermissions(Project $project, $selected_ugroup_id)
    {
        $writers_ugroup_id = $this->removeNobodyUgroupFromUgroups([
            ProjectUGroup::PROJECT_ADMIN,
            $this->permission_manager->getSelectUGroupFor($project, PermissionsManager::PERM_WRITE)
        ]);

        if (
            $selected_ugroup_id
            && ! in_array($selected_ugroup_id, $writers_ugroup_id)
        ) {
            return [];
        }

        $granted_groups = $this->ugroup_formatter->getFormattedUGroups($project, $writers_ugroup_id);

        return [
            "name"             => dgettext('tuleap-proftpd', 'Writers'),
            "admin_quick_link" => $this->getAdminQuickLink($project),
            "groups"           => $granted_groups
        ];
    }

    private function getAdminQuickLink(Project $project)
    {
        return PROFTPD_BASE_URL . '/?' . http_build_query(
            [
                "group_id"   => $project->getID(),
                "controller" => "admin",
                "action"     => "index"
            ]
        );
    }

    private function removeNobodyUgroupFromUgroups(array $ugroups_ids)
    {
        return array_filter($ugroups_ids, function ($ugroup_id) {
            return $ugroup_id !== ProjectUGroup::NONE;
        });
    }
}
