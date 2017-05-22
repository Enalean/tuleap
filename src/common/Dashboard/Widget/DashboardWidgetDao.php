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

        try {
            $dashboard_type = $this->legacy_types_to_new_types[$owner_type];
            $this->checkThatDashboardBelongsToTheOwner($owner_id, $dashboard_type, $dashboard_id);

            $column_id = $this->getFirstColumnIdOfDashboard($dashboard_id, $dashboard_type);
            $this->insertWidgetInColumn($name, $content_id, $column_id);

            $this->commit();
        } catch (\Exception $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    public function deleteWidget($owner_id, $dashboard_id, $dashboard_type, $widget_id)
    {
        $this->startTransaction();

        try {
            $this->checkThatDashboardBelongsToTheOwner($owner_id, $dashboard_type, $dashboard_id);

            $row = $this->searchInfoForWidget($dashboard_id, $dashboard_type, $widget_id)->getRow();
            if (! $row) {
                throw new \DataAccessException();
            }
            $line_id    = $row['line_id'];
            $column_id  = $row['column_id'];
            $content_id = $row['content_id'];
            $name       = $row['name'];

            $this->removeWidget($widget_id, $name, $content_id);
            if ($this->areThereAnyWidgetsLeftIfTheColumn($column_id)) {
                $this->reorderWidgets($column_id);
            } else {
                $position = $this->getCurrentPositionOfColumn($line_id, $column_id);
                $this->removeColumn($column_id);
                $this->adaptLayoutAfterColumnRemoval($line_id, $position);
                $this->reorderColumns($line_id);
            }
            $this->commit();
        } catch (\Exception $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    private function removeWidgetContent($name, $content_id)
    {
        if (! $content_id) {
            return;
        }

        $widget = \Widget::getInstance($name);
        if (! $widget) {
            return;
        }

        $widget->destroy($content_id);
    }

    private function getCurrentPositionOfColumn($line_id, $column_id)
    {
        $line_id   = $this->da->escapeInt($line_id);
        $column_id = $this->da->escapeInt($column_id);

        $sql = "SELECT id FROM dashboards_lines_columns WHERE line_id = $line_id";

        $position = 1;
        foreach ($this->retrieve($sql) as $row) {
            if ((int)$row['id'] === (int)$column_id) {
                break;
            }
            $position++;
        }

        return $position;
    }

    private function adaptLayoutAfterColumnRemoval($line_id, $removed_column_position)
    {
        $line_id = $this->da->escapeInt($line_id);

        $nb_columns = (int)$this->getNbColumns($line_id);

        if ($nb_columns > 3) {
            return;
        }

        if ($nb_columns === 0) {
            $this->removeLine($line_id);

            return;
        }

        $nb_columns              = $this->da->escapeInt($nb_columns);
        $removed_column_position = $this->da->escapeInt($removed_column_position);

        $sql = "UPDATE dashboards_lines
                SET layout = CASE
                    WHEN $nb_columns = 1 THEN 'one-column'
                    WHEN $nb_columns = 2
                        AND (
                            layout = 'three-columns-small-small-big' AND $removed_column_position = 1
                            OR layout = 'three-columns-small-small-big' AND $removed_column_position = 2
                            OR layout = 'three-columns-small-big-small' AND $removed_column_position = 3
                        )
                        THEN 'two-columns-small-big'
                    WHEN $nb_columns = 2
                        AND (
                            layout = 'three-columns-small-big-small' AND $removed_column_position = 1
                            OR layout = 'three-columns-big-small-small' AND $removed_column_position = 2
                            OR layout = 'three-columns-big-small-small' AND $removed_column_position = 3
                        )
                        THEN 'two-columns-big-small'
                    WHEN $nb_columns = 2 THEN 'two-columns'
                    ELSE 'three-columns'
                    END
                WHERE id = $line_id";

        $this->update($sql);
    }

    private function getNbColumns($line_id)
    {
        $sql = "SELECT count(*) as nb FROM dashboards_lines_columns WHERE line_id = $line_id";
        $row = $this->retrieveFirstRow($sql);

        return $row['nb'];
    }

    private function reorderColumns($line_id)
    {
        $line_id = $this->da->escapeInt($line_id);

        $this->update("SET @counter = 0");

        $sql = "UPDATE dashboards_lines_columns
                INNER JOIN (
                    SELECT @counter := @counter + 1 AS new_rank, dashboards_lines_columns.*
                    FROM dashboards_lines_columns
                    WHERE line_id = $line_id
                    ORDER BY rank, id
                ) as R1 USING(id)
                SET dashboards_lines_columns.rank = R1.new_rank";

        $this->update($sql);
    }

    private function reorderWidgets($column_id)
    {
        $column_id = $this->da->escapeInt($column_id);

        $this->update("SET @counter = 0");

        $sql = "UPDATE dashboards_lines_columns_widgets
            INNER JOIN (
                SELECT @counter := @counter + 1 AS new_rank, dashboards_lines_columns_widgets.*
                FROM dashboards_lines_columns_widgets
                WHERE column_id = $column_id
                ORDER BY rank, id
            ) as R1 USING(id)
            SET dashboards_lines_columns_widgets.rank = R1.new_rank";

        $this->update($sql);
    }

    private function removeLine($line_id)
    {
        $line_id = $this->da->escapeInt($line_id);

        $sql = "DELETE FROM dashboards_lines WHERE id = $line_id";

        $this->update($sql);
    }

    private function removeColumn($column_id)
    {
        $column_id = $this->da->escapeInt($column_id);

        $sql = "DELETE FROM dashboards_lines_columns WHERE id = $column_id";

        $this->update($sql);
    }

    public function deleteAllWidgetsInProjectDashboardInsideTransaction($project_id, $dashboard_id)
    {
        $this->deleteAllWidgetsInDashboard($project_id, $dashboard_id, 'project');
    }

    public function deleteAllWidgetsInUserDashboardInsideTransaction($user_id, $dashboard_id)
    {
        $this->deleteAllWidgetsInDashboard($user_id, $dashboard_id, 'user');
    }

    private function deleteAllWidgetsInDashboard($owner_id, $dashboard_id, $dashboard_type)
    {
        $this->checkThatDashboardBelongsToTheOwner($owner_id, $dashboard_type, $dashboard_id);

        $dashboard_id   = $this->da->quoteSmart($dashboard_id);
        $dashboard_type = $this->da->quoteSmart($dashboard_type);

        $sql = "SELECT widget.id, name, content_id
                FROM dashboards_lines_columns_widgets AS widget
                    INNER JOIN dashboards_lines_columns AS col ON (
                        widget.column_id = col.id
                    )
                    INNER JOIN dashboards_lines AS line ON (
                        col.line_id = line.id
                        AND line.dashboard_id = $dashboard_id
                        AND line.dashboard_type = $dashboard_type
                    )
                ";
        foreach ($this->retrieve($sql) as $widget) {
            $this->removeWidget($widget['id'], $widget['name'], $widget['content_id']);
        }

        $sql = "DELETE col.*, line.*
                FROM dashboards_lines_columns AS col
                    INNER JOIN dashboards_lines AS line ON (
                        col.line_id = line.id
                        AND line.dashboard_id = $dashboard_id
                        AND line.dashboard_type = $dashboard_type
                    )";
        $this->update($sql);
    }

    private function removeWidget($widget_id, $name, $content_id)
    {
        $widget_id = $this->da->escapeInt($widget_id);

        $sql = "DELETE FROM dashboards_lines_columns_widgets WHERE id = $widget_id";

        $this->update($sql);
        $this->removeWidgetContent($name, $content_id);
    }

    private function areThereAnyWidgetsLeftIfTheColumn($column_id)
    {
        $column_id = $this->da->escapeInt($column_id);

        $sql = "SELECT NULL FROM dashboards_lines_columns_widgets WHERE column_id = $column_id LIMIT 1";

        return count($this->retrieve($sql)) > 0;
    }

    private function searchInfoForWidget($dashboard_id, $dashboard_type, $widget_id)
    {
        $dashboard_id   = $this->da->escapeInt($dashboard_id);
        $dashboard_type = $this->da->quoteSmart($dashboard_type);
        $widget_id      = $this->da->escapeInt($widget_id);

        $sql = "SELECT *
                FROM dashboards_lines_columns_widgets AS widget
                    INNER JOIN dashboards_lines_columns AS col ON (
                        widget.column_id = col.id
                        AND widget.id = $widget_id
                    )
                    INNER JOIN dashboards_lines AS line ON (
                        line.id = col.line_id
                        AND line.dashboard_type = $dashboard_type
                        AND line.dashboard_id = $dashboard_id
                    )";

        return $this->retrieve($sql);
    }

    private function getFirstColumnIdOfDashboard($dashboard_id, $dashboard_type)
    {
        $id   = $this->da->escapeInt($dashboard_id);
        $type = $this->da->quoteSmart($dashboard_type);

        $sql = "SELECT col.id
                FROM dashboards_lines_columns AS col
                    INNER JOIN dashboards_lines AS line ON (
                        line.id = col.line_id
                        AND line.dashboard_id = $id
                        AND line.dashboard_type = $type
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
        $sql     = "INSERT INTO dashboards_lines_columns (line_id, rank) VALUES ($line_id, 1)";

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

    public function searchWidgetById($widget_id)
    {
        $widget_id = $this->da->escapeInt($widget_id);

        $sql = "SELECT *
                FROM dashboards_lines_columns_widgets
                WHERE id=$widget_id";

        return $this->retrieve($sql);
    }

    public function updateColumnIdByWidgetId($widget_id, $column_id)
    {
        $widget_id = $this->da->escapeInt($widget_id);
        $column_id = $this->da->escapeInt($column_id);

        $sql = "UPDATE dashboards_lines_columns_widgets
                SET column_id=$column_id
                WHERE id=$widget_id";

        return $this->retrieve($sql);
    }

    public function updateWidgetRankByWidgetId($widget_id, $rank)
    {
        $widget_id = $this->da->escapeInt($widget_id);
        $rank      = $this->da->escapeInt($rank);

        $sql = "UPDATE dashboards_lines_columns_widgets
                SET rank=$rank
                WHERE id=$widget_id";

        return $this->retrieve($sql);
    }
}
