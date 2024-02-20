<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Project\Registration;

use ForgeConfig;
use PFUser;
use Project;
use ProjectDao;
use ProjectManager;

class ProjectRegistrationUserPermissionChecker implements CheckProjectRegistrationUserPermission
{
    public function __construct(private ProjectDao $project_dao)
    {
    }

    /**
     * @throws MaxNumberOfProjectReachedForPlatformException
     * @throws MaxNumberOfProjectReachedForUserException
     * @throws LimitedToSiteAdministratorsException
     * @throws AnonymousNotAllowedException
     * @throws RestrictedUsersNotAllowedException
     */
    public function checkUserCreateAProject(PFUser $user): void
    {
        $this->checkUserHasThePermissionToCreateProject($user);

        if ((int) ForgeConfig::get(ProjectManager::CONFIG_PROJECT_APPROVAL, 1) === 1) {
            if (! $this->numberOfProjectsWaitingForValidationBelowThreshold()) {
                throw new MaxNumberOfProjectReachedForPlatformException();
            }
            if (! $this->numberOfProjectsWaitingForValidationPerUserBelowThreshold($user)) {
                throw new MaxNumberOfProjectReachedForUserException();
            }
        }
    }

    private function numberOfProjectsWaitingForValidationBelowThreshold(): bool
    {
        $max_nb_projects_waiting_for_validation = (int) ForgeConfig::get(ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION, -1);
        if ($max_nb_projects_waiting_for_validation < 0) {
            return true;
        }
        $current_nb_projects_waiting_for_validation = $this->project_dao->countByStatus(Project::STATUS_PENDING);
        return $current_nb_projects_waiting_for_validation < $max_nb_projects_waiting_for_validation;
    }

    private function numberOfProjectsWaitingForValidationPerUserBelowThreshold(PFUser $requester): bool
    {
        $max_per_user = (int) ForgeConfig::get(ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION_PER_USER, -1);
        if ($max_per_user < 0) {
            return true;
        }
        $current_per_user = $this->project_dao->countByStatusAndUser((int) $requester->getId(), Project::STATUS_PENDING);
        return $current_per_user < $max_per_user;
    }

    /**
     * @throws LimitedToSiteAdministratorsException
     * @throws AnonymousNotAllowedException
     * @throws RestrictedUsersNotAllowedException
     */
    public function checkUserHasThePermissionToCreateProject(PFUser $user): void
    {
        if (! ForgeConfig::get(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED) && ! $user->isSuperUser()) {
            throw new LimitedToSiteAdministratorsException();
        }

        if ($user->isAnonymous()) {
            throw new AnonymousNotAllowedException();
        }

        if (
            $user->isRestricted()
            && (int) ForgeConfig::get(ProjectManager::CONFIG_RESTRICTED_USERS_CAN_CREATE_PROJECTS) === 0
        ) {
            throw new RestrictedUsersNotAllowedException();
        }
    }
}
