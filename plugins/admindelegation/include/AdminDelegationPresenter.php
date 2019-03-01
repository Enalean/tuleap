<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

        $this->admindelegation_title = dgettext('tuleap-admindelegation', 'Admin rights delegation');

        $this->admindelegation_user       = dgettext('tuleap-admindelegation', 'User');
        $this->admindelegation_permission = dgettext('tuleap-admindelegation', 'Service');

        $this->no_permissions_granted = dgettext('tuleap-admindelegation', 'There is nothing here,');

        $this->no_permissions_granted_next_part = dgettext('tuleap-admindelegation', 'start by granting a permission to somebody.');

        $this->add_grant_permission = dgettext('tuleap-admindelegation', 'Grant permission');

        $this->delegation_table_title = dgettext('tuleap-admindelegation', 'Granted users');

        $this->admindelegation_avatar = dgettext('tuleap-admindelegation', 'Avatar');

        $this->cancel_grant_permission = dgettext('tuleap-admindelegation', 'Cancel');

        $this->label_grant_permission = dgettext('tuleap-admindelegation', 'Grant permissions');

        $this->label_username = dgettext('tuleap-admindelegation', 'To user');

        $this->revoke_permissions = dgettext('tuleap-admindelegation', 'Revoke permissions');

        $this->modal_revoke_warning = dgettext('tuleap-admindelegation', 'Wow, wait a minute. Your about to revoke permission. Please confirm your action.');
    }
}
