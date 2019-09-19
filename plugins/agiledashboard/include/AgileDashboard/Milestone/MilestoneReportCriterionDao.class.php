<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class MilestoneReportCriterionDao extends DataAccessObject
{

    public function save($report_id, $milestone_id)
    {
        $report_id    = $this->da->escapeInt($report_id);
        $milestone_id = $this->da->escapeInt($milestone_id);

        $sql = "REPLACE INTO plugin_agiledashboard_criteria (report_id, milestone_id)
                VALUES ($report_id, $milestone_id)";

        return $this->update($sql);
    }

    public function delete($report_id)
    {
        $report_id = $this->da->escapeInt($report_id);

        $sql = "DELETE FROM plugin_agiledashboard_criteria
                WHERE report_id = $report_id";

        return $this->update($sql);
    }

    public function searchByReportId($report_id)
    {
        $report_id = $this->da->escapeInt($report_id);

        $sql = "SELECT milestone_id
                FROM plugin_agiledashboard_criteria
                WHERE report_id = $report_id";

        return $this->retrieve($sql);
    }
}
