<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class User_ForgeUserGroupUsersFactory
{

    /** @var User_ForgeUserGroupUsersDao */
    private $users_dao;

    public function __construct(User_ForgeUserGroupUsersDao $users_dao)
    {
        $this->users_dao = $users_dao;
    }

    public function getAllUsersFromForgeUserGroup(User_ForgeUGroup $user_group)
    {
        $rows = $this->users_dao->getUsersByForgeUserGroupId($user_group->getId());

        if (! $rows) {
            return array();
        }

        return $rows->instanciateWith(array($this, 'instantiateFromRow'));
    }

    public function instantiateFromRow(array $row)
    {
        return new PFUser($row);
    }
}
