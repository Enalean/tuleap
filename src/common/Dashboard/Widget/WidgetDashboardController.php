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

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;

class WidgetDashboardController
{
    const USER_DASHBOARD_TYPE    = 'user';
    const PROJECT_DASHBOARD_TYPE = 'project';
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    /**
     * @var DashboardWidgetRetriever
     */
    private $widget_retriever;
    /**
     * @var DashboardWidgetReorder
     */
    private $widget_reorder;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        DashboardWidgetRetriever $widget_retriever,
        DashboardWidgetReorder $widget_reorder
    ) {
        $this->csrf             = $csrf;
        $this->widget_retriever = $widget_retriever;
        $this->widget_reorder   = $widget_reorder;
    }

    public function reorderWidgets(HTTPRequest $request, $dashboard_type)
    {
        $this->csrf->check();

        if (! $this->hasReorderRights($request, $dashboard_type)) {
            return;
        }

        $dashboard_id = $request->get('dashboard-id');
        $widget_id = $request->get('widget-id');
        $new_column_id = $request->get('new-column-id');
        $new_rank = $request->get('new-rank');

        if ($dashboard_id === false || $widget_id === false || $new_column_id === false || $new_rank === false) {
            return;
        }

        $widgets_lines = $this->widget_retriever->getAllWidgets($dashboard_id, $dashboard_type);
        $widget_to_update = $this->widget_retriever->getWidgetById($widget_id);

        if ($new_column_id === $widget_to_update->getColumnId()
            && $widget_to_update->getRank() === $new_rank
        ) {
            return;
        }

        $this->reorderByLine($widgets_lines, $widget_to_update, $new_column_id, $new_rank);
    }

    /**
     * @param array $widgets_lines
     * @param DashboardWidget $widget_to_update
     * @param $new_column_id
     * @param $new_rank
     */
    private function reorderByLine(
        array $widgets_lines,
        DashboardWidget $widget_to_update,
        $new_column_id,
        $new_rank
    ) {
        foreach ($widgets_lines as $line) {
            $this->reorderByColumn($line, $widget_to_update, $new_column_id, $new_rank);
        }
    }

    /**
     * @param DashboardWidgetLine $line
     * @param DashboardWidget $widget_to_update
     * @param $new_column_id
     * @param $new_rank
     */
    private function reorderByColumn(
        DashboardWidgetLine $line,
        DashboardWidget $widget_to_update,
        $new_column_id,
        $new_rank
    ) {
        foreach ($line->getWidgetColumns() as $column) {
            if ($column->getId() === $new_column_id) {
                $this->updateRankToEnterInColumn($widget_to_update, $new_rank, $column);
            } else if ($column->getId() === $widget_to_update->getColumnId()) {
                $this->widget_reorder->updateColumnIdByWidgetId($widget_to_update->getId(), $new_column_id);
                $this->updateWidgetRankToLeaveColumn($widget_to_update, $column);
            }
        }
    }

    /**
     * @param DashboardWidget $widget_to_update
     * @param DashboardWidgetColumn $column
     */
    private function updateWidgetRankToLeaveColumn(
        DashboardWidget $widget_to_update,
        DashboardWidgetColumn $column
    ) {
        $widgets = $this->removeWidgetInColumn($widget_to_update, $column);
        $this->updateRank($widgets);
    }

    /**
     * @param DashboardWidget $widget_to_update
     * @param $new_rank
     * @param $column
     */
    private function updateRankToEnterInColumn(
        DashboardWidget $widget_to_update,
        $new_rank,
        DashboardWidgetColumn $column
    ) {
        $widgets = $this->removeWidgetInColumn($widget_to_update, $column);
        array_splice($widgets, $new_rank, 0, array($widget_to_update));
        $this->updateRank($widgets);
    }

    /**
     * @param DashboardWidget $widget_to_update
     * @param DashboardWidgetColumn $column
     * @return array
     */
    private function removeWidgetInColumn(DashboardWidget $widget_to_update, DashboardWidgetColumn $column)
    {
        $widgets = array();
        foreach ($column->getWidgets() as $widget) {
            if ($widget->getId() !== $widget_to_update->getId()) {
                $widgets[] = $widget;
            }
        }
        return $widgets;
    }

    /**
     * @param DashboardWidget[] $widgets
     */
    private function updateRank(array $widgets)
    {
        foreach ($widgets as $index => $widget) {
            $this->widget_reorder->updateWidgetRankByWidgetId($widget->getId(), $index);
        }
    }

    /**
     * @param HTTPRequest $request
     * @param $dashboard_type
     * @return bool
     */
    private function hasReorderRights(HTTPRequest $request, $dashboard_type)
    {
        $user = $request->getCurrentUser();
        if ($dashboard_type === self::USER_DASHBOARD_TYPE) {
            return $user->isLoggedIn();
        } else if ($dashboard_type === self::PROJECT_DASHBOARD_TYPE) {
            $project = $request->getProject();
            return $user->isAdmin($project->getID());
        }

        return false;
    }
}
