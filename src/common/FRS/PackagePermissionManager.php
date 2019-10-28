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

use FRSPackage;
use FRSPackageFactory;
use PFUser;
use Project;

class PackagePermissionManager
{
    /**
     * @var FRSPermissionManager
     */
    private $frs_service_permission_manager;

    /**
     * @var FRSPackageFactory
     */
    private $package_factory;

    public function __construct(FRSPermissionManager $permission_manager, FRSPackageFactory $package_factory)
    {
        $this->frs_service_permission_manager = $permission_manager;
        $this->package_factory                = $package_factory;
    }

    public function canUserSeePackage(PFUser $user, FRSPackage $package, Project $project)
    {
        if ($package->isActive() && $this->frs_service_permission_manager->userCanRead($project, $user)) {
            return true;
        } elseif ($package->isHidden() && $this->package_factory->userCanAdmin($user, $project->getID())) {
            return true;
        }

        return false;
    }
}
