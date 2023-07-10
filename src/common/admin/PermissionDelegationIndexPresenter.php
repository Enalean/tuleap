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
class Admin_PermissionDelegationIndexPresenter
{
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var Admin_PermissionDelegationGroupPresenter[]
     */
    private $groups = [];

    /**
     * @var Admin_PermissionDelegationGroupPresenter
     */
    private $current_group;

    /**
     * @var Admin_PermissionDelegationGroupModalPresenter
     */
    private $add_group;

    /**
     * @var Admin_PermissionDelegationDeleteGroupModalPresenter
     */
    private $delete_group;

    /**
     * @var Admin_PermissionDelegationGroupModalPresenter
     */
    private $edit_group;
    /**
     * @var Admin_PermissionDelegationPermissionsModalPresenter
     */
    private $add_perm_presenter;


    public function __construct(
        CSRFSynchronizerToken $csrf_token,
        array $groups,
        Admin_PermissionDelegationGroupModalPresenter $add_group,
        ?Admin_PermissionDelegationDeleteGroupModalPresenter $delete_group = null,
        ?Admin_PermissionDelegationGroupModalPresenter $edit_group = null,
        ?Admin_PermissionDelegationPermissionsModalPresenter $add_perm_presenter = null,
        ?Admin_PermissionDelegationGroupPresenter $current_group_presenter = null,
    ) {
        $this->csrf_token         = $csrf_token;
        $this->groups             = $groups;
        $this->current_group      = $current_group_presenter;
        $this->add_group          = $add_group;
        $this->delete_group       = $delete_group;
        $this->edit_group         = $edit_group;
        $this->add_perm_presenter = $add_perm_presenter;
    }

    public function page_title()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'page_title');
    }

    public function page_description()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'page_description');
    }

    public function group_action_add()
    {
        return $GLOBALS['Language']->getText('admin_permission_delegation', 'group_action_add');
    }

    public function has_groups()
    {
        return count($this->groups) > 0;
    }

    public function purified_no_group()
    {
        return Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText('admin_permission_delegation', 'no_group'),
            CODENDI_PURIFIER_LIGHT
        );
    }

    public function groups()
    {
        return $this->groups;
    }

    public function add_group()
    {
        return $this->add_group;
    }

    public function delete_group()
    {
        return $this->delete_group;
    }

    public function current_group()
    {
        return $this->current_group;
    }

    public function edit_group()
    {
        return $this->edit_group;
    }

    public function add_perm_presenter()
    {
        return $this->add_perm_presenter;
    }
}
