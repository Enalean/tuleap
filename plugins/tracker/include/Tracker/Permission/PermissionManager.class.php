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

class Tracker_Permission_PermissionManager {

    public function save(Tracker_Permission_PermissionRequest $request, Tracker_Permission_PermissionSetter $permission_setter) {
        $this->getChainOfResponsability()->execute($request, $permission_setter);
    }

    private function getChainOfResponsability() {
        $anonymous_command  = new Tracker_Permission_ChainOfResponsibility_Anonymous();
        $registered_command = new Tracker_Permission_ChainOfResponsibility_Registered();
        $ugroup_command     = new Tracker_Permission_ChainOfResponsibility_AllGroups();

        $anonymous_command->setNextCommand($registered_command);
        $registered_command->setNextCommand($ugroup_command);

        return $anonymous_command;
    }
}
