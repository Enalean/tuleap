<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\REST\Tracker;

use Tracker;
use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\Tracker\PermissionsFunctionsWrapper;

class PermissionsRepresentationBuilder
{
    /**
     * @var \UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var PermissionsFunctionsWrapper
     */
    private $permissions_functions_wrapper;

    public function __construct(\UGroupManager $ugroup_manager, PermissionsFunctionsWrapper $permissions_functions_wrapper)
    {
        $this->ugroup_manager = $ugroup_manager;
        $this->permissions_functions_wrapper = $permissions_functions_wrapper;
    }

    public function getPermissionsRepresentation(Tracker $tracker, \PFUser $user): ?PermissionsRepresentation
    {
        if ($tracker->userIsAdmin($user)) {
            $ugroups = $this->permissions_functions_wrapper->getTrackerUGroupsPermissions($tracker);
            $can_access = [];
            $can_access_submitted_by_user = [];
            $can_access_assigned_to_group = [];
            $can_access_submitted_by_group = [];
            $can_admin = [];
            foreach ($ugroups as $ugroup) {
                if (isset($ugroup['permissions'][Tracker::PERMISSION_ADMIN])) {
                    $this->addUserGroupRepresentation($can_admin, $tracker, $ugroup);
                }
                if (isset($ugroup['permissions'][Tracker::PERMISSION_FULL])) {
                    $this->addUserGroupRepresentation($can_access, $tracker, $ugroup);
                }
                if (isset($ugroup['permissions'][Tracker::PERMISSION_ASSIGNEE])) {
                    $this->addUserGroupRepresentation($can_access_assigned_to_group, $tracker, $ugroup);
                }
                if (isset($ugroup['permissions'][Tracker::PERMISSION_SUBMITTER])) {
                    $this->addUserGroupRepresentation($can_access_submitted_by_group, $tracker, $ugroup);
                }
                if (isset($ugroup['permissions'][Tracker::PERMISSION_SUBMITTER_ONLY])) {
                    $this->addUserGroupRepresentation($can_access_submitted_by_user, $tracker, $ugroup);
                }
            }
            $representation = new PermissionsRepresentation();
            $representation->build($can_access, $can_access_submitted_by_user, $can_access_assigned_to_group, $can_access_submitted_by_group, $can_admin);

            return $representation;
        }
        return null;
    }

    private function addUserGroupRepresentation(array &$ugroup_representations, Tracker $tracker, array $result_array): void
    {
        $ugroup = $this->ugroup_manager->getUGroup($tracker->getProject(), $result_array['ugroup']['id']);
        if ($ugroup) {
            $representation = new UserGroupRepresentation();
            $representation->build((int) $tracker->getProject()->getID(), $ugroup);
            $ugroup_representations[] = $representation;
        }
    }
}
