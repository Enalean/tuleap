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

abstract class Git_Driver_Gerrit_MembershipCommand_User extends Git_Driver_Gerrit_MembershipCommand {
    private $gerrit_user_manager;
    protected $user;

    public function __construct(
        Git_Driver_Gerrit_MembershipManager $membership_manager,
        Git_Driver_Gerrit $driver,
        Git_Driver_Gerrit_UserAccountManager $gerrit_user_manager,
        UGroup $ugroup,
        PFUser $user
    ) {
        parent::__construct($membership_manager, $driver, $ugroup);
        $this->gerrit_user_manager = $gerrit_user_manager;
        $this->user                = $user;
    }

    /**
     * 
     * @param PFUser $user
     * 
     * @return Git_Driver_Gerrit_User
     */
    protected function getGerritUser(PFUser $user) {
        $ldap_user = null;
        $params    = array('ldap_user' => &$ldap_user, 'user' => $user);
        EventManager::instance()->processEvent(Event::GET_LDAP_LOGIN_NAME_FOR_USER, $params);
        if ($ldap_user) {
            return new Git_Driver_Gerrit_User($ldap_user);
        } else {
            return null;
        }
    }

    public function execute(Git_RemoteServer_GerritServer $server) {
        $gerrit_user = $this->gerrit_user_manager->getGerritUser($this->user);
        if ($gerrit_user) {
            $this->executeForGerritUser($server, $gerrit_user);
        }
    }

    abstract protected function executeForGerritUser(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $gerrit_user);
}

?>
