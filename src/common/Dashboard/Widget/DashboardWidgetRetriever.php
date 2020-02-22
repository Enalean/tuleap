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

class DashboardWidgetRetriever
{
    /**
     * @var DashboardWidgetDao
     */
    private $dao;

    public function __construct(DashboardWidgetDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $dashboard_id
     * @param $dashboard_type
     * @return DashboardWidgetLine[]
     */
    public function getAllWidgets($dashboard_id, $dashboard_type)
    {
        $widgets_by_line = array();

        foreach ($this->dao->searchAllLinesByDashboardIdOrderedByRank($dashboard_id, $dashboard_type) as $line) {
            $widget_line = new DashboardWidgetLine(
                $line['id'],
                $line['layout'],
                array()
            );
            $widgets_by_line[] = $widget_line;
            $this->addColumnWidgetsByLine($widget_line);
        }

        return $widgets_by_line;
    }

    /**
     * @param $widget_id
     * @return DashboardWidget
     */
    public function getWidgetById($widget_id)
    {
        $row = $this->dao->searchWidgetById($widget_id)->getRow();
        if (! $row) {
            throw new WidgetNotFoundException();
        }

        return $this->instanciateFromRow($row);
    }

    /**
     * @param $line_id
     * @return array
     */
    public function getColumnsByLineById($line_id)
    {
        $columns = array();

        foreach ($this->dao->searchAllColumnsByLineIdOrderedByRank($line_id) as $column) {
            $columns[] = new DashboardWidgetColumn(
                $column['id'],
                $column['line_id'],
                array()
            );
        }

        return $columns;
    }

    /**
     * @param $column_id
     * @param DashboardWidgetLine[] $widgets_lines
     * @return null|DashboardWidgetColumn
     */
    public function getColumnByIdInWidgetsList($column_id, array $widgets_lines)
    {
        foreach ($widgets_lines as $line) {
            foreach ($line->getWidgetColumns() as $column) {
                if ($column->getId() === $column_id) {
                    return $column;
                }
            }
        }
        return null;
    }

    private function addColumnWidgetsByLine(DashboardWidgetLine $widget_line)
    {
        foreach ($this->dao->searchAllColumnsByLineIdOrderedByRank($widget_line->getId()) as $column) {
            $widget_column = new DashboardWidgetColumn(
                $column['id'],
                $column['line_id'],
                array()
            );
            $widget_line->addWidgetColumn($widget_column);
            $this->addWidgetsByColumn($widget_column);
        }
    }

    private function addWidgetsByColumn(DashboardWidgetColumn $widget_column)
    {
        foreach ($this->dao->searchAllWidgetByColumnId($widget_column->getId()) as $row) {
            $widget_column->addWidget($this->instanciateFromRow($row));
        }
    }

    /**
     * @return DashboardWidget
     */
    private function instanciateFromRow(array $row)
    {
        return new DashboardWidget(
            $row['id'],
            $row['name'],
            $row['content_id'],
            $row['column_id'],
            $row['rank'],
            $row['is_minimized']
        );
    }
}
