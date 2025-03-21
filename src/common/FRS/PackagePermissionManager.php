<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

readonly class PackagePermissionManager
{
    public function __construct(private FRSPackageFactory $package_factory)
    {
    }

    public function canUserSeePackage(PFUser $user, FRSPackage $package, Project $project): bool
    {
        if ($package->isActive() && $this->package_factory->userCanRead($project->getID(), $package->getPackageID(), $user->getId())) {
            return true;
        } elseif ($package->isHidden() && $this->package_factory->userCanAdmin($user, $project->getID())) {
            return true;
        }

        return false;
    }
}
