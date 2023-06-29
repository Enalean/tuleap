<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Kanban\TrackerReport;

use Tuleap\Kanban\Kanban;
use Tracker_ReportFactory;

class TrackerReportBuilder
{
    /**
     * @var Tracker_ReportFactory
     */
    private $tracker_report_factory;
    /**
     * @var Kanban
     */
    private $kanban;

    /** @var TrackerReportDao */
    private $tracker_report_dao;

    public function __construct(
        Tracker_ReportFactory $tracker_report_factory,
        Kanban $kanban,
        TrackerReportDao $tracker_report_dao,
    ) {
        $this->tracker_report_factory = $tracker_report_factory;
        $this->kanban                 = $kanban;
        $this->tracker_report_dao     = $tracker_report_dao;
    }

    public function build($selected_tracker_report_id)
    {
        $selectable_report_ids  = $this->tracker_report_dao->searchReportIdsForKanban($this->kanban->getId());
        $filters_tracker_report = [];
        $reports                = $this->tracker_report_factory->getReportsByTrackerId($this->kanban->getTrackerId(), null);
        foreach ($reports as $report) {
            $report_id             = (int) $report->getId();
            $filter_tracker_report = [
                'id'   => $report_id,
                'name' => $report->getName(),
            ];

            if (in_array($report_id, $selectable_report_ids)) {
                $filter_tracker_report['selectable'] = true;
            }

            if ($report_id === (int) $selected_tracker_report_id) {
                $filter_tracker_report['selected'] = true;
            }

            $filters_tracker_report[] = $filter_tracker_report;
        }
        return $filters_tracker_report;
    }
}
