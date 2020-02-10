<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\PermissionsPerGroup;

use PermissionsManager;
use Planning;
use PlanningPermissionsManager;
use Project;
use ProjectUGroup;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentationBuilder;

class PlanningPermissionsRepresentationBuilder
{
    /**
     * @var PlanningPermissionsManager
     */
    private $planning_permissions_manager;
    /**
     * @var PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var PermissionPerGroupUGroupRepresentationBuilder
     */
    private $ugroup_builder;

    public function __construct(
        PlanningPermissionsManager $planning_permissions_manager,
        PermissionsManager $permissions_manager,
        PermissionPerGroupUGroupRepresentationBuilder $ugroup_builder
    ) {
        $this->planning_permissions_manager = $planning_permissions_manager;
        $this->permissions_manager          = $permissions_manager;
        $this->ugroup_builder               = $ugroup_builder;
    }

    public function build(Project $project, Planning $planning, $user_group)
    {
        $ugroups = $this->getPlanningPrioritizers($planning, $project, $user_group);

        if (count($ugroups) === 0) {
            return;
        }

        return new PlanningPermissionRepresentation(
            $this->getPlanningAdminQuickLink($planning),
            $planning->getName(),
            $ugroups
        );
    }

    /**
     *
     * @return array
     */
    private function getPlanningPrioritizers(
        Planning $planning,
        Project $project,
        ?ProjectUGroup $user_group = null
    ) {
        $planning_prioritizers = array();
        $prioritizers          = $this->planning_permissions_manager->getGroupIdsWhoHasPermissionOnPlanning(
            $planning->getId(),
            $planning->getGroupId(),
            PlanningPermissionsManager::PERM_PRIORITY_CHANGE
        );

        if (count($prioritizers) === 0) {
            $default_permissions = $this->permissions_manager->getDefaults(
                PlanningPermissionsManager::PERM_PRIORITY_CHANGE
            );
            $prioritizers        = $this->extractDefaultPermissions($default_permissions);
        }

        if ($user_group && ! in_array($user_group->getId(), $prioritizers)) {
            return [];
        }

        foreach ($prioritizers as $user_group_id) {
            $planning_prioritizers[] = $this->ugroup_builder->build(
                $project,
                $user_group_id
            );
        }

        return $planning_prioritizers;
    }

    private function extractDefaultPermissions(LegacyDataAccessResultInterface $default_granted_ugroups)
    {
        $default_granted_ugroups_list = array();

        foreach ($default_granted_ugroups as $ugroup) {
            $default_granted_ugroups_list[] = $ugroup['ugroup_id'];
        }

        return $default_granted_ugroups_list;
    }

    private function getPlanningAdminQuickLink(Planning $planning)
    {
        $query_params = http_build_query(
            array(
                "group_id"    => $planning->getGroupId(),
                "planning_id" => $planning->getId(),
                "action"      => 'edit'
            )
        );

        return "/plugins/agiledashboard/?" . $query_params;
    }
}
