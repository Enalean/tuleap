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

class Tracker_Action_AdminUpdateTrackerPermissions {
    private $tracker;

    public function __construct(Tracker $tracker) {
        $this->tracker = $tracker;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user) {
        $this->getChainOfResponsability()->execute(
            $request,
            $this->getPermissionSetter()
        );

        $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId().'&func=admin-perms-tracker');
    }

    /**
     * Order matters as we have to deal with anonymous -> registered before
     * going any further
     *
     * @return \Tracker_Permission_ChainOfResponsibility_Anonymous
     */
    private function getChainOfResponsability() {
        $anonymous_command  = new Tracker_Permission_ChainOfResponsibility_Anonymous();
        $registered_command = new Tracker_Permission_ChainOfResponsibility_Registered();
        $ugroup_command     = new Tracker_Permission_ChainOfResponsibility_AllGroups();

        $anonymous_command->setNextCommand($registered_command);
        $registered_command->setNextCommand($ugroup_command);

        return $anonymous_command;
    }

    private function getPermissionSetter() {
        return new Tracker_Permission_PermissionSetter(
            $this->tracker,
            plugin_tracker_permission_get_tracker_ugroups_permissions(
                $this->tracker->getGroupId(),
                $this->tracker->getId()
            )
        );
    }
}


