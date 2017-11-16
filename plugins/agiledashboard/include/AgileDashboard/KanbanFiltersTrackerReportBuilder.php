<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

use AgileDashboard_Kanban;
use PFUser;
use Tracker_ReportFactory;

class KanbanFiltersTrackerReportBuilder
{
    /**
     * @var Tracker_ReportFactory
     */
    private $tracker_report_factory;
    /**
     * @var AgileDashboard_Kanban
     */
    private $kanban;
    /**
     * @var PFUser
     */
    private $user;

    public function __construct(
        PFUser $user,
        Tracker_ReportFactory $tracker_report_factory,
        AgileDashboard_Kanban $kanban
    ) {
        $this->tracker_report_factory = $tracker_report_factory;
        $this->kanban                 = $kanban;
        $this->user                   = $user;
    }

    public function build($selected_tracker_report_id)
    {
        $filters_tracker_report = array();
        $reports                = $this->tracker_report_factory->getReportsByTrackerId($this->kanban->getTrackerId(), $this->user->getId());
        foreach ($reports as $report) {
            $filter_tracker_report = array(
                'id'   => $report->getId(),
                'name' => $report->getName()
            );
            if ($report->getId() === $selected_tracker_report_id) {
                $filter_tracker_report['selected'] = true;
            }
            $filters_tracker_report[] = $filter_tracker_report;
        }
        return $filters_tracker_report;
    }
}
