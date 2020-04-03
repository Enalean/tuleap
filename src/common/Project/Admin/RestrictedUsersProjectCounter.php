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

namespace Tuleap\Project\Admin;

use ForgeConfig;
use Project;
use UserDao;

final class RestrictedUsersProjectCounter
{
    /**
     * @var UserDao
     */
    private $user_dao;

    public function __construct(UserDao $user_dao)
    {
        $this->user_dao = $user_dao;
    }

    public function getNumberOfRestrictedUsersInProject(Project $project): int
    {
        if (! ForgeConfig::areRestrictedUsersAllowed()) {
            return 0;
        }

        $userlist = $this->user_dao->listAllUsers($project->getID(), '', 0, 0, 'user_name', 'ASC', ['R']);

        return (int) ($userlist['numrows'] ?: 0);
    }
}
