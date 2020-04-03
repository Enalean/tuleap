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
use HTTPRequest;

class WidgetDashboardController
{
    public const USER_DASHBOARD_TYPE    = 'user';
    public const PROJECT_DASHBOARD_TYPE = 'project';
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
    /**
     * @var WidgetCreator
     */
    private $widget_creator;
    /**
     * @var DashboardWidgetChecker
     */
    private $widget_checker;
    /**
     * @var DashboardWidgetDeletor
     */
    private $widget_deletor;
    /**
     * @var DashboardWidgetLineUpdater
     */
    private $widget_line_updater;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        WidgetCreator $widget_creator,
        DashboardWidgetRetriever $widget_retriever,
        DashboardWidgetReorder $widget_reorder,
        DashboardWidgetChecker $widget_checker,
        DashboardWidgetDeletor $widget_deletor,
        DashboardWidgetLineUpdater $widget_line_updater
    ) {
        $this->csrf                = $csrf;
        $this->widget_retriever    = $widget_retriever;
        $this->widget_reorder      = $widget_reorder;
        $this->widget_creator      = $widget_creator;
        $this->widget_checker      = $widget_checker;
        $this->widget_deletor      = $widget_deletor;
        $this->widget_line_updater = $widget_line_updater;
    }

    public function reorderWidgets(HTTPRequest $request, $dashboard_type)
    {
        $this->csrf->check();

        if (! $this->hasEditPermission($request, $dashboard_type)) {
            return;
        }

        $dashboard_id    = $request->get('dashboard-id');
        $widget_id       = $request->get('widget-id');
        $new_line_id     = $request->get('new-line-id');
        $new_column_id   = $request->get('new-column-id');
        $new_widget_rank = $request->get('new-widget-rank');
        $new_line_rank   = $request->get('new-line-rank');
        $new_column_rank = $request->get('new-column-rank');

        $new_ids     = array();
        $deleted_ids = array();

        if ($dashboard_id === false || $widget_id === false || $new_widget_rank === false) {
            return;
        }

        $widgets_lines    = $this->widget_retriever->getAllWidgets($dashboard_id, $dashboard_type);
        $widget_to_update = $this->widget_retriever->getWidgetById($widget_id);
        $old_column_id    = $widget_to_update->getColumnId();

        if (
            $new_column_id === $old_column_id
            && $widget_to_update->getRank() === $new_widget_rank
        ) {
            return;
        }

        list($new_line_id, $new_ids) = $this->createLineIfDoesNotExist(
            $dashboard_type,
            $new_line_id,
            $new_column_id,
            $dashboard_id,
            $widgets_lines,
            $new_line_rank,
            $new_ids
        );

        list($new_column_id, $new_ids) = $this->createColumnIfDoesNotExist(
            $new_line_id,
            $new_column_id,
            $new_column_rank,
            $new_ids
        );

        $widgets_lines = $this->widget_retriever->getAllWidgets($dashboard_id, $dashboard_type);
        $new_column    = $this->widget_retriever->getColumnByIdInWidgetsList($new_column_id, $widgets_lines);
        $old_column    = $this->widget_retriever->getColumnByIdInWidgetsList($widget_to_update->getColumnId(), $widgets_lines);

        if ($new_column === null || $old_column === null) {
            return;
        }

        $this->widget_reorder->reorderWidgets(
            $new_column,
            $old_column,
            $widget_to_update,
            $new_widget_rank
        );

        $deleted_ids = $this->deleteColumnIfEmpty($old_column, $deleted_ids);
        $deleted_ids = $this->deleteLineIfEmpty($old_column, $deleted_ids);

        $GLOBALS['Response']->sendJSON(array('new_ids' => $new_ids, 'deleted_ids' => $deleted_ids));
    }

    /**
     * @param $dashboard_type
     * @return bool
     */
    private function hasEditPermission(HTTPRequest $request, $dashboard_type)
    {
        $user = $request->getCurrentUser();
        if ($dashboard_type === self::USER_DASHBOARD_TYPE) {
            return $user->isLoggedIn();
        } elseif ($dashboard_type === self::PROJECT_DASHBOARD_TYPE) {
            $project = $request->getProject();
            return $user->isAdmin($project->getID());
        }

        return false;
    }

    /**
     * @param $dashboard_type
     * @param $new_line_id
     * @param $new_column_id
     * @param $dashboard_id
     * @param DashboardWidgetLine[] $widgets_lines
     * @param $new_line_rank
     * @param array $new_ids
     * @return array
     */
    private function createLineIfDoesNotExist(
        $dashboard_type,
        $new_line_id,
        $new_column_id,
        $dashboard_id,
        array $widgets_lines,
        $new_line_rank,
        array $new_ids
    ) {
        if (empty($new_line_id) && empty($new_column_id)) {
            $new_line_id = strval(
                $this->widget_creator->createLine(
                    $dashboard_id,
                    $dashboard_type,
                    $widgets_lines,
                    $new_line_rank
                )
            );
            $new_ids['new_line_id'] = $new_line_id;
        }
        return array($new_line_id, $new_ids);
    }

    /**
     * @param $new_line_id
     * @param $new_column_id
     * @param $new_column_rank
     * @param array $new_ids
     * @return array
     */
    private function createColumnIfDoesNotExist($new_line_id, $new_column_id, $new_column_rank, array $new_ids)
    {
        if ($new_line_id && empty($new_column_id)) {
            $columns       = $this->widget_retriever->getColumnsByLineById($new_line_id);
            $new_column_id = strval(
                $this->widget_creator->createColumn(
                    $new_line_id,
                    $columns,
                    $new_column_rank
                )
            );
            $new_ids['new_column_id'] = $new_column_id;
        }
        return array($new_column_id, $new_ids);
    }

    /**
     * @param array $deleted_ids
     * @return array
     */
    private function deleteColumnIfEmpty(DashboardWidgetColumn $old_column, array $deleted_ids)
    {
        if ($this->widget_checker->isEmptyColumn($old_column)) {
            $deleted_ids['deleted_column_id'] = $old_column->getId();
            $this->widget_deletor->deleteColumn($old_column);
        }
        return $deleted_ids;
    }

    /**
     * @param array $deleted_ids
     * @return array
     */
    private function deleteLineIfEmpty(DashboardWidgetColumn $old_column, array $deleted_ids)
    {
        if ($this->widget_checker->isEmptyLine($old_column)) {
            $deleted_ids['deleted_line_id'] = $old_column->getLineId();
            $this->widget_deletor->deleteLineByColumn($old_column);
        }
        return $deleted_ids;
    }

    public function editWidgetLine(HTTPRequest $request, $dashboard_type)
    {
        $this->csrf->check();

        $dashboard_line_id = $request->get('line-id');
        $layout            = $request->get('layout');

        if (! isset($dashboard_line_id) || ! isset($layout)) {
            return;
        }

        if (! $this->hasEditPermission($request, $dashboard_type)) {
            return;
        }

        $this->widget_line_updater->updateLayout($dashboard_line_id, $layout);
    }
}
