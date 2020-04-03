<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use FRSRelease;
use FRSReleaseFactory;
use PFUser;
use Project;

class ReleasePermissionManager
{
    /**
     * @var FRSPermissionManager
     */
    private $frs_service_permission_manager;

    /**
     * @var FRSReleaseFactory
     */
    private $release_factory;

    public function __construct(FRSPermissionManager $permission_manager, FRSReleaseFactory $release_factory)
    {
        $this->frs_service_permission_manager = $permission_manager;
        $this->release_factory                = $release_factory;
    }

    public function canUserSeeRelease(PFUser $user, FRSRelease $release, Project $project)
    {
        if (
            $release->isActive()
            && $this->frs_service_permission_manager->userCanRead($project, $user)
            && $this->release_factory->userCanRead($project->getID(), $release->getPackageID(), $release->getReleaseID())
        ) {
            return true;
        } elseif ($release->isHidden() && $this->release_factory->userCanAdmin($user, $project->getID())) {
            return true;
        }

        return false;
    }
}
