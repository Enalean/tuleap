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

use Tuleap\Project\CheckProjectAccess;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\User\RetrieveUserById;

class Tracker_Permission_PermissionChecker
{
    public function __construct(
        private readonly RetrieveUserById $user_manager,
        private readonly CheckProjectAccess $project_access_checker,
        private readonly GlobalAdminPermissionsChecker $global_admin_permissions_checker,
    ) {
    }

    /**
     * Check if a user can view a given artifact
     *
     * @return bool
     */
    public function userCanView(PFUser $user, Artifact $artifact)
    {
        $project = $artifact->getTracker()->getProject();
        try {
            $this->project_access_checker->checkUserCanAccessProject($user, $project);
        } catch (Project_AccessException $e) {
            return false;
        }

        if ($artifact->getTracker()->isDeleted()) {
            return false;
        }

        if ($user->isAdmin($project->getID())) {
            return true;
        }

        if ($artifact->getTracker()->userIsAdmin($user)) {
            return true;
        }

        if ($this->userCanViewArtifact($user, $artifact)) {
            return $this->userHavePermissionOnTracker($user, $artifact);
        }

        return false;
    }

    private function userCanViewArtifact(PFUser $user, Artifact $artifact)
    {
        if ($artifact->useArtifactPermissions()) {
            $rows = $artifact->permission_db_authorized_ugroups(Artifact::PERMISSION_ACCESS);

            if ($rows !== false) {
                foreach ($rows as $row) {
                    if ($this->userBelongsToGroup($user, $artifact, $row['ugroup_id'])) {
                        return true;
                    }
                }
            }
            return false;
        }
        return true;
    }

    public function userCanViewTracker(PFUser $user, Tracker $tracker)
    {
        $project = $tracker->getProject();
        if ($this->global_admin_permissions_checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user)) {
            return true;
        }

        try {
            $this->project_access_checker->checkUserCanAccessProject($user, $project);
        } catch (Project_AccessException $e) {
            return false;
        }

        if ($tracker->isDeleted()) {
            return false;
        }

        if ($tracker->userIsAdmin($user)) {
            return true;
        }

        foreach ($tracker->getPermissionsByUgroupId() as $ugroup_id => $permission_types) {
            if ($user->isMemberOfUGroup($ugroup_id, $tracker->getGroupId())) {
                return true;
            }
        }

        return false;
    }

    private function userHavePermissionOnTracker(PFUser $user, Artifact $artifact)
    {
        $permissions = $artifact->getTracker()->getAuthorizedUgroupsByPermissionType();

        foreach ($permissions as $permission_type => $ugroups) {
            switch ($permission_type) {
                case Tracker::PERMISSION_FULL:
                    foreach ($ugroups as $ugroup) {
                        if ($this->userBelongsToGroup($user, $artifact, $ugroup)) {
                            return true;
                        }
                    }
                    break;

                case Tracker::PERMISSION_SUBMITTER:
                    foreach ($ugroups as $ugroup) {
                        if ($this->userBelongsToGroup($user, $artifact, $ugroup)) {
                            // check that submitter is also a member
                            $submitter = $this->user_manager->getUserById($artifact->getSubmittedBy());
                            if ($this->userBelongsToGroup($submitter, $artifact, $ugroup)) {
                                return true;
                            }
                        }
                    }
                    break;

                case Tracker::PERMISSION_ASSIGNEE:
                    foreach ($ugroups as $ugroup) {
                        if ($this->userBelongsToGroup($user, $artifact, $ugroup)) {
                            // check that one of the assignees is also a member
                            $permission_assignee = new Tracker_Permission_PermissionRetrieveAssignee($this->user_manager);
                            foreach ($permission_assignee->getAssignees($artifact) as $assignee) {
                                if ($this->userBelongsToGroup($assignee, $artifact, $ugroup)) {
                                    return true;
                                }
                            }
                        }
                    }
                    break;

                case Tracker::PERMISSION_SUBMITTER_ONLY:
                    foreach ($ugroups as $ugroup) {
                        if ($this->userBelongsToGroup($user, $artifact, $ugroup)) {
                            if ($user->getId() == $artifact->getSubmittedBy()) {
                                return true;
                            }
                        }
                    }
                    break;
            }
        }
        return false;
    }

    private function userBelongsToGroup(PFUser $user, Artifact $artifact, $ugroup_id)
    {
        return $user->isMemberOfUGroup($ugroup_id, $artifact->getTracker()->getGroupId());
    }
}
