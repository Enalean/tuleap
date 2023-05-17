<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap;

use ParagonIE\EasyDB\EasyDB;

final class FilterReportDao extends \Tuleap\DB\DataAccessObject
{
    public function getReportIdToFilterArtifacts(int $widget_id): ?int
    {
        return $this->getDB()->tryFlatTransaction(static function (EasyDB $db) use ($widget_id): ?int {
            $nb_selected_trackers = $db->cell(
                "SELECT count(*) FROM plugin_roadmap_widget_trackers WHERE plugin_roadmap_widget_id = ?",
                $widget_id
            );
            if ($nb_selected_trackers !== 1) {
                return null;
            }

            $report_id = $db->cell(
                "SELECT report_id
                    FROM plugin_roadmap_widget_filter AS filter
                        INNER JOIN tracker_report AS report ON (report.id = filter.report_id AND report.user_id IS NULL)
                        INNER JOIN plugin_roadmap_widget_trackers USING (tracker_id)
                    WHERE widget_id = ?",
                $widget_id
            );

            return $report_id ?: null;
        });
    }

    public function deleteByReport(int $report_id): void
    {
        $this->getDB()->delete('plugin_roadmap_widget_filter', ['report_id' => $report_id]);
    }
}
