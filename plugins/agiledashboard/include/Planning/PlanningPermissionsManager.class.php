<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

class PlanningPermissionsManager
{

    public const PERM_PRIORITY_CHANGE = 'PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE';

    public function getPlanningPermissionForm($planning_id, $group_id, $permission, $html_element_name)
    {
        return permission_fetch_selection_field($permission, $planning_id, $group_id, $html_element_name);
    }

    public function getGroupIdsWhoHasPermissionOnPlanning($planning_id, $group_id, $permission)
    {
        return permission_fetch_selected_ugroups_ids($permission, $planning_id, $group_id);
    }

    public function userHasPermissionOnPlanning($planning_id, $group_id, PFUser $user, $permission)
    {
        return $user->isMember($group_id) && $user->hasPermission($permission, $planning_id, $group_id);
    }

    public function savePlanningPermissionForUgroups($planning_id, $group_id, $permission, $ugroup_ids)
    {
        if (empty($ugroup_ids)) {
            $ugroup_ids = [];
        }

        /** @psalm-suppress DeprecatedFunction */
        return permission_process_selection_form($group_id, $permission, $planning_id, $ugroup_ids);
    }
}
