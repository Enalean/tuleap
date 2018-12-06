<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Statistic;

use DataAccessObject;

class ServiceUsageDao extends DataAccessObject
{
    public function searchWriteOperationsByProject($start_date, $end_date)
    {
        $start_date = $this->getDa()->quoteSmart($start_date);
        $end_date   = $this->getDa()->quoteSmart($end_date);

        $sql = "SELECT group_id, SUM(svn_write_operations) AS result
                FROM plugin_svn_full_history
                JOIN plugin_svn_repositories ON plugin_svn_repositories.id = plugin_svn_full_history.repository_id
                JOIN groups ON groups.group_id = plugin_svn_repositories.project_id
                WHERE day BETWEEN DATE_FORMAT($start_date, '%Y%m%d') AND DATE_FORMAT($end_date, '%Y%m%d')
                GROUP BY group_id";

        return $this->retrieve($sql);
    }
}
