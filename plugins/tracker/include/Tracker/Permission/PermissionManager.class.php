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

class Tracker_Permission_PermissionManager
{

    public function save(Tracker_Permission_PermissionRequest $request, Tracker_Permission_PermissionSetter $permission_setter)
    {
        $tracker = $permission_setter->getTracker();

        if ($this->checkPermissionValidity($request, $tracker)) {
            $this->getChainOfResponsability()->apply($request, $permission_setter);

            EventManager::instance()->processEvent(
                TRACKER_EVENT_TRACKER_PERMISSIONS_CHANGE,
                array(
                    'tracker' => $tracker,
                )
            );
        }
    }

    private function getChainOfResponsability()
    {
        $anonymous_command  = new Tracker_Permission_ChainOfResponsibility_PermissionsOfAnonymous();
        $registered_command = new Tracker_Permission_ChainOfResponsibility_PermissionsOfRegistered();
        $ugroup_command     = new Tracker_Permission_ChainOfResponsibility_PermissionsOfAllGroups();

        $anonymous_command->setNextCommand($registered_command);
        $registered_command->setNextCommand($ugroup_command);

        return $anonymous_command;
    }

    private function checkPermissionValidity(Tracker_Permission_PermissionRequest $request, Tracker $tracker)
    {
        if ($request->containsPermissionType(Tracker_Permission_Command::PERMISSION_ASSIGNEE) != null && $tracker->getContributorField() === null) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_admin_permissions',
                    'no_assignee_semantic',
                    array(
                        TRACKER_BASE_URL . '/?' .  http_build_query(array('func' => 'admin-semantic', 'tracker' => $tracker->getId())),
                        $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'contributor_label')
                    )
                ),
                CODENDI_PURIFIER_DISABLED
            );
            return false;
        }
        if ($request->getPermissionType(ProjectUGroup::PROJECT_ADMIN)) {
            return false;
        }
        return true;
    }
}
