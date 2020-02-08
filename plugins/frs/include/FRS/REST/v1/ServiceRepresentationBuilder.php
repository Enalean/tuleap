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

use Luracast\Restler\RestException;
use PFUser;
use Project;
use ProjectUGroup;
use Tuleap\FRS\FRSPermission;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\Project\REST\UserGroupRepresentation;

class ServiceRepresentationBuilder
{
    /**
     * @var FRSPermissionManager
     */
    private $permission_manager;
    /**
     * @var FRSPermissionFactory
     */
    private $permission_factory;
    /**
     * @var \UGroupManager
     */
    private $ugroup_manager;

    public function __construct(FRSPermissionManager $permission_manager, FRSPermissionFactory $permission_factory, \UGroupManager $ugroup_manager)
    {
        $this->permission_manager = $permission_manager;
        $this->permission_factory = $permission_factory;
        $this->ugroup_manager = $ugroup_manager;
    }

    /**
     * @throws RestException
     */
    public function getServiceRepresentation(PFUser $user, Project $project): ServiceRepresentation
    {
        if (! $project->usesFile()) {
            throw new RestException(404, 'File Release System service is not used by the project');
        }
        $service_representation = new ServiceRepresentation();
        if (! $this->permission_manager->isAdmin($project, $user)) {
            return $service_representation;
        }

        return $service_representation->build(
            (new ServicePermissionsForGroupsRepresentation())->build($this->getCanAdmin($project), $this->getCanRead($project))
        );
    }

    private function getCanRead(Project $project): array
    {
        return $this->getUserGroups($project, FRSPermission::FRS_READER);
    }

    private function getCanAdmin(Project $project): array
    {
        return $this->getUserGroups($project, FRSPermission::FRS_ADMIN);
    }

    private function getUserGroups(Project $project, string $permission): array
    {
        return $this->getUserGroupRepresentations($project, $this->permission_factory->getFrsUGroupsByPermission($project, $permission));
    }

    private function getUserGroupRepresentations(Project $project, array $frs_permissions): array
    {
        $ugroups = [];
        foreach ($frs_permissions as $frs_permission) {
            $ugroup = $this->ugroup_manager->getUGroup($project, $frs_permission->getUGroupId());
            if ($ugroup && (int) $ugroup->getId() !== ProjectUGroup::NONE) {
                $ugroups[] = (new UserGroupRepresentation())->build((int) $project->getID(), $ugroup);
            }
        }
        return $ugroups;
    }
}
