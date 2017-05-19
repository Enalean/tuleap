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
    private $legacy_types_to_new_types = array(
        \WidgetLayoutManager::OWNER_TYPE_GROUP => 'project',
        \WidgetLayoutManager::OWNER_TYPE_USER  => 'user'
    );

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

    public function create($owner_id, $owner_type, $dashboard_id, $name, $content_id)
    {
        $this->startTransaction();

        $dashboard_type = $this->legacy_types_to_new_types[$owner_type];
        $this->checkThatDashboardBelongsToTheOwner($owner_id, $dashboard_type, $dashboard_id);

        $column_id = $this->getFirstColumnIdOfDashboard($dashboard_id, $dashboard_type);
        $this->insertWidgetInColumn($name, $content_id, $column_id);

        $this->commit();
    }

    private function getFirstColumnIdOfDashboard($dashboard_id, $dashboard_type)
    {
        $dashboard_id = $this->da->escapeInt($dashboard_id);

        $sql = "SELECT col.id
                FROM dashboards_lines_columns AS col
                    INNER JOIN dashboards_lines AS line ON (
                        line.id = col.line_id
                        AND line.dashboard_id = $dashboard_id
                    )
                ORDER BY line.rank ASC, col.rank ASC
                LIMIT 1";

        $row = $this->retrieve($sql)->getRow();
        if ($row) {
            return $row['id'];
        }

        return $this->createFirstColumn($dashboard_id, $dashboard_type);
    }

    private function createFirstColumn($dashboard_id, $dashboard_type)
    {
        $dashboard_id   = $this->da->escapeInt($dashboard_id);
        $dashboard_type = $this->da->quoteSmart($dashboard_type);

        $sql = "INSERT INTO dashboards_lines (dashboard_id, dashboard_type, layout, rank)
                VALUES ($dashboard_id, $dashboard_type, 'one-column', 1)";

        $line_id = $this->updateAndGetLastId($sql);

        $line_id = $this->da->escapeInt($line_id);
        $sql = "INSERT INTO dashboards_lines_columns (line_id, rank) VALUES ($line_id, 1)";

        return $this->updateAndGetLastId($sql);
    }

    private function insertWidgetInColumn($name, $content_id, $column_id)
    {
        $column_id  = $this->da->escapeInt($column_id);
        $content_id = $this->da->escapeInt($content_id);
        $name       = $this->da->quoteSmart($name);

        $sql = "INSERT INTO dashboards_lines_columns_widgets (column_id, rank, name, content_id)
                SELECT $column_id, IFNULL(min(rank) - 1, 1), $name, $content_id
                FROM dashboards_lines_columns_widgets
                WHERE column_id = $column_id";
        $this->update($sql);

        $this->adjustOrderOfWidgetsInColumn($column_id);
    }

    private function adjustOrderOfWidgetsInColumn($column_id)
    {
        $sql = "UPDATE dashboards_lines_columns_widgets
                SET rank = rank + 1
                WHERE column_id = $column_id";
        $this->update($sql);
    }

    private function checkThatDashboardBelongsToTheOwner($owner_id, $dashboard_type, $dashboard_id)
    {
        $dashboard_id = $this->da->escapeInt($dashboard_id);
        $owner_id     = $this->da->escapeInt($owner_id);

        if ($dashboard_type === 'project') {
            $sql = "SELECT NULL
                    FROM project_dashboards
                    WHERE id = $dashboard_id
                      AND project_id = $owner_id";
        } else {
            $sql = "SELECT NULL
                    FROM user_dashboards
                    WHERE id = $dashboard_id
                      AND user_id = $owner_id";
        }

        if (count($this->retrieve($sql)) === 0) {
            throw new \DataAccessException();
        }
    }
}
