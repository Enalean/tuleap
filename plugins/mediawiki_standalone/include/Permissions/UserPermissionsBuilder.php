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

namespace Tuleap\MediawikiStandalone\Permissions;

use Tuleap\Mediawiki\ForgeUserGroupPermission\MediawikiAdminAllProjects;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\User\ForgePermissionsRetriever;

final class UserPermissionsBuilder
{
    public function __construct(private ForgePermissionsRetriever $forge_permissions_retriever, private CheckProjectAccess $check_project_access)
    {
    }

    public function getPermissions(\PFUser $user, \Project $project): UserPermissions
    {
        if (
            $user->isSuperUser() ||
            $this->forge_permissions_retriever->doesUserHavePermission($user, new MediawikiAdminAllProjects()) ||
            $user->isAdmin((int) $project->getID())
        ) {
            return UserPermissions::fullAccess();
        }

        if ($user->isMember($project->getID())) {
            return UserPermissions::writer();
        }

        try {
            $this->check_project_access->checkUserCanAccessProject($user, $project);
            return UserPermissions::reader();
        } catch (\Project_AccessException) {
        }
        return UserPermissions::noAccess();
    }
}
