<?php
/**
 * Copyright Ericsson AB (c) 2018. All rights reserved.
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

namespace Tuleap\LDAP;

use ForgeConfig;

/**
 * This class will silently discard any notifications sent through it
 * */
class GroupSyncSilentNotificationsManager implements GroupSyncNotificationsManager
{
    /**
     * @param $project   Project subject to the sync
     * @param $to_add    an array of user IDs to be added
     * @param $to_remove an array of suer IDs to be removed
     * @return Void
     * */
    public function sendNotifications(\Project $project, array $to_add, array $to_remove)
    {
        // Do nothing
    }
}
