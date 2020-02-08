<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\FRS\REST\v1;

use FRSPackage;
use IPermissionsManagerNG;
use PFUser;
use Project;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\Project\REST\UserGroupRepresentation;
use UGroupManager;

class PackageRepresentationBuilder
{
    /**
     * @var IPermissionsManagerNG
     */
    private $permissions_manager;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var FRSPermissionManager
     */
    private $frs_permission_manager;

    public function __construct(IPermissionsManagerNG $permissions_manager, UGroupManager $ugroup_manager, FRSPermissionManager $frs_permission_manager)
    {
        $this->permissions_manager = $permissions_manager;
        $this->ugroup_manager = $ugroup_manager;
        $this->frs_permission_manager = $frs_permission_manager;
    }

    public function getPackageForUser(PFUser $user, FRSPackage $package, Project $project): PackageRepresentation
    {
        $representation = new PackageRepresentation();
        $representation->buildFullRepresentation($package, $project, $this->getPermissionsForGroupsRepresentation($user, $package, $project));
        return $representation;
    }

    private function getPermissionsForGroupsRepresentation(PFUser $user, FRSPackage $package, Project $project): ?PermissionsForGroupsRepresentation
    {
        if (! $this->frs_permission_manager->isAdmin($project, $user)) {
            return null;
        }
        $permissions_for_groups = $this->getPermissionsForGroups($package, $project);
        return (new PermissionsForGroupsRepresentation())->build($permissions_for_groups);
    }

    /**
     * @return array
     */
    private function getPermissionsForGroups(FRSPackage $package, Project $project): array
    {
        $permissions_for_groups = [];
        $ugroup_ids             = $this->permissions_manager->getAuthorizedUGroupIdsForProject(
            $project,
            $package->getPackageID(),
            FRSPackage::PERM_READ
        );
        foreach ($ugroup_ids as $ugroup_id) {
            $ugroup = $this->ugroup_manager->getUGroup($project, $ugroup_id);
            if ($ugroup) {
                $ugroup_representation = new UserGroupRepresentation();
                $ugroup_representation->build((int) $project->getID(), $ugroup);
                $permissions_for_groups[] = $ugroup_representation;
            }
        }
        return $permissions_for_groups;
    }
}
