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
 */

declare(strict_types=1);

namespace Tuleap\Project;

use ForgeConfig;
use PermissionsOverrider_PermissionsOverriderManager;
use PFUser;
use Project;
use Project_AccessDeletedException;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Project_AccessRestrictedException;
use Tuleap\Project\Admin\ProjectWithoutRestrictedFeatureFlag;

class ProjectAccessChecker
{
    /**
     * @var PermissionsOverrider_PermissionsOverriderManager
     */
    private $permissions_overrider_manager;
    /**
     * @var RestrictedUserCanAccessVerifier
     */
    private $verifier;

    public function __construct(
        PermissionsOverrider_PermissionsOverriderManager $permissions_overrider_manager,
        RestrictedUserCanAccessVerifier $verifier
    ) {
        $this->permissions_overrider_manager = $permissions_overrider_manager;
        $this->verifier                      = $verifier;
    }

    /**
     * @throws ProjectAccessSuspendedException
     * @throws Project_AccessDeletedException
     * @throws Project_AccessPrivateException
     * @throws Project_AccessProjectNotFoundException
     * @throws Project_AccessRestrictedException
     */
    public function checkUserCanAccessProject(PFUser $user, Project $project): void
    {
        if ($project->isError()) {
            throw new Project_AccessProjectNotFoundException();
        }

        if ($user->isSuperUser()) {
            return;
        }

        if (! $project->isActive()) {
            if ($project->isSuspended()) {
                throw new ProjectAccessSuspendedException();
            }

            throw new Project_AccessDeletedException($project);
        }

        if ($user->isMember($project->getID())) {
            if (
                $project->getAccess() === Project::ACCESS_PRIVATE_WO_RESTRICTED &&
                ProjectWithoutRestrictedFeatureFlag::isEnabled() &&
                ForgeConfig::areRestrictedUsersAllowed() &&
                $user->isRestricted()
            ) {
                throw new Project_AccessProjectNotFoundException(_('Project does not exist'));
            }
            return;
        }

        if ($this->permissions_overrider_manager->doesOverriderAllowUserToAccessProject($user, $project)) {
            return;
        }

        if ($user->isRestricted()) {
            if ( ! $project->allowsRestricted() ||
                ! $this->verifier->isRestrictedUserAllowedToAccess($user, $project)) {
                throw new Project_AccessRestrictedException();
            }
            return;
        }

        if ($project->isPublic()) {
            return;
        }

        throw new Project_AccessPrivateException();
    }
}
