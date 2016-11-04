<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\AdminDelegation;

class AdminDelegationPresenter
{
    public $admindelegation_title;
    public $admindelegation_user;
    public $admindelegation_permission;
    public $has_users;
    public $users;
    public $no_permissions_granted;
    public $no_permissions_granted_next_part;
    public $add_grant_permission;
    public $delegation_table_title;
    public $admindelegation_avatar;
    public $cancel_grant_permission;
    public $label_grant_permission;
    public $label_username;
    public $services;

    public function __construct(array $users, array $services)
    {
        $this->has_users = count($users) > 0;
        $this->users     = $users;
        $this->services  = $services;

        $this->admindelegation_title = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'permissions_page_title'
        );

        $this->admindelegation_user       = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'permissions_user_col'
        );
        $this->admindelegation_permission = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'permissions_service_col'
        );

        $this->no_permissions_granted = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'no_permissions_granted'
        );

        $this->no_permissions_granted_next_part = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'no_permissions_granted_next_part'
        );

        $this->add_grant_permission = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'add_grant_permission'
        );

        $this->delegation_table_title = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'delegation_table_title'
        );

        $this->admindelegation_avatar = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'admindelegation_avatar'
        );

        $this->cancel_grant_permission = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'cancel_grant_permission'
        );

        $this->label_grant_permission = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'label_grant_permission'
        );

        $this->label_username = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'label_username'
        );

        $this->revoke_permissions = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'revoke_permissions'
        );

        $this->modal_revoke_warning = $GLOBALS['Language']->getText(
            'plugin_admindelegation',
            'modal_revoke_warning'
        );
    }
}
