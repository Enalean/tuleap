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

class Admin_PermissionDelegationDeleteGroupModalPresenter {

    /**
     * @var int
     */
    private $group_id;

    /**
     * @var string
     */
    private $group_name;


    public function __construct(User_ForgeUGroup $group) {
        $this->group_id          = $group->getId();
        $this->group_name        = $group->getName();
    }

    public function group_title_delete() {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_title_delete', array($this->group_name));
    }

    public function group_delete_confirmation() {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_delete_confirmation', array($this->group_name));
    }

    public function group_id() {
        return $this->group_id;
    }

    public function group_submit_delete() {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_submit_delete');
    }

    public function group_cancel_delete() {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_cancel_delete');
    }

}
