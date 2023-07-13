<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

declare(strict_types=1);

namespace Tuleap\Kanban\TrackerReport;

use Tuleap\Kanban\Kanban;
use Tracker_Report;

class TrackerReportUpdater
{
    public function __construct(private readonly TrackerReportDao $dao)
    {
    }

    public function save(Kanban $kanban, array $tracker_report_ids): void
    {
        $this->dao->save($kanban->getId(), $tracker_report_ids);
    }

    public function deleteAllForReport(Tracker_Report $report): void
    {
        $this->dao->deleteAllForReport((int) $report->getId());
    }
}
