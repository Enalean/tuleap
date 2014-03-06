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

class Admin_PermissionDelegationIndexPresenter {

    /**
     * @var Admin_PermissionDelegationGroupPresenter[]
     */
    private $groups;

    /**
     * @var Admin_PermissionDelegationGroupPresenter
     */
    private $current_group;


    public function __construct() {
        $this->groups = array(
            new Admin_PermissionDelegationGroupPresenter('My group', null, false, array(), array()),
            new Admin_PermissionDelegationGroupPresenter('My second group', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Nemo, eveniet, accusantium, quos doloremque tempora voluptas veniam recusandae.', true, array(), array())
        );
        $this->current_group = $this->groups[1];
    }

    public function page_title() {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'page_title');
    }

    public function page_description() {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'page_description');
    }

    public function group_action_add() {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_action_add');
    }

    public function has_groups() {
        return count($this->groups) > 0;
    }

    public function no_group() {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'no_group');
    }

    public function groups() {
        return $this->groups;
    }

    public function current_group() {
        return $this->current_group;
    }

}
