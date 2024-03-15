<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin\GlobalAdmin;

use Tuleap\Tracker\ForgeUserGroupPermission\TrackerAdminAllProjects;
use User_ForgeUserGroupPermissionsManager;

class GlobalAdminPermissionsChecker
{
    /**
     * @var User_ForgeUserGroupPermissionsManager
     */
    private $forge_user_group_permissions_manager;

    public function __construct(User_ForgeUserGroupPermissionsManager $forge_user_group_permissions_manager)
    {
        $this->forge_user_group_permissions_manager = $forge_user_group_permissions_manager;
    }

    public function doesUserHaveTrackerGlobalAdminRightsOnTheWholePlatform(\PFUser $user): bool
    {
        return $user->isSuperUser()
            || $this->forge_user_group_permissions_manager->doesUserHavePermission($user, new TrackerAdminAllProjects());
    }

    public function doesUserHaveTrackerGlobalAdminRightsOnProject(\Project $project, \PFUser $user): bool
    {
        if ($project->getStatus() === \Project::STATUS_CREATING_FROM_ARCHIVE) {
            return false;
        }

        return $user->isAdmin($project->getID())
            || $this->doesUserHaveTrackerGlobalAdminRightsOnTheWholePlatform($user);
    }
}
