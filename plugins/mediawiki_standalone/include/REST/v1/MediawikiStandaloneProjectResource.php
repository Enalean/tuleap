<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\MediawikiStandalone\Permissions\AdminsRetriever;
use Tuleap\MediawikiStandalone\Permissions\ProjectPermissionsRetriever;
use Tuleap\MediawikiStandalone\Permissions\ReadersRetriever;
use Tuleap\MediawikiStandalone\Permissions\RestrictedUserCanAccessMediaWikiVerifier;
use Tuleap\MediawikiStandalone\Permissions\UserPermissionsBuilder;
use Tuleap\MediawikiStandalone\Permissions\WritersRetriever;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\REST\Header;

final class MediawikiStandaloneProjectResource
{
    /**
     * @url OPTIONS {id}/mediawiki_standalone_permissions
     *
     * @oauth2-scope read:mediawiki_standalone
     *
     * @param int $id Id of the project
     */
    public function options(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get Mediawiki permissions of the current user
     *
     * @url    GET {id}/mediawiki_standalone_permissions
     * @access hybrid
     * @oauth2-scope read:mediawiki_standalone
     *
     * @param int $id Id of the project
     */
    public function getPermissions(int $id): GetPermissionsRepresentation
    {
        $user = \UserManager::instance()->getCurrentUser();

        try {
            $project = \ProjectManager::instance()->getValidProject($id);

            $dao = new \Tuleap\MediawikiStandalone\Permissions\MediawikiPermissionsDao();

            $permissions_builder = new UserPermissionsBuilder(
                new \User_ForgeUserGroupPermissionsManager(
                    new \User_ForgeUserGroupPermissionsDao()
                ),
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessMediaWikiVerifier(),
                    \EventManager::instance(),
                ),
                new ProjectPermissionsRetriever(
                    new ReadersRetriever($dao),
                    new WritersRetriever($dao),
                    new AdminsRetriever($dao),
                ),
            );

            return new GetPermissionsRepresentation($permissions_builder->getPermissions($user, $project));
        } catch (\Project_NotFoundException) {
            throw new RestException(404, 'Project not found');
        }
    }
}
