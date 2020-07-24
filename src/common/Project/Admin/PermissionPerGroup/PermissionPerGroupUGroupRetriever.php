<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Project\Admin\PermissionsPerGroup;

use PermissionsManager;

class PermissionPerGroupUGroupRetriever
{
    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    public function __construct(PermissionsManager $permissions_manager)
    {
        $this->permissions_manager = $permissions_manager;
    }

    public function getAdminUGroupIdsForProjectContainingUGroupId(\Project $project, $object_id, $permission_type, $permission_id)
    {
        $permissions = $this->permissions_manager->getAuthorizedUGroupIdsForProject($project, $object_id, $permission_type);

        if (in_array($permission_id, $permissions)) {
            return $permissions;
        }

        return [];
    }

    public function getAllUGroupForObject(\Project $project, $object_id, $permission_type)
    {
        return $this->permissions_manager->getAuthorizedUGroupIdsForProject($project, $object_id, $permission_type);
    }
}
