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

class Admin_PermissionDelegationPermissionsModalPresenter
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var User_ForgeUserGroupPermission[]
     */
    private $permissions;


    public function __construct($group, array $permissions)
    {
        $this->id          = $group->id;
        $this->name        = $group->name;
        $this->permissions = $permissions;

        $this->sortPermissionsAlphabetically();
    }

    public function id()
    {
        return $this->id;
    }

    public function permissions_modal_title()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'permissions_modal_title', array($this->name));
    }

    public function purified_permissions_modal_description()
    {
        return Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText('admin_permission_delegation', 'permissions_modal_description', $this->name),
            CODENDI_PURIFIER_LIGHT
        );
    }

    public function has_permissions()
    {
        return count($this->permissions) > 0;
    }

    public function no_permission_to_add()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'no_permission_to_add');
    }

    public function permissions()
    {
        return $this->permissions;
    }

    public function permissions_modal_submit()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'permissions_modal_submit');
    }

    public function permissions_modal_cancel()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'permissions_modal_cancel');
    }

    private function sortPermissionsAlphabetically()
    {
        usort(
            $this->permissions,
            function (User_ForgeUserGroupPermission $permission_a, User_ForgeUserGroupPermission $permission_b) {
                return strnatcasecmp($permission_a->getName(), $permission_b->getName());
            }
        );
    }
}
