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

namespace Tuleap\ProjectCertification\REST;

use Luracast\Restler\RestException;
use Tuleap\ProjectCertification\ProjectOwner\ProjectOwnerDAO;
use Tuleap\ProjectCertification\ProjectOwner\ProjectOwnerRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\User\ForgeUserGroupPermission\RestProjectManagementPermission;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;

class ProjectCertificationResource extends AuthenticatedResource
{
    /**
     * @url OPTIONS {project_id}
     *
     * @access protected
     */
    public function option($project_id)
    {
        Header::allowOptionsGet();
    }

    /**
     * Get project certification information
     *
     * @url GET {project_id}
     *
     * @access protected
     *
     * @param int $project_id ID of the project
     *
     * @status 200
     *
     * @return \Tuleap\ProjectCertification\REST\ProjectCertificationRepresentation
     */
    public function get($project_id)
    {
        $user_manager = \UserManager::instance();
        if (! $this->canUserManageProjectCertification($user_manager->getCurrentUser())) {
            throw new RestException(
                403,
                'You need to be a site administrator or have REST project management permission delegation'
            );
        }

        try {
            $project = \ProjectManager::instance()->getValidProject($project_id);
        } catch (\Project_NotFoundException $exception) {
            throw new RestException(404, 'Project not found');
        }

        $project_owner_retriever = new ProjectOwnerRetriever(new ProjectOwnerDAO(), $user_manager);

        $representation = new ProjectCertificationRepresentation();
        $project_owner  = $project_owner_retriever->getProjectOwner($project);

        $representation->build($project_owner);

        return $representation;
    }

    /**
     * @return bool
     */
    private function canUserManageProjectCertification(\PFUser $user)
    {
        $forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
        $forge_ugroup_permissions_manager->doesUserHavePermission($user, new RestProjectManagementPermission());
        return $user->isSuperUser() ||
            $forge_ugroup_permissions_manager->doesUserHavePermission($user, new RestProjectManagementPermission());
    }
}
