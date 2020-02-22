<?php
/**
  * Copyright (c) Enalean, 2014-Present. All rights reserved
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

use Tuleap\User\ForgeUserGroupPermission\UserForgeUGroupPresenter;

class Admin_PermissionDelegationGroupPresenter
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var  Tuleap\admin\PermissionDelegation\PermissionPresenter[]
     */
    private $permissions;

    /**
     * @var array
     */
    private $users;

    /**
     * @var User_ForgeUGroup
     */
    private $group;

    public function __construct(UserForgeUGroupPresenter $group, array $permissions, $users)
    {
        $this->id          = $group->id;
        $this->permissions = $permissions;
        $this->users       = $users;
        $this->group       = $group;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function description_label()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_description');
    }

    public function group_action_edit()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_action_edit');
    }

    public function group_action_delete()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_action_delete');
    }

    public function permissions_title()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'permissions_title');
    }

    public function users_title()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'users_title');
    }

    public function permissions_list_title()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'permissions_list_title');
    }

    public function users_list_title()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'users_list_title');
    }

    public function permissions_action_add()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'permissions_action_add');
    }

    public function permissions_action_delete()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'permissions_action_delete');
    }

    public function users_action_add()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'users_action_add');
    }

    public function users_action_delete()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'users_action_delete');
    }

    public function has_permissions()
    {
        return count($this->permissions) > 0;
    }

    public function no_permission()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'no_permission');
    }

    public function permissions()
    {
        return $this->permissions;
    }

    public function has_users()
    {
        return count($this->users) > 0;
    }

    public function no_user()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'no_user');
    }

    public function users()
    {
        return $this->users;
    }
}
