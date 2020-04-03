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

namespace Tuleap\User\Admin;

use ForgeConfig;
use PFUser;
use Project;
use UserGroupDao;

final class RestrictedProjectsUserCounter
{
    /**
     * @var UserGroupDao
     */
    private $user_group_dao;

    public function __construct(UserGroupDao $user_group_dao)
    {
        $this->user_group_dao = $user_group_dao;
    }

    public function getNumberOfProjectsNotAllowingRestrictedTheUserIsMemberOf(PFUser $user): int
    {
        if (! ForgeConfig::areRestrictedUsersAllowed()) {
            return 0;
        }

        $projects_dar = $this->user_group_dao->searchActiveProjectsByUserIdAndAccessType(
            (int) $user->getId(),
            Project::ACCESS_PRIVATE_WO_RESTRICTED
        );

        return $projects_dar === false ? 0 : count($projects_dar);
    }
}
