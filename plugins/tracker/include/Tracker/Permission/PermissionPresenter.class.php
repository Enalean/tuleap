<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
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

class Tracker_Permission_PermissionPresenter
{
    private $tracker;
    private $ugroup_permissions;

    public function __construct(Tracker $tracker, array $ugroup_permissions)
    {
        $this->tracker            = $tracker;
        $this->ugroup_permissions = $ugroup_permissions;
    }

    public function title()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_type', 'manage_tracker_permissions');
    }

    public function intro()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'fields_tracker_intro');
    }

    public function form_url()
    {
        return '?' . http_build_query(array(
            'tracker' => $this->tracker->getId(),
            'func'    => 'admin-perms-tracker'
        ));
    }

    public function ugroup_title()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'ugroup');
    }

    public function permissions_title()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'permissions');
    }

    public function submit_permissions()
    {
        return $GLOBALS['Language']->getText('project_admin_permissions', 'submit_perm');
    }

    public function ugroup_permissions()
    {
        return $this->ugroup_permissions;
    }

    public function create_modify_ugroups()
    {
        return $GLOBALS['Language']->getText(
            'project_admin_permissions',
            'admins_create_modify_ug',
            array(
                '/project/admin/ugroup.php?group_id=' . (int) $this->tracker->getGroupID()
            )
        );
    }
}
