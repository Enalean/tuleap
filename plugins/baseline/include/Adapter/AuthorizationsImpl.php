<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter;

use Override;
use PFUser;
use Tuleap\Baseline\Domain\Authorizations;
use Tuleap\Baseline\Domain\Baseline;
use Tuleap\Baseline\Domain\Comparison;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;
use Tuleap\Baseline\Domain\RoleBaselineAdmin;
use Tuleap\Baseline\Domain\RoleBaselineReader;
use Tuleap\Baseline\Domain\TransientBaseline;
use Tuleap\Baseline\Domain\TransientComparison;
use Tuleap\Baseline\Domain\UserIdentifier;

class AuthorizationsImpl implements Authorizations
{
    public function __construct(
        private RoleAssignmentRepository $role_assignment_repository,
        private \UserManager $user_manager,
    ) {
    }

    #[Override]
    public function canCreateBaseline(UserIdentifier $current_user, TransientBaseline $baseline): bool
    {
        return $this->canUserAdministrateBaselineOnProject($current_user, $baseline->getProject());
    }

    #[Override]
    public function canDeleteBaseline(UserIdentifier $current_user, Baseline $baseline): bool
    {
        return $this->canUserAdministrateBaselineOnProject($current_user, $baseline->getProject());
    }

    private function getUserFromIdentifier(UserIdentifier $identifier): PFUser
    {
        $user = $this->user_manager->getUserById($identifier->getId());
        if (! $user) {
            throw new \LogicException('User not found, this is not expected');
        }

        return $user;
    }

    #[Override]
    public function canReadBaseline(UserIdentifier $current_user, Baseline $baseline): bool
    {
        return $this->canReadBaselinesOnProject($current_user, $baseline->getProject());
    }

    #[Override]
    public function canReadBaselinesOnProject(UserIdentifier $current_user, ProjectIdentifier $project): bool
    {
        $user = $this->getUserFromIdentifier($current_user);

        if ($this->isUserAdminOnProject($user, $project)) {
            return true;
        }
        return $this->canUserAdministrateBaselineOnProject($current_user, $project)
            || $this->hasUserRoleOnProject($user, new RoleBaselineReader(), $project);
    }

    #[Override]
    public function canUserAdministrateBaselineOnProject(UserIdentifier $current_user, ProjectIdentifier $project): bool
    {
        $user = $this->getUserFromIdentifier($current_user);

        if ($this->isUserAdminOnProject($user, $project)) {
            return true;
        }

        return $this->hasUserRoleOnProject($user, new RoleBaselineAdmin(), $project);
    }

    private function hasUserRoleOnProject(PFUser $user, Role $role, ProjectIdentifier $project): bool
    {
        $assignments = $this->role_assignment_repository->findByProjectAndRole($project, $role);
        foreach ($assignments as $assignment) {
            if ($user->isMemberOfUGroup($assignment->getUserGroupId(), $project->getID())) {
                return true;
            }
        }
        return false;
    }

    private function isUserAdminOnProject(PFUser $user, ProjectIdentifier $project): bool
    {
        return $user->isSuperUser() || $user->isAdmin($project->getID());
    }

    #[Override]
    public function canCreateComparison(UserIdentifier $current_user, TransientComparison $comparison): bool
    {
        return $this->canUserAdministrateBaselineOnProject($current_user, $comparison->getProject());
    }

    #[Override]
    public function canDeleteComparison(UserIdentifier $current_user, Comparison $comparison): bool
    {
        return $this->canUserAdministrateBaselineOnProject($current_user, $comparison->getProject());
    }

    #[Override]
    public function canReadComparison(UserIdentifier $current_user, Comparison $comparison): bool
    {
        return $this->canReadComparisonsOnProject($current_user, $comparison->getProject());
    }

    #[Override]
    public function canReadComparisonsOnProject(UserIdentifier $current_user, ProjectIdentifier $project): bool
    {
        return $this->canReadBaselinesOnProject($current_user, $project);
    }
}
