<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\Project;

use DataAccess;
use DataAccessObject;

class ProjectDashboardDao extends DataAccessObject
{

    public function __construct(DataAccess $da = null)
    {
        parent::__construct($da);
        $this->enableExceptionsOnError();
    }

    public function searchAllProjectDashboards($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT *
                FROM project_dashboards
                WHERE project_id=$project_id";

        return $this->retrieve($sql);
    }

    /**
     * @param $project_id
     * @param $name
     * @return int
     */
    public function save($project_id, $name)
    {
        $project_id = $this->da->escapeInt($project_id);
        $name       = $this->da->quoteSmart($name);

        $sql = "INSERT INTO project_dashboards(project_id, name)
                VALUES ($project_id, $name)";

        return $this->updateAndGetLastId($sql);
    }

    public function searchByProjectIdAndName($project_id, $name)
    {
        $project_id = $this->da->escapeInt($project_id);
        $name       = $this->da->quoteSmart($name);

        $sql = "SELECT *
                FROM project_dashboards
                WHERE project_id=$project_id AND name=$name";

        return $this->retrieve($sql);
    }
}
