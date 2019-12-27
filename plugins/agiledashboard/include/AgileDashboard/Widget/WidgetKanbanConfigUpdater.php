<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\AgileDashboard\Widget;

use Tracker_Report;

class WidgetKanbanConfigUpdater
{
    /**
     * @var WidgetKanbanConfigDAO
     */
    private $config_dao;

    public function __construct(
        WidgetKanbanConfigDAO $config_dao
    ) {
        $this->config_dao = $config_dao;
    }

    public function updateConfiguration(
        $widget_id,
        $tracker_report_id
    ) {
        if (! $tracker_report_id) {
            return $this->config_dao->deleteConfigForWidgetId($widget_id);
        }

        $this->config_dao->createNewConfigForWidgetId(
            $widget_id,
            $tracker_report_id
        );
    }

    public function deleteConfigurationForWidgetMatchingReportId(Tracker_Report $report)
    {
        $this->config_dao->deleteConfigurationForWidgetMatchingReportId($report->getId());
    }
}
