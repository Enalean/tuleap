<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

class Tracker_Permission_PermissionPresenterBuilder
{
    public function getPresenter(Tracker $tracker)
    {
        return new Tracker_Permission_PermissionPresenter($tracker, $this->getUGroupList($tracker));
    }

    private function getUGroupList(Tracker $tracker)
    {
        $ugroup_list = [];

        $ugroups_permissions = plugin_tracker_permission_get_tracker_ugroups_permissions($tracker->getGroupId(), $tracker->getId());
        ksort($ugroups_permissions);
        reset($ugroups_permissions);
        foreach ($ugroups_permissions as $ugroup_permissions) {
            $ugroup      = $ugroup_permissions['ugroup'];
            $permissions = $ugroup_permissions['permissions'];

            if ($ugroup['id'] != ProjectUGroup::PROJECT_ADMIN) {
                $ugroup_list[] = new Tracker_Permission_PermissionUgroupPresenter(
                    $ugroup['id'],
                    $ugroup['name'],
                    isset($ugroup['link']) ? $ugroup['link'] : '',
                    $this->getPermissionTypeList($ugroup['id'], $permissions)
                );
            }
        }

        return $ugroup_list;
    }

    private function getPermissionTypeList($ugroup_id, $permissions)
    {
        $permission_type_list = [];

        $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
            Tracker_Permission_Command::PERMISSION_NONE,
            dgettext('tuleap-tracker', '-'),
            count($permissions) == 0
        );

        $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
            Tracker_Permission_Command::PERMISSION_FULL,
            dgettext('tuleap-tracker', 'have access to all artifacts'),
            isset($permissions[Tracker::PERMISSION_FULL])
        );

        if ($ugroup_id != ProjectUGroup::ANONYMOUS) {
            $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
                Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
                dgettext('tuleap-tracker', 'have access to artifacts they submitted'),
                isset($permissions[Tracker::PERMISSION_SUBMITTER_ONLY])
            );

            if ($ugroup_id != ProjectUGroup::REGISTERED) {
                $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
                    Tracker_Permission_Command::PERMISSION_ASSIGNEE,
                    dgettext('tuleap-tracker', 'have access to artifacts assigned to group'),
                    (isset($permissions[Tracker::PERMISSION_ASSIGNEE]) && ! isset($permissions[Tracker::PERMISSION_SUBMITTER]))
                );

                $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
                    Tracker_Permission_Command::PERMISSION_SUBMITTER,
                    dgettext('tuleap-tracker', 'have access to artifacts submitted by group'),
                    ! isset($permissions[Tracker::PERMISSION_ASSIGNEE]) && isset($permissions[Tracker::PERMISSION_SUBMITTER])
                );

                $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
                    Tracker_Permission_Command::PERMISSION_ASSIGNEE_AND_SUBMITTER,
                    dgettext('tuleap-tracker', 'have access to artifacts assigned to or submitted by group'),
                    isset($permissions[Tracker::PERMISSION_ASSIGNEE]) && isset($permissions[Tracker::PERMISSION_SUBMITTER])
                );

                $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
                    Tracker_Permission_Command::PERMISSION_ADMIN,
                    dgettext('tuleap-tracker', 'are admin of the tracker'),
                    isset($permissions[Tracker::PERMISSION_ADMIN])
                );
            }
        }

        return $permission_type_list;
    }
}
