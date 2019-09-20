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

abstract class Git_Driver_Gerrit_MembershipCommand_User extends Git_Driver_Gerrit_MembershipCommand
{
    private $autoflush = true;
    private $gerrit_user_manager;
    protected $user;

    public function __construct(
        Git_Driver_Gerrit_MembershipManager $membership_manager,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        Git_Driver_Gerrit_UserAccountManager $gerrit_user_manager,
        ProjectUGroup $ugroup,
        PFUser $user
    ) {
        parent::__construct($membership_manager, $driver_factory, $ugroup);
        $this->gerrit_user_manager = $gerrit_user_manager;
        $this->user                = $user;
    }

    public function disableAutoFlush()
    {
        $this->autoflush = false;
    }

    public function execute(Git_RemoteServer_GerritServer $server)
    {
        $driver      = $this->getDriver($server);
        $gerrit_user = $this->gerrit_user_manager->getGerritUser($this->user);
        if ($gerrit_user) {
            $this->executeForGerritUser($server, $gerrit_user);
            if ($this->autoflush) {
                $driver->flushGerritCacheAccounts($server);
            }
        }
    }

    abstract protected function executeForGerritUser(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $gerrit_user);
}
