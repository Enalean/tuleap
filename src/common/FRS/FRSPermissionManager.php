<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use EventManager;
use PermissionsOverrider_PermissionsOverriderManager;
use Project;
use PFUser;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;

class FRSPermissionManager
{
    /** @var FRSPermissionDao */
    private $permission_dao;
    /** @var FRSPermissionFactory */
    private $permission_factory;
    /**
     * @var ProjectAccessChecker
     */
    private $access_checker;

    public function __construct(
        FRSPermissionDao $permission_dao,
        FRSPermissionFactory $permission_factory,
        ProjectAccessChecker $access_checker
    ) {
        $this->permission_dao     = $permission_dao;
        $this->permission_factory = $permission_factory;
        $this->access_checker     = $access_checker;
    }

    /**
     * @return FRSPermissionManager
     */
    public static function build()
    {
        return new self(
            new FRSPermissionDao(),
            new FRSPermissionFactory(new FRSPermissionDao()),
            new ProjectAccessChecker(
                PermissionsOverrider_PermissionsOverriderManager::instance(),
                new RestrictedUserCanAccessProjectVerifier(),
                EventManager::instance()
            )
        );
    }

    public function isAdmin(Project $project, PFUser $user): bool
    {
        try {
            $this->access_checker->checkUserCanAccessProject($user, $project);

            if ($user->isSuperUser() || $user->isAdmin($project->getId())) {
                return true;
            }

            $permissions = $this->permission_factory->getFrsUgroupsByPermission($project, FRSPermission::FRS_ADMIN);

            foreach ($permissions as $permission) {
                if ($user->isMemberOfUGroup($permission->getUGroupId(), $project->getID())) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function userCanRead(Project $project, PFUser $user)
    {
        try {
            $this->access_checker->checkUserCanAccessProject($user, $project);

            if ($this->isAdmin($project, $user)) {
                return true;
            }

            $permissions = $this->permission_dao->searchPermissionsForProjectByType($project->getID(), FRSPermission::FRS_READER);
            foreach ($permissions as $permission) {
                if ($user->isMemberOfUGroup($permission['ugroup_id'], $project->getID())) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
