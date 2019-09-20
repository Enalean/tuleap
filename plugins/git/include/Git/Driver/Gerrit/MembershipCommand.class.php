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
 * I'm responsible of managing propagation of user membership changes (add/remove) to gerrit.
 */
abstract class Git_Driver_Gerrit_MembershipCommand
{
    /** @var Git_Driver_Gerrit_MembershipManager */
    protected $membership_manager;

    /** @var Git_Driver_Gerrit_GerritDriverFactory */
    protected $driver_factory;
    /** @var ProjectUGroup */
    protected $ugroup;

    public function __construct(
        Git_Driver_Gerrit_MembershipManager $membership_manager,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        ProjectUGroup $ugroup
    ) {
        $this->membership_manager = $membership_manager;
        $this->driver_factory     = $driver_factory;
        $this->ugroup             = $ugroup;
    }

    public function getUGroup()
    {
        return $this->ugroup;
    }

    abstract public function execute(Git_RemoteServer_GerritServer $server);

    protected function getDriver(Git_RemoteServer_GerritServer $server)
    {
        return $this->driver_factory->getDriver($server);
    }
}
