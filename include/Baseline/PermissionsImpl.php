<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline;

use Project;
use Tracker_Artifact;

class PermissionsImpl implements Permissions
{
    /** @var CurrentUserProvider */
    private $current_user_provider;

    /** @var ProjectPermissions */
    private $project_permissions;

    /** @var RoleAssignmentRepository */
    private $role_assignment_repository;

    public function __construct(
        CurrentUserProvider $current_user_provider,
        ProjectPermissions $project_permissions,
        RoleAssignmentRepository $role_assignment_repository
    ) {
        $this->current_user_provider      = $current_user_provider;
        $this->project_permissions        = $project_permissions;
        $this->role_assignment_repository = $role_assignment_repository;
    }

    /**
     * @throws NotAuthorizedException
     */
    public function checkCreateBaseline(TransientBaseline $baseline)
    {
        $project = $baseline->getMilestone()->getTracker()->getProject();

        if (! $this->isCurrentUserAdminOf($project)
            && ! $this->hasCurrentUserRole(Role::ADMIN, $project)) {
            throw new NotAuthorizedException(
                sprintf(
                    "You cannot create a baseline on project with id %u",
                    $project->getID()
                )
            );
        }
        $this->checkReadArtifact($baseline->getMilestone());
    }

    /**
     * @throws NotAuthorizedException
     */
    public function checkReadSimpleBaseline(SimplifiedBaseline $baseline): void
    {
        $this->checkReadArtifact($baseline->getMilestone());
    }

    /**
     * @throws NotAuthorizedException
     */
    public function checkReadBaselinesOn(Project $project): void
    {
        $this->project_permissions->checkRead($project);
    }

    /**
     * @throws NotAuthorizedException
     */
    private function checkReadArtifact(Tracker_Artifact $artifact): void
    {
        if (! $artifact->userCanView($this->current_user_provider->getUser())) {
            throw new NotAuthorizedException(
                dgettext('tuleap-baseline', 'You cannot read this artifact')
            );
        }

        $tracker = $artifact->getTracker();
        if (! $tracker->userCanView($this->current_user_provider->getUser())) {
            throw new NotAuthorizedException(
                dgettext('tuleap-baseline', 'You cannot read this artifact because you cannot access to its tracker')
            );
        }

        $project = $tracker->getProject();
        try {
            $this->project_permissions->checkRead($project);
        } catch (NotAuthorizedException $e) {
            throw new NotAuthorizedException(
                dgettext(
                    'tuleap-baseline',
                    'You cannot read this artifact because you cannot access to its project'
                )
            );
        }
    }

    private function isCurrentUserAdminOf(Project $project)
    {
        $current_user = $this->current_user_provider->getUser();
        return $current_user->isSuperUser() || $current_user->isAdmin($project->getId());
    }

    private function hasCurrentUserRole(string $role, Project $project)
    {
        $current_user = $this->current_user_provider->getUser();

        $assignments = $this->role_assignment_repository->findByProjectAndRole($project, $role);
        foreach ($assignments as $assignment) {
            if ($current_user->isMemberOfUGroup($assignment->getUserGroupId(), $project->getID())) {
                return true;
            }
        }
        return false;
    }
}
