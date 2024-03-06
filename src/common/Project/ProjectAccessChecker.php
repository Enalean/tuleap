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

use EventManager;
use ForgeConfig;
use PFUser;
use Project;
use Project_AccessDeletedException;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Project_AccessRestrictedException;

class ProjectAccessChecker implements CheckProjectAccess
{
    public function __construct(
        private RestrictedUserCanAccessVerifier $verifier,
        private EventManager $event_manager,
    ) {
    }

    /**
     * @throws ProjectAccessSuspendedException
     * @throws Project_AccessDeletedException
     * @throws Project_AccessPrivateException
     * @throws Project_AccessProjectNotFoundException
     * @throws Project_AccessRestrictedException
     * @throws AccessNotActiveException
     */
    public function checkUserCanAccessProject(PFUser $user, Project $project): void
    {
        if ($project->isError()) {
            throw new Project_AccessProjectNotFoundException();
        }

        if ($user->isAnonymous() && ! ForgeConfig::areAnonymousAllowed()) {
            throw new Project_AccessProjectNotFoundException(_('Project does not exist'));
        }

        if ($user->isSuperUser()) {
            return;
        }

        if (! $project->isActive()) {
            if ($project->isSuspended()) {
                throw new ProjectAccessSuspendedException();
            }

            if ($project->isDeleted()) {
                throw new Project_AccessDeletedException();
            }

            throw new AccessNotActiveException();
        }

        if ($user->isMember($project->getID())) {
            if (
                $project->getAccess() === Project::ACCESS_PRIVATE_WO_RESTRICTED &&
                ForgeConfig::areRestrictedUsersAllowed() &&
                $user->isRestricted()
            ) {
                throw new Project_AccessProjectNotFoundException(_('Project does not exist'));
            }
            return;
        }

        if ($user->isRestricted()) {
            if (
                ! $project->allowsRestricted() ||
                ! $this->verifier->isRestrictedUserAllowedToAccess($user, $project)
            ) {
                throw new Project_AccessRestrictedException();
            }
            return;
        }

        if ($project->isPublic()) {
            return;
        }

        if ($this->userHasBeenDelegatedAccess($user)) {
            return;
        }

        throw new Project_AccessPrivateException();
    }

    private function userHasBeenDelegatedAccess(PFUser $user): bool
    {
        $event = new DelegatedUserAccessForProject($user);

        $this->event_manager->processEvent($event);

        return $event->canUserAccessProject();
    }
}
