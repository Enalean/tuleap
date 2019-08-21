<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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

use Tuleap\User\GroupCannotRemoveLastAdministrationPermission;
use Tuleap\User\ForgeUserGroupPermission\SiteAdministratorPermissionChecker;

class User_ForgeUserGroupManager
{

    /**
     * @var UserGroupDao
     */
    private $dao;
    /**
     * @var SiteAdministratorPermissionChecker
     */
    private $permission_checker;

    public function __construct(UserGroupDao $dao, SiteAdministratorPermissionChecker $permission_checker)
    {
        $this->dao                = $dao;
        $this->permission_checker = $permission_checker;
    }

    /**
     * @return bool
     */
    public function deleteForgeUserGroup(User_ForgeUGroup $user_group)
    {
        if ($this->permission_checker->checkUGroupIsNotTheOnlyOneWithPlatformAdministrationPermission($user_group)) {
            throw new GroupCannotRemoveLastAdministrationPermission();
        }
        return $this->dao->deleteForgeUGroup($user_group->getId());
    }

    /**
     * @return bool
     * @throws User_UserGroupNotFoundException
     * @throws User_UserGroupNameInvalidException
     */
    public function updateUserGroup(User_ForgeUGroup $user_group)
    {
        $row = $this->dao->getForgeUGroup($user_group->getId());
        if (! $row) {
            throw new User_UserGroupNotFoundException($user_group->getId());
        }

        if (! $this->userGroupHasModifications($user_group, $row)) {
            return true;
        }

        return $this->dao->updateForgeUGroup(
            $user_group->getId(),
            $user_group->getName(),
            $user_group->getDescription()
        );
    }

    private function userGroupHasModifications(User_ForgeUGroup $user_group, $row)
    {
        return $user_group->getName() != $row['name'] ||
            $user_group->getDescription() != $row['description'];
    }
}
