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

class Admin_PermissionDelegationGroupModalPresenter {

    /**
     * @var int
     */
    private $group_id;

    /**
     * @var string
     */
    private $group_name;

    /**
     * @var string
     */
    private $group_description;

    /**
     * @var boolean
     */
    private $is_new;


    public function __construct(User_ForgeUGroup $group = null) {
        $this->is_new = true;

        if ($group) {
            $this->group_id          = $group->getId();
            $this->group_name        = $group->getName();
            $this->group_description = $group->getDescription();
            $this->is_new            = false;
        }
    }

    public function group_title() {
        if ($this->is_new) {
            return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_title_create');
        }

        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_title_edit', array($this->group_name));
    }

    public function group_name_label() {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_name');
    }

    public function group_description_label() {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_description');
    }

    public function group_id() {
        return $this->group_id;
    }

    public function group_name() {
        return $this->group_name;
    }

    public function group_description() {
        return $this->group_description;
    }

    public function is_new() {
        return $this->is_new;
    }

    public function group_submit() {
        if ($this->is_new) {
            return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_submit_create');
        }

        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_submit_edit');
    }

    public function group_cancel() {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_cancel');
    }

}
