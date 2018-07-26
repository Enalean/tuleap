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

namespace Tuleap\Tracker\dao;

use PFUser;
use Project;
use TrackerManager;
use Tuleap\DB\DataAccessObject;

class ProjectDao extends DataAccessObject
{
    /**
     * @var TrackerManager
     */
    private $tracker_manager;

    public function __construct(TrackerManager $tracker_manager)
    {
        parent::__construct();

        $this->tracker_manager = $tracker_manager;
    }

    public function searchProjectsForREST(PFUser $user, $limit, $offset)
    {
        if ($user->isSuperUser() || $this->tracker_manager->userCanAdminAllProjectTrackers($user)) {
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
                      AND (access != ?
                        OR user_group.user_id = ?)
                    ORDER BY group_id ASC
                    LIMIT ?
                    OFFSET ?";

        return $this->getDB()->run($sql, Project::ACCESS_PRIVATE, $user->getId(), $limit, $offset);
    }
}
