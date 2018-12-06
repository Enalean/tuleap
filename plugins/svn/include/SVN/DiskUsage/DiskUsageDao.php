<?php
/**
 * Copyright (c) Enalean, 2017- 2018. All Rights Reserved.
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

namespace Tuleap\SVN\DiskUsage;

use DataAccessObject;

class DiskUsageDao extends DataAccessObject
{
    public function hasRepositoriesUpdatedAfterGivenDate($project_id, $date)
    {
        $project_id = $this->da->escapeInt($project_id);
        $date       = $this->da->escapeInt($date);

        $sql = "SELECT NULL
                FROM plugin_svn_repositories
                LEFT JOIN plugin_svn_last_access
                  ON plugin_svn_repositories.id = plugin_svn_last_access.repository_id
                WHERE project_id = $project_id
                AND repository_deletion_date IS NULL
                AND plugin_svn_last_access.commit_date  > $date";

        return $this->retrieve($sql)->count() > 0;
    }
}
