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

namespace Tuleap\Dashboard\Widget;

use DataAccess;
use DataAccessObject;

class DashboardWidgetDao extends DataAccessObject
{
    public function __construct(DataAccess $da = null)
    {
        parent::__construct($da);
        $this->enableExceptionsOnError();
    }

    public function searchAllLinesByDashboardIdOrderedByRank($dashboard_id, $dashboard_type)
    {
        $dashboard_id   = $this->da->escapeInt($dashboard_id);
        $dashboard_type = $this->da->quoteSmart($dashboard_type);

        $sql = "SELECT *
                FROM dashboards_lines
                WHERE dashboard_id=$dashboard_id AND dashboard_type=$dashboard_type
                ORDER BY rank ASC";

        return $this->retrieve($sql);
    }

    public function searchAllColumnsByLineIdOrderedByRank($line_id)
    {
        $line_id = $this->da->escapeInt($line_id);

        $sql = "SELECT *
                FROM dashboards_lines_columns
                WHERE line_id=$line_id
                ORDER BY rank ASC";

        return $this->retrieve($sql);
    }

    public function searchAllWidgetByColumnId($column_id)
    {
        $column_id = $this->da->escapeInt($column_id);

        $sql = "SELECT *
                FROM dashboards_lines_columns_widgets
                WHERE column_id=$column_id
                ORDER BY rank ASC";

        return $this->retrieve($sql);
    }
}
