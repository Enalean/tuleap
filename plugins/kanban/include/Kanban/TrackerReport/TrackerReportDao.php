<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

declare(strict_types=1);

namespace Tuleap\Kanban\TrackerReport;

use Tuleap\DB\DataAccessObject;

final class TrackerReportDao extends DataAccessObject
{
    public function save(int $kanban_id, array $tracker_report_ids): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($kanban_id, $tracker_report_ids) {
            $this->deleteAllForKanban($kanban_id);

            if (count($tracker_report_ids) > 0) {
                $this->addForKanban($kanban_id, $tracker_report_ids);
            }
        });
    }

    private function deleteAllForKanban(int $kanban_id): void
    {
        $sql = "DELETE FROM plugin_agiledashboard_kanban_tracker_reports
                WHERE kanban_id = ?";

        $this->getDB()->run($sql, $kanban_id);
    }

    private function addForKanban(int $kanban_id, array $tracker_report_ids): void
    {
        $values     = [];
        $parameters = [];
        foreach ($tracker_report_ids as $report_id) {
            $values[]     = "(?, ?)";
            $parameters[] = $kanban_id;
            $parameters[] = $report_id;
        }

        $values_statement = implode(',', $values);

        $sql = "REPLACE INTO plugin_agiledashboard_kanban_tracker_reports (kanban_id, report_id)
                VALUES $values_statement";
        $this->getDB()->run($sql, ...$parameters);
    }

    public function deleteAllForReport(int $report_id): void
    {
        $sql = "DELETE FROM plugin_agiledashboard_kanban_tracker_reports
                WHERE report_id = ?";

        $this->getDB()->run($sql, $report_id);
    }

    /**
     * @return int[]
     */
    public function searchReportIdsForKanban(int $kanban_id): array
    {
        $sql = "SELECT report_id as id
                FROM plugin_agiledashboard_kanban_tracker_reports
                WHERE kanban_id = ?";

        return $this->getDB()->column($sql, [$kanban_id]);
    }
}
