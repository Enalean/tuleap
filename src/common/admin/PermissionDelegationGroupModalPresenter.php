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

class Admin_PermissionDelegationGroupModalPresenter
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
     * @var string
     */
    private $description;

    /**
     * @var bool
     */
    private $is_new;


    public function __construct(?UserForgeUGroupPresenter $group = null)
    {
        $this->is_new = true;

        if ($group) {
            $this->id          = $group->id;
            $this->name        = $group->name;
            $this->description = $group->description;
            $this->is_new            = false;
        }
    }

    public function group_title()
    {
        if ($this->is_new) {
            return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_title_create');
        }

        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_title_edit', [$this->name]);
    }

    public function name_label()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_name');
    }

    public function description_label()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_description');
    }

    public function id()
    {
        return $this->id;
    }

    public function name()
    {
        return $this->name;
    }

    public function description()
    {
        return $this->description;
    }

    public function is_new()
    {
        return $this->is_new;
    }

    public function group_submit()
    {
        if ($this->is_new) {
            return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_submit_create');
        }

        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_submit_edit');
    }

    public function group_cancel()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_cancel');
    }
}
