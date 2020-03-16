<?php
/**
 * Copyright (c) Enalean, 2017-2018. All rights reserved
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

use DataAccessObject;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;
use Tuleap\Widget\WidgetFactory;

class DashboardWidgetDao extends DataAccessObject
{
    private $legacy_types_to_new_types = array(
        ProjectDashboardController::LEGACY_DASHBOARD_TYPE => 'project',
        UserDashboardController::LEGACY_DASHBOARD_TYPE    => 'user'
    );

    /**
     * @var WidgetFactory
     */
    private $widget_factory;

    public function __construct(
        WidgetFactory $widget_factory,
        ?LegacyDataAccessInterface $da = null
    ) {
        parent::__construct($da);
        $this->enableExceptionsOnError();

        $this->widget_factory = $widget_factory;
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

    public function searchUsedWidgetsContentByDashboardId($dashboard_id, $dashboard_type)
    {
        $dashboard_id   = $this->da->escapeInt($dashboard_id);
        $dashboard_type = $this->da->quoteSmart($dashboard_type);

        $sql = "SELECT dashboards_lines_columns_widgets.name
                FROM dashboards_lines
                INNER JOIN dashboards_lines_columns
                  ON (dashboards_lines.id = dashboards_lines_columns.line_id)
                INNER JOIN dashboards_lines_columns_widgets
                  ON (dashboards_lines_columns.id = dashboards_lines_columns_widgets.column_id)
                WHERE dashboards_lines.dashboard_id = $dashboard_id
                AND dashboard_type = $dashboard_type";

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

    public function minimizeWidget($owner_id, $dashboard_id, $dashboard_type, $widget_id)
    {
        $is_minimized = 1;
        $this->saveMinimizedState($owner_id, $dashboard_id, $dashboard_type, $widget_id, $is_minimized);
    }

    public function maximizeWidget($owner_id, $dashboard_id, $dashboard_type, $widget_id)
    {
        $is_minimized = 0;
        $this->saveMinimizedState($owner_id, $dashboard_id, $dashboard_type, $widget_id, $is_minimized);
    }

    private function saveMinimizedState($owner_id, $dashboard_id, $dashboard_type, $widget_id, $is_minimized)
    {
        $this->startTransaction();

        try {
            $this->checkThatDashboardBelongsToTheOwner($owner_id, $dashboard_type, $dashboard_id);

            $widget_id    = $this->da->escapeInt($widget_id);
            $is_minimized = $this->da->escapeInt($is_minimized);

            $sql = "UPDATE dashboards_lines_columns_widgets
                    SET is_minimized = $is_minimized
                    WHERE id = $widget_id";
            $this->update($sql);

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
            $this->adaptLayoutAfterWidgetRemoval($column_id, $line_id);
            $this->commit();
        } catch (\Exception $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    private function adaptLayoutAfterWidgetRemoval($column_id, $line_id)
    {
        if ($this->areThereAnyWidgetsLeftIfTheColumn($column_id)) {
            $this->reorderWidgets($column_id);
        } else {
            $position = $this->getCurrentPositionOfColumn($line_id, $column_id);
            $this->removeColumn($column_id);
            $this->adaptLayoutAfterColumnRemoval($line_id, $position);
            $this->reorderColumns($line_id);
        }
    }

    private function removeWidgetContent($name, $content_id)
    {
        if (! $content_id) {
            return;
        }

        $widget = $this->widget_factory->getInstanceByWidgetName($name);
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
            if ((int) $row['id'] === (int) $column_id) {
                break;
            }
            $position++;
        }

        return $position;
    }

    private function adaptLayoutAfterColumnRemoval($line_id, $removed_column_position)
    {
        $line_id = $this->da->escapeInt($line_id);

        $nb_columns = (int) $this->getNbColumns($line_id);

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

    public function reorderLines($line_id)
    {
        $line_id = $this->da->escapeInt($line_id);

        $this->update("SET @counter = 0");

        $sql = "UPDATE dashboards_lines
                INNER JOIN (
                    SELECT @counter := @counter + 1 AS new_rank, dashboards_lines.*
                    FROM dashboards_lines
                    WHERE id = $line_id
                    ORDER BY rank, id
                ) as R1 USING(id)
                SET dashboards_lines.rank = R1.new_rank";

        $this->update($sql);
    }

    public function reorderColumns($line_id)
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

    public function removeLine($line_id)
    {
        $line_id = $this->da->escapeInt($line_id);

        $sql = "DELETE FROM dashboards_lines WHERE id = $line_id";

        $this->update($sql);
    }

    public function removeColumn($column_id)
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

    public function createLine($dashboard_id, $dashboard_type, $new_line_rank)
    {
        $dashboard_id   = $this->da->escapeInt($dashboard_id);
        $dashboard_type = $this->da->quoteSmart($dashboard_type);
        $new_line_rank  = $this->da->escapeInt($new_line_rank);

        $sql = "INSERT INTO dashboards_lines (dashboard_id, dashboard_type, layout, rank)
                VALUES ($dashboard_id, $dashboard_type, 'one-column', $new_line_rank)";

        return $this->updateAndGetLastId($sql);
    }

    public function createColumn($line_id, $new_column_rank)
    {
        $line_id         = $this->da->escapeInt($line_id);
        $new_column_rank = $this->da->escapeInt($new_column_rank);

        $sql = "INSERT INTO dashboards_lines_columns (line_id, rank) VALUES ($line_id, $new_column_rank)";

        return $this->updateAndGetLastId($sql);
    }

    public function insertWidgetInColumnWithRank($name, $content_id, $column_id, $rank)
    {
        $column_id  = $this->da->escapeInt($column_id);
        $content_id = $this->da->escapeInt($content_id);
        $rank       = $this->da->escapeInt($rank);
        $name       = $this->da->quoteSmart($name);

        $sql = "INSERT INTO dashboards_lines_columns_widgets (column_id, rank, name, content_id)
                VALUES ($column_id, $rank, $name, $content_id)";
        return $this->update($sql);
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

    public function checkThatDashboardBelongsToTheOwner($owner_id, $dashboard_type, $dashboard_id)
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

    public function updateWidgetRankByLineId($line_id, $rank)
    {
        $line_id = $this->da->escapeInt($line_id);
        $rank    = $this->da->escapeInt($rank);

        $sql = "UPDATE dashboards_lines
                SET rank=$rank
                WHERE id=$line_id";

        return $this->retrieve($sql);
    }

    public function updateWidgetRankByColumnId($column_id, $rank)
    {
        $column_id = $this->da->escapeInt($column_id);
        $rank      = $this->da->escapeInt($rank);

        $sql = "UPDATE dashboards_lines_columns
                SET rank=$rank
                WHERE id=$column_id";

        return $this->retrieve($sql);
    }

    public function searchLineColumnsByColumnId($column_id)
    {
        $column_id = $this->da->escapeInt($column_id);

        $sql = "SELECT lines_columns.*
                FROM dashboards_lines_columns AS lines_columns
                INNER JOIN dashboards_lines_columns AS lines_columns_bis
                ON lines_columns_bis.line_id=lines_columns.line_id
                WHERE lines_columns.id=$column_id";

        return $this->retrieve($sql);
    }

    public function searchColumnsByColumnId($column_id)
    {
        $column_id = $this->da->escapeInt($column_id);

        $sql = "SELECT lines_columns.*
                FROM dashboards_lines_columns AS lines_columns
                INNER JOIN dashboards_lines_columns_widgets AS lines_columns_widgets
                ON lines_columns_widgets.column_id=lines_columns.id
                WHERE lines_columns.id=$column_id";

        return $this->retrieve($sql);
    }

    public function searchWidgetInDashboardById($widget_id)
    {
        $widget_id = $this->da->escapeInt($widget_id);

        $sql = "SELECT widget.id,
                       widget.name,
                       widget.content_id,
                       line.dashboard_id,
                       line.dashboard_type,
                       project_dashboards.project_id,
                       project.unix_group_name,
                       user_dashboards.user_id
                FROM dashboards_lines_columns_widgets AS widget
                    INNER JOIN dashboards_lines_columns AS col ON(
                        widget.id = $widget_id
                        AND col.id = widget.column_id
                    )
                    INNER JOIN dashboards_lines AS line ON(
                        line.id = col.line_id
                    )
                    LEFT JOIN project_dashboards ON(
                        line.dashboard_type = 'project'
                        AND project_dashboards.id = line.dashboard_id
                    )
                    LEFT JOIN groups AS project ON(
                        project.group_id = project_dashboards.project_id
                    )
                    LEFT JOIN user_dashboards ON(
                        line.dashboard_type = 'user'
                        AND user_dashboards.id = line.dashboard_id
                    )";

        return $this->retrieve($sql);
    }

    public function updateLayout($line_id, $layout)
    {
        $line_id = $this->da->escapeInt($line_id);
        $layout  = $this->da->quoteSmart($layout);

        $sql = "UPDATE dashboards_lines
                  SET layout = $layout
                WHERE id = $line_id";

        return $this->update($sql);
    }

    public function duplicateLine($template_dashboard_id, $new_dashboard_id, $template_line_id, $dashboard_type)
    {
        $template_dashboard_id = $this->da->escapeInt($template_dashboard_id);
        $new_dashboard_id      = $this->da->escapeInt($new_dashboard_id);
        $template_line_id      = $this->da->escapeInt($template_line_id);
        $dashboard_type        = $this->da->quoteSmart($dashboard_type);

        $sql = "INSERT INTO dashboards_lines (dashboard_id, dashboard_type, layout, rank)
                SELECT $new_dashboard_id, dashboard_type, layout, rank
                FROM dashboards_lines
                WHERE dashboard_type = $dashboard_type
                  AND dashboard_id = $template_dashboard_id
                  AND id = $template_line_id";

        return $this->updateAndGetLastId($sql);
    }

    public function duplicateColumn($template_line_id, $new_line_id, $template_colmun_id)
    {
        $template_line_id   = $this->da->escapeInt($template_line_id);
        $new_line_id        = $this->da->escapeInt($new_line_id);
        $template_colmun_id = $this->da->escapeInt($template_colmun_id);

        $sql = "INSERT INTO dashboards_lines_columns (line_id, rank)
                SELECT $new_line_id, rank
                FROM dashboards_lines_columns
                WHERE line_id = $template_line_id
                  AND id = $template_colmun_id";

        return $this->updateAndGetLastId($sql);
    }

    public function duplicateWidget($new_column_id, $template_widget_id, $new_content_id)
    {
        $new_column_id      = $this->da->escapeInt($new_column_id);
        $new_content_id     = $this->da->escapeInt($new_content_id);
        $template_widget_id = $this->da->escapeInt($template_widget_id);

        $sql = "INSERT INTO dashboards_lines_columns_widgets (column_id, rank, name, content_id, is_minimized)
                SELECT $new_column_id, rank, name, $new_content_id, is_minimized
                FROM dashboards_lines_columns_widgets
                WHERE id = $template_widget_id";

        return $this->update($sql);
    }

    public function removeOrphanWidgetsByNames(array $names)
    {
        $names = $this->da->quoteSmartImplode(',', $names);

        $sql = "DELETE FROM dashboards_lines_columns_widgets WHERE name IN ($names)";

        $this->update($sql);

        $this->adaptLayoutAfterOrphanWidgetRemoval();
    }

    private function adaptLayoutAfterOrphanWidgetRemoval()
    {
        $this->startTransaction();
        try {
            foreach ($this->getEmptyColumns() as $column_row) {
                $this->adaptLayoutAfterWidgetRemoval($column_row['id'], $column_row['line_id']);
            }
            $this->commit();
        } catch (\Exception $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    private function getEmptyColumns()
    {
        $sql = "SELECT col.*
                FROM dashboards_lines_columns AS col
                  LEFT JOIN dashboards_lines_columns_widgets AS widget ON (col.id = widget.column_id)
                WHERE widget.id IS NULL";
        return $this->retrieve($sql);
    }

    /**
     * @param int $user_id
     * @param \Widget[] $widgets
     * @throws \Exception
     */
    public function createDefaultDashboardForUser($user_id, array $widgets)
    {
        $this->startTransaction();
        try {
            $dashboard_id = $this->createUserDashboard($user_id);

            $nb_widgets = count($widgets);
            $line_id = $this->createDefaultLine($dashboard_id, $nb_widgets);

            $rank = 0;
            foreach ($widgets as $widget_name) {
                $column_id = $this->createColumn($line_id, $rank++);
                $this->insertWidgetInColumn($widget_name, 0, $column_id);
            }
            $this->commit();
        } catch (\Exception $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    private function createUserDashboard($user_id)
    {
        $user_id = $this->da->escapeInt($user_id);
        $name    = $this->da->quoteSmart('My Dashboard');

        $sql = "INSERT INTO user_dashboards (user_id, name) VALUES ($user_id, $name)";

        return $this->updateAndGetLastId($sql);
    }

    private function createDefaultLine($dashboard_id, $nb_widgets)
    {
        $line_id = $this->createLine($dashboard_id, UserDashboardController::DASHBOARD_TYPE, 0);
        $this->adjustLayoutAccordinglyToNumberOfWidgets($nb_widgets, $line_id);

        return $line_id;
    }

    public function adjustLayoutAccordinglyToNumberOfWidgets($nb_widgets, $line_id)
    {
        if ($nb_widgets < 2) {
            return;
        } elseif ($nb_widgets === 2) {
            $layout = 'two-columns';
        } elseif ($nb_widgets === 3) {
            $layout = 'three-columns';
        } else {
            $layout = 'too-many-columns';
        }
        $layout  = $this->da->quoteSmart($layout);
        $line_id = $this->da->escapeInt($line_id);

        $sql = "UPDATE dashboards_lines SET layout = $layout WHERE id = $line_id";

        $this->update($sql);
    }
}
