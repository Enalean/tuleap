<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
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

/**
 * Description of AddBinding
 *
 * @author manuel
 */
class Git_Driver_Gerrit_MembershipCommand_RemoveBinding {
    private $membership_manager;
    private $driver;
    private $ugroup;

    public function __construct(Git_Driver_Gerrit_MembershipManager $membership_manager, Git_Driver_Gerrit $driver, UGroup $ugroup) {
        $this->membership_manager = $membership_manager;
        $this->driver = $driver;
        $this->ugroup = $ugroup;
    }

    public function getUGroup() {
        return $this->ugroup;
    }

    public function execute(Git_RemoteServer_GerritServer $server) {
        $group_name = $this->membership_manager->getFullyQualifiedUGroupName($this->ugroup);
        $this->driver->removeAllIncludedGroups($server, $group_name);
        $previous_source_group = $this->ugroup->getSourceGroup();
        if ($previous_source_group) {
            foreach ($previous_source_group->getMembers() as $user) {
                $this->membership_manager->addUserToGroup($user, $this->ugroup);
            }
        }
    }
}

?>
