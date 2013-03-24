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
require_once GIT_BASE_DIR .'/Git/Driver/Gerrit/MembershipCommand.class.php';

class Git_Driver_Gerrit_MembershipCommand_AddUser extends Git_Driver_Gerrit_MembershipCommand {

    protected function propagateToGerrit(Git_RemoteServer_GerritServer $server, PFUser $user, $group_full_name) {
        $this->driver->addUserToGroup($server, $user, $group_full_name);
    }

    protected function isUserConcernedByPermission(PFUser $user, Project $project, $groups) {
        return $this->isUserInGroups($user, $project, $groups);
    }

}
?>
