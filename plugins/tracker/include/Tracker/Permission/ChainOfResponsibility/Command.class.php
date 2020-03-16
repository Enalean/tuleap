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

/**
 * I am a command in a chain of responsability
 * I apply permissions on users or groups of users
 *
 * @see http://en.wikipedia.org/wiki/Chain-of-responsibility_pattern
 */
abstract class Tracker_Permission_Command
{

    public const PERMISSION_PREFIX = 'permissions_';

    public const PERMISSION_ADMIN                  = 'ADMIN';
    public const PERMISSION_FULL                   = 'FULL';
    public const PERMISSION_ASSIGNEE               = 'ASSIGNEE';
    public const PERMISSION_SUBMITTER              = 'SUBMITTER';
    public const PERMISSION_ASSIGNEE_AND_SUBMITTER = 'SUBMITTER_N_ASSIGNEE';
    public const PERMISSION_NONE                   = 'NONE';
    public const PERMISSION_SUBMITTER_ONLY         = 'SUBMITTER_ONLY';

    protected static $non_admin_permissions = array(
        Tracker_Permission_Command::PERMISSION_FULL,
        Tracker_Permission_Command::PERMISSION_ASSIGNEE,
        Tracker_Permission_Command::PERMISSION_SUBMITTER,
        Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
        Tracker_Permission_Command::PERMISSION_ASSIGNEE_AND_SUBMITTER
    );

    /** @var Tracker_Permission_Command */
    private $next_command;

    public function __construct()
    {
        $this->setNextCommand(new Tracker_Permission_ChainOfResponsibility_DoNothing());
    }

    public function setNextCommand(Tracker_Permission_Command $next_command)
    {
        $this->next_command = $next_command;
    }

    public function getNextCommand()
    {
        return $this->next_command;
    }

    public function applyNextCommand(Tracker_Permission_PermissionRequest $request, Tracker_Permission_PermissionSetter $permissions_setter)
    {
        $this->next_command->apply($request, $permissions_setter);
    }

    abstract public function apply(Tracker_Permission_PermissionRequest $request, Tracker_Permission_PermissionSetter $permissions_setter);

    protected function revokeAllButAdmin(
        Tracker_Permission_PermissionRequest $request,
        Tracker_Permission_PermissionSetter $permission_setter,
        $ugroup_id
    ) {
        if ($this->requestContainsNonAdminPermissions($request, $ugroup_id)) {
            $this->warnAlreadyHaveFullAccess($permission_setter, $ugroup_id);
            $request->revoke($ugroup_id);
            $this->revokeNonAdmin($permission_setter, $ugroup_id);
        }
    }

    private function requestContainsNonAdminPermissions(Tracker_Permission_PermissionRequest $request, $ugroup_id)
    {
        return in_array($request->getPermissionType($ugroup_id), self::$non_admin_permissions);
    }

    private function revokeNonAdmin(Tracker_Permission_PermissionSetter $permission_setter, $ugroup_id)
    {
        $permission_setter->revokeAccess(Tracker::PERMISSION_FULL, $ugroup_id);
        $permission_setter->revokeAccess(Tracker::PERMISSION_ASSIGNEE, $ugroup_id);
        $permission_setter->revokeAccess(Tracker::PERMISSION_SUBMITTER, $ugroup_id);
        $permission_setter->revokeAccess(Tracker::PERMISSION_SUBMITTER_ONLY, $ugroup_id);
    }

    protected function warnAlreadyHaveFullAccess(Tracker_Permission_PermissionSetter $permission_setter, $ugroup_id)
    {
        // eventually do something here
    }
}
