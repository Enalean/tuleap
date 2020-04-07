<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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

use Tuleap\Dashboard\Dashboard;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Widget\WidgetFactory;

class DashboardWidgetPresenterBuilder
{
    /**
     * @var WidgetFactory
     */
    private $widget_factory;

    /**
     * @var DisabledProjectWidgetsChecker
     */
    private $disabled_project_widgets_checker;

    public function __construct(
        WidgetFactory $widget_factory,
        DisabledProjectWidgetsChecker $disabled_project_widgets_checker
    ) {
        $this->widget_factory = $widget_factory;
        $this->disabled_project_widgets_checker = $disabled_project_widgets_checker;
    }

    /**
     * @param DashboardWidgetLine[] $widgets_lines
     * @param bool $can_update_dashboards
     *
     * @return DashboardWidgetLinePresenter[]
     */
    public function getWidgetsPresenter(
        Dashboard $dashboard,
        OwnerInfo $owner_info,
        array $widgets_lines,
        $can_update_dashboards
    ) {
        $lines_presenter = array();

        foreach ($widgets_lines as $line) {
            $columns_presenter = $this->getColumnsPresenterByLine($dashboard, $owner_info, $line, $can_update_dashboards);
            $lines_presenter[] = new DashboardWidgetLinePresenter(
                $line->getId(),
                $line->getLayout(),
                $columns_presenter
            );
        }

        return $lines_presenter;
    }

    /**
     * @return DashboardWidgetColumnPresenter[]
     */
    private function getColumnsPresenterByLine(
        Dashboard $dashboard,
        OwnerInfo $owner_info,
        DashboardWidgetLine $line,
        $can_update_dashboards
    ) {
        $columns_presenter = array();
        foreach ($line->getWidgetColumns() as $column) {
            $widgets_presenter = $this->getWidgetsPresenterByColumn($dashboard, $owner_info, $column, $can_update_dashboards);
            $columns_presenter[] = new DashboardWidgetColumnPresenter($column->getId(), $widgets_presenter);
        }
        return $columns_presenter;
    }

    /**
     * @return DashboardWidgetPresenter[]
     */
    private function getWidgetsPresenterByColumn(
        Dashboard $dashboard,
        OwnerInfo $owner_info,
        DashboardWidgetColumn $column,
        $can_update_dashboards
    ) {
        $widgets_presenter = array();
        foreach ($column->getWidgets() as $dashboard_widget) {
            $widget = $this->widget_factory->getInstanceByWidgetName($dashboard_widget->getName());
            if (
                $widget &&
                $widget->isAvailable() &&
                $this->disabled_project_widgets_checker->checkWidgetIsDisabledFromDashboard($widget, $dashboard) === false
            ) {
                $widget->owner_id   = $owner_info->getId();
                $widget->owner_type = $owner_info->getType();
                $widget->loadContent($dashboard_widget->getContentId());
                $widgets_presenter[] = new DashboardWidgetPresenter(
                    $dashboard,
                    $dashboard_widget,
                    $widget,
                    $can_update_dashboards
                );
            }
        }
        return $widgets_presenter;
    }
}
