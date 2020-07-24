<?php
/**
 * Copyright Enalean (c) 2017-2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

namespace Tuleap\AgileDashboard\Kanban\TrackerReport;

use DataAccessObject;
use Exception;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;

class TrackerReportDao extends DataAccessObject
{

    public function __construct(?LegacyDataAccessInterface $da = null)
    {
        parent::__construct($da);

        $this->enableExceptionsOnError();
    }

    public function save($kanban_id, array $tracker_report_ids)
    {
        $this->startTransaction();

        try {
            $this->deleteAllForKanban($kanban_id);
            if (count($tracker_report_ids) > 0) {
                $this->addForKanban($kanban_id, $tracker_report_ids);
            }

            $this->commit();
        } catch (Exception $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    private function deleteAllForKanban($kanban_id)
    {
        $kanban_id = $this->da->escapeInt($kanban_id);

        $sql = "DELETE FROM plugin_agiledashboard_kanban_tracker_reports
                WHERE kanban_id = $kanban_id";

        return $this->update($sql);
    }

    private function addForKanban($kanban_id, array $tracker_report_ids)
    {
        $kanban_id = $this->da->escapeInt($kanban_id);
        $values = [];
        foreach ($tracker_report_ids as $report_id) {
            $report_id = $this->da->escapeInt($report_id);

            $values[] = "($kanban_id, $report_id)";
        }

        $values_statement = implode(',', $values);

        $sql = "REPLACE INTO plugin_agiledashboard_kanban_tracker_reports (kanban_id, report_id)
                VALUES $values_statement";
        return $this->update($sql);
    }

    public function deleteAllForReport($report_id)
    {
        $report_id = $this->da->escapeInt($report_id);

        $sql = "DELETE FROM plugin_agiledashboard_kanban_tracker_reports
                WHERE report_id = $report_id";

        return $this->update($sql);
    }

    public function searchReportIdsForKanban($kanban_id)
    {
        $kanban_id = $this->da->escapeInt($kanban_id);

        $sql = "SELECT report_id as id
                FROM plugin_agiledashboard_kanban_tracker_reports
                WHERE kanban_id = $kanban_id";

        return $this->retrieveIds($sql);
    }
}
