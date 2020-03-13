<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013, 2014. All rights reserved.
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

require_once GIT_BASE_DIR . '/Git/Driver/Gerrit/MembershipCommand.class.php';

class Git_Driver_Gerrit_MembershipCommand_RemoveBinding extends Git_Driver_Gerrit_MembershipCommand
{

    public function execute(Git_RemoteServer_GerritServer $server)
    {
        $driver     = $this->getDriver($server);
        $group_name = $this->membership_manager->getFullyQualifiedUGroupName($this->ugroup);
        $driver->removeAllIncludedGroups($server, $group_name);
        $previous_source_group = $this->ugroup->getSourceGroup();
        if ($previous_source_group) {
            foreach ($previous_source_group->getMembers() as $user) {
                $this->membership_manager->addUserToGroup($user, $this->ugroup);
            }
        }
    }
}
