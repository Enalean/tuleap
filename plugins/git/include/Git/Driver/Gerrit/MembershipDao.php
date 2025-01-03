<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
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

use Tuleap\DB\DataAccessObject;

class Git_Driver_Gerrit_MembershipDao extends DataAccessObject
{
    public function addReference($group_id, $ugroup_id, $remote_server_id)
    {
        $sql = 'INSERT INTO plugin_git_remote_ugroups(group_id, ugroup_id, remote_server_id)
                VALUES (?, ?, ?)';

        $this->getDB()->run($sql, $group_id, $ugroup_id, $remote_server_id);
    }
}
