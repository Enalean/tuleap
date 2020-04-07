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
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Permissions;

use Docman_Item;
use Docman_PermissionsManager;
use IPermissionsManagerNG;
use PFUser;
use Project;
use ProjectManager;
use Tuleap\Project\REST\UserGroupRepresentation;
use UGroupManager;

class DocmanItemPermissionsForGroupsBuilder
{
    /**
     * @var Docman_PermissionsManager
     */
    private $docman_permissions_manager;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var IPermissionsManagerNG
     */
    private $permissions_manager;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        Docman_PermissionsManager $docman_permissions_manager,
        ProjectManager $project_manager,
        IPermissionsManagerNG $permissions_manager,
        UGroupManager $ugroup_manager
    ) {
        $this->docman_permissions_manager = $docman_permissions_manager;
        $this->project_manager            = $project_manager;
        $this->permissions_manager        = $permissions_manager;
        $this->ugroup_manager             = $ugroup_manager;
    }

    public function getRepresentation(PFUser $user, Docman_Item $item): ?DocmanItemPermissionsForGroupsRepresentation
    {
        if (! $this->docman_permissions_manager->userCanManage($user, $item->getId())) {
            return null;
        }

        $project = $this->project_manager->getProject($item->getGroupId());

        return DocmanItemPermissionsForGroupsRepresentation::build(
            $this->buildUGroupsRepresentationForPermission($project, $item, Docman_PermissionsManager::ITEM_PERMISSION_TYPE_READ),
            $this->buildUGroupsRepresentationForPermission($project, $item, Docman_PermissionsManager::ITEM_PERMISSION_TYPE_WRITE),
            $this->buildUGroupsRepresentationForPermission($project, $item, Docman_PermissionsManager::ITEM_PERMISSION_TYPE_MANAGE),
        );
    }

    /**
     * @psalm-param value-of<Docman_PermissionsManager::ITEM_PERMISSION_TYPES> $permission_type
     * @return UserGroupRepresentation[]
     */
    private function buildUGroupsRepresentationForPermission(Project $project, Docman_Item $item, string $permission_type): array
    {
        $user_group_representations = [];

        $ugroup_ids = $this->permissions_manager->getAuthorizedUGroupIdsForProjectWithoutDefaultValues(
            $project,
            $item->getId(),
            $permission_type
        );
        foreach ($ugroup_ids as $ugroup_id) {
            $ugroup = $this->ugroup_manager->getUGroup($project, $ugroup_id);
            if ($ugroup !== null) {
                $user_group_representations[] = (new UserGroupRepresentation())->build((int) $project->getID(), $ugroup);
            }
        }

        return $user_group_representations;
    }
}
