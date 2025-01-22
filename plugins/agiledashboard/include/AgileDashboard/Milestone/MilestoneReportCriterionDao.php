<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_Milestone_MilestoneReportCriterionProvider;
use Tuleap\DB\DataAccessObject;

class MilestoneReportCriterionDao extends DataAccessObject
{
    public function save(int $report_id, int $milestone_id): void
    {
        $sql = 'REPLACE INTO plugin_agiledashboard_criteria (report_id, milestone_id) VALUES (?, ?)';

        $this->getDB()->run($sql, $report_id, $milestone_id);
    }

    public function delete(int $report_id): void
    {
        $sql = 'DELETE FROM plugin_agiledashboard_criteria WHERE report_id = ?';

        $this->getDB()->run($sql, $report_id);
    }

    /**
     * @return array{milestone_id: int}|null
     */
    public function searchByReportId(int $report_id): ?array
    {
        $sql = 'SELECT milestone_id FROM plugin_agiledashboard_criteria WHERE report_id = ?';

        return $this->getDB()->row($sql, $report_id);
    }

    public function updateAllUnplannedValueToAnyInProject(int $project_id): void
    {
        $sql = <<<SQL
        DELETE plugin_agiledashboard_criteria.*
        FROM plugin_agiledashboard_criteria
            INNER JOIN tracker_report ON (plugin_agiledashboard_criteria.report_id = tracker_report.id)
            INNER JOIN tracker ON (tracker_report.tracker_id = tracker.id)
        WHERE plugin_agiledashboard_criteria.milestone_id = ?
            AND tracker.group_id = ?
        SQL;

        $this->getDB()->run($sql, AgileDashboard_Milestone_MilestoneReportCriterionProvider::UNPLANNED, $project_id);
    }
}
