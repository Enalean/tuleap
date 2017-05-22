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
    /**
     * @var WidgetCreator
     */
    private $widget_creator;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        WidgetCreator $widget_creator,
        DashboardWidgetRetriever $widget_retriever,
        DashboardWidgetReorder $widget_reorder
    ) {
        $this->csrf             = $csrf;
        $this->widget_retriever = $widget_retriever;
        $this->widget_reorder   = $widget_reorder;
        $this->widget_creator   = $widget_creator;
    }

    public function reorderWidgets(HTTPRequest $request, $dashboard_type)
    {
        $this->csrf->check();

        if (! $this->hasReorderRights($request, $dashboard_type)) {
            return;
        }

        $dashboard_id    = $request->get('dashboard-id');
        $widget_id       = $request->get('widget-id');
        $new_line_id     = $request->get('new-line-id');
        $new_column_id   = $request->get('new-column-id');
        $new_widget_rank = $request->get('new-widget-rank');
        $new_line_rank   = $request->get('new-line-rank');
        $new_column_rank = $request->get('new-column-rank');

        if ($dashboard_id === false || $widget_id === false || $new_widget_rank === false) {
            return;
        }

        $widgets_lines    = $this->widget_retriever->getAllWidgets($dashboard_id, $dashboard_type);
        $widget_to_update = $this->widget_retriever->getWidgetById($widget_id);
        $old_column_id    = $widget_to_update->getColumnId();

        if ($new_column_id === $old_column_id
            && $widget_to_update->getRank() === $new_widget_rank
        ) {
            return;
        }

        if (empty($new_line_id) && empty($new_column_id)) {
            $new_line_id = strval($this->widget_creator->createLine($dashboard_id, $dashboard_type, $widgets_lines, $new_line_rank));
        }

        if ($new_line_id && empty($new_column_id)) {
            $columns       = $this->widget_retriever->getColumnsByLineById($new_line_id);
            $new_column_id = strval($this->widget_creator->createColumn($new_line_id, $columns, $new_column_rank));
        }

        $widgets_lines = $this->widget_retriever->getAllWidgets($dashboard_id, $dashboard_type);

        $this->widget_reorder->reorderWidgets(
            $widgets_lines,
            $widget_to_update,
            $new_column_id,
            $new_widget_rank
        );
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
