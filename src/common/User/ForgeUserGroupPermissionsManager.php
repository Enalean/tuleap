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

use Tuleap\User\ForgePermissionsRetriever;

class User_ForgeUserGroupPermissionsManager implements ForgePermissionsRetriever // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var User_ForgeUserGroupPermissionsDao
     */
    private $permissions_dao;

    public function __construct(User_ForgeUserGroupPermissionsDao $dao)
    {
        $this->permissions_dao = $dao;
    }

    /**
     * @return bool
     */
    public function addPermission(User_ForgeUGroup $user_group, User_ForgeUserGroupPermission $permission)
    {
        $user_group_id = $user_group->getId();
        $permission_id = $permission->getId();

        if (! $this->permissions_dao->permissionExistsForUGroup($user_group_id, $permission_id)) {
            return $this->permissions_dao->addPermission($user_group_id, $permission_id);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function deletePermission(User_ForgeUGroup $user_group, User_ForgeUserGroupPermission $permission)
    {
        $user_group_id = $user_group->getId();
        $permission_id = $permission->getId();

        return $this->permissions_dao->deletePersmissionForUGroup($user_group_id, $permission_id);
    }

    public function doesUserHavePermission(PFUser $user, User_ForgeUserGroupPermission $permission): bool
    {
        return $this->permissions_dao->doesUserHavePermission($user->getId(), $permission->getId());
    }
}
