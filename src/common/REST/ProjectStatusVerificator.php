<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\REST;

use EventManager;
use Luracast\Restler\RestException;
use PermissionsOverrider_PermissionsOverriderManager;
use Project;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\ProjectAccessSuspendedException;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;

class ProjectStatusVerificator
{
    /**
     * @var ProjectAccessChecker
     */
    private $access_checker;

    public function __construct(ProjectAccessChecker $access_checker)
    {
        $this->access_checker = $access_checker;
    }

    public static function build()
    {
        return new self(
            new ProjectAccessChecker(
                PermissionsOverrider_PermissionsOverriderManager::instance(),
                new RestrictedUserCanAccessProjectVerifier(),
                EventManager::instance()
            )
        );
    }

    /**
     * @throws RestException
     */
    public function checkProjectStatusAllowsAllUsersToAccessIt(Project $project)
    {
        if ($project->isSuspended()) {
            $this->blockRestAccess();
        }
    }

    /**
     * @deprecated You should be checking permissions at the resource level directly (Artifact, File, Document, etc).
     * @throws RestException
     */
    public function checkProjectStatusAllowsOnlySiteAdminToAccessIt(\PFUser $user, Project $project): void
    {
        try {
            $this->access_checker->checkUserCanAccessProject($user, $project);
        } catch (ProjectAccessSuspendedException $exception) {
            $this->blockRestAccess();
        } catch (\Exception $exception) {
            throw new RestException(404);
        }
    }

    /**
     * @throws RestException
     */
    private function blockRestAccess()
    {
        $status_suspended_label = Project::STATUS_SUSPENDED_LABEL;

        throw new RestException(403, "This project is $status_suspended_label");
    }
}
