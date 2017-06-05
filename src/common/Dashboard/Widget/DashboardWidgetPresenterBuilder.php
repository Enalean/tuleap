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

use Tuleap\Widget\WidgetFactory;

class DashboardWidgetPresenterBuilder
{

    /**
     * @var WidgetFactory
     */
    private $widget_factory;

    public function __construct(WidgetFactory $widget_factory)
    {
        $this->widget_factory = $widget_factory;
    }

    /**
     * @param DashboardWidgetLine[] $widgets_lines
     * @return array
     */
    public function getWidgetsPresenter(OwnerInfo $owner_info, array $widgets_lines)
    {
        $lines_presenter = array();

        foreach ($widgets_lines as $line) {
            $columns_presenter = $this->getColumnsPresenterByLine($owner_info, $line);
            $lines_presenter[] = new DashboardWidgetLinePresenter(
                $line->getId(),
                $line->getLayout(),
                $columns_presenter
            );
        }

        return $lines_presenter;
    }

    /**
     * @param DashboardWidgetLine $line
     * @return array
     */
    private function getColumnsPresenterByLine(OwnerInfo $owner_info, DashboardWidgetLine $line)
    {
        $columns_presenter = array();
        foreach ($line->getWidgetColumns() as $column) {
            $widgets_presenter = $this->getWidgetsPresenterByColumn($owner_info, $column);
            $columns_presenter[] = new DashboardWidgetColumnPresenter($column->getId(), $widgets_presenter);
        }
        return $columns_presenter;
    }

    /**
     * @param DashboardWidgetColumn $column
     * @return array
     */
    private function getWidgetsPresenterByColumn(OwnerInfo $owner_info, DashboardWidgetColumn $column)
    {
        $widgets_presenter = array();
        foreach ($column->getWidgets() as $dashboard_widget) {
            $widget = $this->widget_factory->getInstanceByWidgetName($dashboard_widget->getName());
            if ($widget) {
                $widget->owner_id   = $owner_info->getId();
                $widget->owner_type = $owner_info->getType();
                $widget->loadContent($dashboard_widget->getContentId());
                $widgets_presenter[] = new DashboardWidgetPresenter($dashboard_widget, $widget);
            }
        }
        return $widgets_presenter;
    }
}
