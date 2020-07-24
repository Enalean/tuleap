<?php
/**
  * Copyright (c) Enalean, 2014 - 2017. All rights reserved
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

class Admin_PermissionDelegationDeleteGroupModalPresenter
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;


    public function __construct(UserForgeUGroupPresenter $group)
    {
        $this->id   = $group->id;
        $this->name = $group->name;
    }

    public function group_title_delete()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_title_delete', [$this->name]);
    }

    public function purified_group_delete_confirmation()
    {
        return Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText('admin_permission_delegation', 'group_delete_confirmation', $this->name),
            CODENDI_PURIFIER_LIGHT
        );
    }

    public function id()
    {
        return $this->id;
    }

    public function group_submit_delete()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_submit_delete');
    }

    public function group_cancel_delete()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_cancel_delete');
    }
}
