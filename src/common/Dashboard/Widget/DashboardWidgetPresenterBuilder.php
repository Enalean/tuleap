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

class DashboardWidgetPresenterBuilder
{
    /**
     * @param DashboardWidgetLine[] $widgets_lines
     * @return array
     */
    public function getWidgetsPresenter(array $widgets_lines)
    {
        $lines_presenter = array();

        foreach ($widgets_lines as $line) {
            $columns_presenter = $this->getColumnsPresenterByLine($line);
            $lines_presenter[] = new DashboardWidgetLinePresenter($line->getLayout(), $columns_presenter);
        }

        return $lines_presenter;
    }

    /**
     * @param DashboardWidgetLine $line
     * @return array
     */
    private function getColumnsPresenterByLine(DashboardWidgetLine $line)
    {
        $columns_presenter = array();
        foreach ($line->getWidgetColumns() as $column) {
            $widgets_presenter = $this->getWidgetsPresenterByColumn($column);
            $columns_presenter[] = new DashboardWidgetColumnPresenter($widgets_presenter);
        }
        return $columns_presenter;
    }

    /**
     * @param DashboardWidgetColumn $column
     * @return array
     */
    private function getWidgetsPresenterByColumn(DashboardWidgetColumn $column)
    {
        $widgets_presenter = array();
        foreach ($column->getWidgets() as $widget) {
            $widgets_presenter[] = new DashboardWidgetPresenter();
        }
        return $widgets_presenter;
    }
}
