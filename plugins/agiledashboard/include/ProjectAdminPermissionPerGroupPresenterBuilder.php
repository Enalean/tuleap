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

namespace Tuleap\AgileDashboard;

use DataAccessResult;
use PermissionsManager;
use PFUser;
use Planning;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use ProjectUGroup;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use UGroupManager;

class ProjectAdminPermissionPerGroupPresenterBuilder
{
    /**
     * @var PlanningPermissionsManager
     */
    private $planning_permissions_manager;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;
    /**
     * @var PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var PermissionPerGroupUGroupFormatter
     */
    private $formatter;

    public function __construct(
        PlanningPermissionsManager $planning_permissions_manager,
        UGroupManager $ugroup_manager,
        PlanningFactory $planning_factory,
        PermissionsManager $permissions_manager,
        PermissionPerGroupUGroupFormatter $formatter
    ) {
        $this->planning_permissions_manager = $planning_permissions_manager;
        $this->ugroup_manager               = $ugroup_manager;
        $this->planning_factory             = $planning_factory;
        $this->permissions_manager          = $permissions_manager;
        $this->formatter                    = $formatter;
    }

    public function buildPresenter(
        Project $project,
        PFUser $user,
        $ugroup_id = null
    ) {
        $ugroup      = $this->ugroup_manager->getUGroup($project, $ugroup_id);
        $permissions = $this->getPermissions(
            $project,
            $user,
            $ugroup
        );

        return new PermissionPerGroupPanePresenter(
            $permissions,
            $ugroup
        );
    }

    private function getPermissions(
        Project $project,
        PFUser $user,
        ProjectUGroup $user_group = null
    ) {
        $plannings      = $this->planning_factory->getPlannings($user, $project->getID());
        $planning_names = array();

        foreach ($plannings as $project_planning) {
            $planning_groups = $this->getAuthorizedGroupsForPlanning($project_planning, $project, $user_group);

            if ($planning_groups) {
                $planning_names[] = $planning_groups;
            }
        }

        return $planning_names;
    }

    private function getAuthorizedGroupsForPlanning(
        Planning $planning,
        Project $project,
        ProjectUGroup $user_group = null
    ) {
        $groups = $this->getPlanningPriorizers($planning, $project, $user_group);

        if (count($groups) === 0) {
            return;
        }

        return array(
            'name'       => $planning->getName(),
            'quick_link' => $this->getPlanningAdminQuickLink($planning),
            'groups'     => $groups
        );
    }

    /**
     * @param Planning $planning
     * @return array
     */
    private function getPlanningPriorizers(
        Planning $planning,
        Project $project,
        ProjectUGroup $user_group = null
    ) {
        $planning_prioritizers = array();
        $prioritizers          = $this->planning_permissions_manager->getGroupIdsWhoHasPermissionOnPlanning(
            $planning->getId(),
            $planning->getGroupId(),
            PlanningPermissionsManager::PERM_PRIORITY_CHANGE
        );

        if (count($prioritizers) === 0) {
            $default_permissions = $this->permissions_manager->getDefaults(PlanningPermissionsManager::PERM_PRIORITY_CHANGE);
            $prioritizers        = $this->extractDefaultPermissions($default_permissions);
        }

        if ($user_group && ! in_array($user_group->getId(), $prioritizers)) {
            return;
        }

        foreach ($prioritizers as $user_group_id) {
            $planning_prioritizers[] = $this->formatter->formatGroup(
                $project,
                $user_group_id
            );
        }

        return $planning_prioritizers;
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

    private function extractDefaultPermissions(DataAccessResult $default_granted_ugroups)
    {
        $default_granted_ugroups_list = array();

        foreach ($default_granted_ugroups as $ugroup) {
            $default_granted_ugroups_list[] = $ugroup['ugroup_id'];
        }

        return $default_granted_ugroups_list;
    }
}
