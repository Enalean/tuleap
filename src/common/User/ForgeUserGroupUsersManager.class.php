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

class User_ForgeUserGroupUsersManager
{

    /** @var User_ForgeUserGroupUsersDao */
    private $users_dao;

    public function __construct(User_ForgeUserGroupUsersDao $users_dao)
    {
        $this->users_dao = $users_dao;
    }

    public function addUserToForgeUserGroup(PFUser $user, User_ForgeUGroup $user_group)
    {
        if ($this->userIsAlreadyInTheGroup($user, $user_group)) {
            return true;
        }
        return $this->users_dao->addUserToForgeUserGroup($user->getId(), $user_group->getId());
    }

    public function removeUserFromForgeUserGroup(PFUser $user, User_ForgeUGroup $user_group)
    {
        return $this->users_dao->removeUserFromForgeUserGroup($user->getId(), $user_group->getId());
    }

    public function userIsAlreadyInTheGroup(PFUser $user, User_ForgeUGroup $user_group)
    {
        return $this->users_dao->isUserInGroup($user->getId(), $user_group->getId());
    }
}
