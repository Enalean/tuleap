<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\dao;

use PFUser;
use Project;
use Tuleap\DB\DataAccessObject;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;

class ProjectDao extends DataAccessObject
{
    /**
     * @var GlobalAdminPermissionsChecker
     */
    private $permissions_checker;

    public function __construct(GlobalAdminPermissionsChecker $permissions_checker)
    {
        parent::__construct();

        $this->permissions_checker = $permissions_checker;
    }

    public function searchProjectsForREST(PFUser $user, $limit, $offset)
    {
        if ($this->permissions_checker->doesUserHaveTrackerGlobalAdminRightsOnTheWholePlatform($user)) {
            return $this->runQueryWithAllProjects($limit, $offset);
        } else {
            return $this->runQueryWithFilteredProjects($user, $limit, $offset);
        }
    }

    private function runQueryWithAllProjects($limit, $offset)
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS groups.*
                    FROM groups
                    WHERE status = 'A'
                      AND group_id > 100
                    ORDER BY group_id ASC
                    LIMIT ?
                    OFFSET ?";

        return $this->getDB()->run($sql, $limit, $offset);
    }

    private function runQueryWithFilteredProjects(PFUser $user, $limit, $offset)
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT groups.*
                    FROM groups
                      JOIN user_group USING (group_id)
                    WHERE status = 'A'
                      AND group_id > 100
                      AND (access NOT IN (?, ?)
                        OR user_group.user_id = ?)
                    ORDER BY group_id ASC
                    LIMIT ?
                    OFFSET ?";

        return $this->getDB()->run($sql, Project::ACCESS_PRIVATE, Project::ACCESS_PRIVATE_WO_RESTRICTED, $user->getId(), $limit, $offset);
    }
}
