<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use DataAccessObject;

class KanbanCumulativeFlowDiagramDao extends DataAccessObject
{
    public function searchKanbanItemsByDates($tracker_id, array $dates)
    {
        $tracker_id  = $this->da->escapeInt($tracker_id);
        $table_dates = $this->generateTableDates($dates);

        $sql = "SELECT ART.*, CVL.bindvalue_id AS column_id, FROM_UNIXTIME(dates.day, '%Y-%m-%d') AS day
                  FROM tracker_artifact AS ART
                  INNER JOIN (
                      $table_dates
                  ) dates
                  INNER JOIN tracker_changeset AS CHG1 ON (CHG1.artifact_id = ART.id AND CHG1.submitted_on <= dates.day)
                  LEFT JOIN tracker_changeset AS CHG2 ON (CHG2.artifact_id = ART.id AND CHG1.id < CHG2.id AND CHG2.submitted_on <= dates.day)
                  INNER JOIN tracker_changeset_value AS CV2 ON (CHG1.id = CV2.changeset_id)
                  INNER JOIN (
                    SELECT DISTINCT(field_id) FROM tracker_semantic_status WHERE tracker_id = $tracker_id
                  ) AS SS ON (CV2.field_id = SS.field_id)
                  INNER JOIN tracker_changeset_value_list AS CVL ON (CV2.id = CVL.changeset_value_id)
                  WHERE CHG2.id IS NULL AND ART.tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }

    public function searchKanbanItemsByDatesWithArtifactIds($tracker_id, array $artifact_ids, array $dates)
    {
        $tracker_id   = $this->da->escapeInt($tracker_id);
        $artifact_ids = $this->da->escapeIntImplode($artifact_ids);
        $table_dates  = $this->generateTableDates($dates);

        $sql = "SELECT ART.*, CVL.bindvalue_id AS column_id, FROM_UNIXTIME(dates.day, '%Y-%m-%d') AS day
                  FROM tracker_artifact AS ART
                  INNER JOIN (
                      $table_dates
                  ) dates
                  INNER JOIN tracker_changeset AS CHG1 ON (CHG1.artifact_id = ART.id AND CHG1.submitted_on <= dates.day)
                  LEFT JOIN tracker_changeset AS CHG2 ON (CHG2.artifact_id = ART.id AND CHG1.id < CHG2.id AND CHG2.submitted_on <= dates.day)
                  INNER JOIN tracker_changeset_value AS CV2 ON (CHG1.id = CV2.changeset_id)
                  INNER JOIN (
                    SELECT DISTINCT(field_id) FROM tracker_semantic_status WHERE tracker_id = $tracker_id
                  ) AS SS ON (CV2.field_id = SS.field_id)
                  INNER JOIN tracker_changeset_value_list AS CVL ON (CV2.id = CVL.changeset_value_id)
                  WHERE CHG2.id IS NULL
                    AND ART.tracker_id = $tracker_id
                    AND ART.id IN($artifact_ids)";
        return $this->retrieve($sql);
    }

    /**
     * @return string
     */
    private function generateTableDates(array $dates)
    {
        $table_dates_sql_parts = [];
        foreach ($dates as $date) {
            $table_dates_sql_parts[] = 'SELECT UNIX_TIMESTAMP(' . $this->getDa()->quoteSmart($date) . ') AS day';
        }

        return implode(' UNION ALL ', $table_dates_sql_parts);
    }
}
