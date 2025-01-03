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

/**
 * When someone grant full access to registered users, it automatically remove
 * the permissions (except admin) for other groups (except anonymous) as they are included into
 * registered
 */
class Tracker_Permission_ChainOfResponsibility_PermissionsOfRegistered extends Tracker_Permission_Command
{
    public function apply(Tracker_Permission_PermissionRequest $request, Tracker_Permission_PermissionSetter $permissions_setter)
    {
        switch ($request->getPermissionType(ProjectUGroup::REGISTERED)) {
            case Tracker_Permission_Command::PERMISSION_FULL:
                if (! $permissions_setter->groupHasPermission(Tracker::PERMISSION_FULL, ProjectUGroup::REGISTERED)) {
                    $permissions_setter->grant(Tracker::PERMISSION_FULL, ProjectUGroup::REGISTERED);
                }
                foreach ($permissions_setter->getAllGroupIds() as $stored_ugroup_id) {
                    if ($stored_ugroup_id !== ProjectUGroup::ANONYMOUS && $stored_ugroup_id !== ProjectUGroup::REGISTERED) {
                        $this->revokeAllButAdmin($request, $permissions_setter, $stored_ugroup_id);
                    }
                }
                break;

            case Tracker_Permission_Command::PERMISSION_NONE:
                $permissions_setter->revokeAll(ProjectUGroup::REGISTERED);
                break;

            case Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY:
                $permissions_setter->grant(Tracker::PERMISSION_SUBMITTER_ONLY, ProjectUGroup::REGISTERED);
                break;
        }

        $this->applyNextCommand($request, $permissions_setter);
    }

    protected function warnAlreadyHaveFullAccess(Tracker_Permission_PermissionSetter $permission_setter, $ugroup_id)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::WARN,
            $GLOBALS['Language']->getText(
                'tracker_admin_permissions',
                'tracker_ignore_g_regis_full',
                [
                    $permission_setter->getUGroupName($ugroup_id),
                    $permission_setter->getUGroupName(ProjectUGroup::REGISTERED),
                ]
            )
        );
    }
}
