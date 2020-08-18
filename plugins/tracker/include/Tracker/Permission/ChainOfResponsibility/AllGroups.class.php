<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

class Tracker_Permission_ChainOfResponsibility_PermissionsOfAllGroups extends Tracker_Permission_Command
{

    public function apply(Tracker_Permission_PermissionRequest $request, Tracker_Permission_PermissionSetter $permissions_setter)
    {
        foreach ($permissions_setter->getAllGroupIds() as $ugroup_id) {
            if ($this->ugroupHasOwnCommand($ugroup_id)) {
                continue;
            }

            $this->adjustPermissionsForGroup($permissions_setter, $ugroup_id, $request->getPermissionType($ugroup_id));
        }

        $this->applyNextCommand($request, $permissions_setter);
    }

    private function ugroupHasOwnCommand($ugroup_id)
    {
        return ($ugroup_id == ProjectUGroup::ANONYMOUS || $ugroup_id == ProjectUGroup::REGISTERED);
    }

    private function adjustPermissionsForGroup(Tracker_Permission_PermissionSetter $permission_setter, $ugroup_id, $permission_type)
    {
        switch ($permission_type) {
            case Tracker_Permission_Command::PERMISSION_FULL:
                $permission_setter->grant(Tracker::PERMISSION_FULL, $ugroup_id);
                break;

            case Tracker_Permission_Command::PERMISSION_ASSIGNEE:
                if ($this->canSetAssignee($permission_setter, $ugroup_id)) {
                    $permission_setter->grant(Tracker::PERMISSION_ASSIGNEE, $ugroup_id);
                }
                break;

            case Tracker_Permission_Command::PERMISSION_SUBMITTER:
                if ($this->canSetSubmitter($permission_setter, $ugroup_id)) {
                    $permission_setter->grant(Tracker::PERMISSION_SUBMITTER, $ugroup_id);
                }
                break;

            case Tracker_Permission_Command::PERMISSION_ASSIGNEE_AND_SUBMITTER:
                if ($this->canSetSubmitterAndAssignee($permission_setter, $ugroup_id)) {
                    $permission_setter->revokeAll($ugroup_id);
                    $permission_setter->grantAccess(Tracker::PERMISSION_ASSIGNEE, $ugroup_id);
                    $permission_setter->grantAccess(Tracker::PERMISSION_SUBMITTER, $ugroup_id);
                }
                break;

            case Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY:
                $permission_setter->grant(Tracker::PERMISSION_SUBMITTER_ONLY, $ugroup_id);
                break;

            case Tracker_Permission_Command::PERMISSION_ADMIN:
                $permission_setter->grant(Tracker::PERMISSION_ADMIN, $ugroup_id);
                break;

            case Tracker_Permission_Command::PERMISSION_NONE:
                $permission_setter->revokeAll($ugroup_id);
                break;
        }
    }

    private function canSetAssignee(Tracker_Permission_PermissionSetter $permission_setter, $ugroup_id)
    {
        return ! $permission_setter->groupHasPermission(Tracker::PERMISSION_ASSIGNEE, $ugroup_id) ||
           ($permission_setter->groupHasPermission(Tracker::PERMISSION_ASSIGNEE, $ugroup_id) && $permission_setter->groupHasPermission(Tracker::PERMISSION_SUBMITTER, $ugroup_id));
    }

    private function canSetSubmitter(Tracker_Permission_PermissionSetter $permission_setter, $ugroup_id)
    {
        return ! $permission_setter->groupHasPermission(Tracker::PERMISSION_SUBMITTER, $ugroup_id) ||
           ($permission_setter->groupHasPermission(Tracker::PERMISSION_ASSIGNEE, $ugroup_id) && $permission_setter->groupHasPermission(Tracker::PERMISSION_SUBMITTER, $ugroup_id));
    }

    private function canSetSubmitterAndAssignee(Tracker_Permission_PermissionSetter $permission_setter, $ugroup_id)
    {
        return ! ($permission_setter->groupHasPermission(Tracker::PERMISSION_SUBMITTER, $ugroup_id) && $permission_setter->groupHasPermission(Tracker::PERMISSION_ASSIGNEE, $ugroup_id));
    }
}
