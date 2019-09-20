<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_Permission_PermissionSetter
{
    private $group_id;
    private $atid;
    private $stored_ugroups_permissions;

    private $feedback_already_given = false;

    /** @var PermissionsManager */
    private $permissions_manager;

    public function __construct(Tracker $tracker, array $stored_ugroups_permissions, PermissionsManager $permissions_manager)
    {
        $this->group_id                   = $tracker->getGroupId();
        $this->atid                       = $tracker->getId();
        $this->tracker                    = $tracker;
        $this->stored_ugroups_permissions = $stored_ugroups_permissions;
        $this->permissions_manager        = $permissions_manager;
    }

    public function getTracker()
    {
        return $this->tracker;
    }

    public function getAllGroupIds()
    {
        return array_keys($this->stored_ugroups_permissions);
    }

    public function getUGroupName($ugroup_id)
    {
        return $this->stored_ugroups_permissions[$ugroup_id]['ugroup']['name'];
    }

    /**
     * Clean set of one permission (will revoke all other perms before granting)
     *
     * @param String  $permission_type
     * @param int $ugroup_id
     */
    public function grant($permission_type, $ugroup_id)
    {
        if (! $this->groupHasPermission($permission_type, $ugroup_id)) {
            $this->revokeAll($ugroup_id);
            $this->grantAccess($permission_type, $ugroup_id);
        }
    }

    /**
     * Only grant a permission, no revoke before hand
     *
     * @param type $permission_type
     * @param type $ugroup_id
     */
    public function grantAccess($permission_type, $ugroup_id)
    {
        if (! $this->groupHasPermission($permission_type, $ugroup_id)) {
            $this->permissions_manager->addPermission($permission_type, $this->atid, $ugroup_id);
            $this->stored_ugroups_permissions[$ugroup_id]['permissions'][$permission_type] = 1;
            $this->addHistory($permission_type);
        }
    }

    public function revokeAccess($permission_type, $ugroup_id)
    {
        if ($this->groupHasPermission($permission_type, $ugroup_id)) {
            $this->permissions_manager->revokePermissionForUGroup($permission_type, $this->atid, $ugroup_id);
            unset($this->stored_ugroups_permissions[$ugroup_id]['permissions'][$permission_type]);
            $this->addHistory($permission_type);
        }
    }

    private function addHistory($permission_type)
    {
        $this->permissions_manager->addHistory($permission_type, $this->atid, $this->group_id);
        $this->feedback();
    }

    private function feedback()
    {
        if (! $this->feedback_already_given) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_userperms', 'perm_upd'));
            $this->feedback_already_given = true;
        }
    }

    public function groupHasPermission($permission_type, $ugroup_id)
    {
        return isset($this->stored_ugroups_permissions[$ugroup_id]['permissions'][$permission_type]);
    }

    public function anonymousHaveFullAccess()
    {
        return $this->groupHasPermission(Tracker::PERMISSION_FULL, ProjectUGroup::ANONYMOUS);
    }

    public function registeredHaveFullAccess()
    {
        return $this->groupHasPermission(Tracker::PERMISSION_FULL, ProjectUGroup::REGISTERED);
    }

    public function revokeAll($ugroup_id)
    {
        $this->revokeAccess(Tracker::PERMISSION_FULL, $ugroup_id);
        $this->revokeAccess(Tracker::PERMISSION_ASSIGNEE, $ugroup_id);
        $this->revokeAccess(Tracker::PERMISSION_SUBMITTER, $ugroup_id);
        $this->revokeAccess(Tracker::PERMISSION_SUBMITTER_ONLY, $ugroup_id);
        $this->revokeAccess(Tracker::PERMISSION_ADMIN, $ugroup_id);
    }
}
