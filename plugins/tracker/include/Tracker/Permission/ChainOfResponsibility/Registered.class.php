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

/**
 * When someone grant full access to registered users, it automatically remove
 * the permissions (except admin) for other groups (except anonymous) as they are included into
 * registered
 */
class Tracker_Permission_ChainOfResponsibility_Registered extends Tracker_Permission_Command {

    public function execute(Codendi_Request $request, Tracker_Permission_PermissionSetter $permission_setter) {
        switch($request->get(self::PERMISSION_PREFIX.UGroup::REGISTERED)) {
        case Tracker::PERMISSION_ID_FULL:
            if ($permission_setter->anonymousHaveFullAccess()) {
                $anonymous_name  = $GLOBALS['Language']->getText('project_ugroup', ugroup_get_name_from_id(UGroup::ANONYMOUS));
                $registered_name = $GLOBALS['Language']->getText('project_ugroup', ugroup_get_name_from_id(UGroup::REGISTERED));
                $GLOBALS['Response']->addFeedback(Feedback::WARN, $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($registered_name, $anonymous_name)));
            } else {
                $permission_setter->grantAccess(Tracker::PERMISSION_FULL, UGroup::REGISTERED);
                foreach ($permission_setter->getAllGroupIds() as $stored_ugroup_id) {
                    if ($stored_ugroup_id !== UGroup::ANONYMOUS && $stored_ugroup_id !== UGroup::REGISTERED) {
                        $permission_setter->revokeAccess(Tracker::PERMISSION_FULL, $stored_ugroup_id);
                        $permission_setter->revokeAccess(Tracker::PERMISSION_ASSIGNEE, $stored_ugroup_id);
                        $permission_setter->revokeAccess(Tracker::PERMISSION_SUBMITTER, $stored_ugroup_id);
                    }
                }
            }
            break;

        case Tracker::PERMISSION_ID_NONE:
            $permission_setter->revokeAccess(Tracker::PERMISSION_FULL, UGroup::REGISTERED);
            break;
        }

        $this->executeNextCommand($request, $permission_setter);
    }
}
