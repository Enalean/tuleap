<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

class KanbanCumulativeFlowDiagramDao extends DataAccessObject
{
    public function searchKanbanItemsByDates(int $tracker_id, array $dates): array
    {
        $table_dates = $this->generateTableDates($dates);

        $sql = "SELECT ART.*, CVL.bindvalue_id AS column_id, FROM_UNIXTIME(dates.day, '%Y-%m-%d') AS day
                  FROM tracker_artifact AS ART
                  INNER JOIN (
                      $table_dates->sql
                  ) dates
                  INNER JOIN tracker_changeset AS CHG1 ON (CHG1.artifact_id = ART.id AND CHG1.submitted_on <= dates.day)
                  LEFT JOIN tracker_changeset AS CHG2 ON (CHG2.artifact_id = ART.id AND CHG1.id < CHG2.id AND CHG2.submitted_on <= dates.day)
                  INNER JOIN tracker_changeset_value AS CV2 ON (CHG1.id = CV2.changeset_id)
                  INNER JOIN (
                    SELECT DISTINCT(field_id) FROM tracker_semantic_status WHERE tracker_id = ?
                  ) AS SS ON (CV2.field_id = SS.field_id)
                  INNER JOIN tracker_changeset_value_list AS CVL ON (CV2.id = CVL.changeset_value_id)
                  WHERE CHG2.id IS NULL AND ART.tracker_id = ?";

        $parameters = [...$table_dates->parameters, $tracker_id, $tracker_id];

        return $this->getDB()->run($sql, ...$parameters);
    }

    public function searchKanbanItemsByDatesWithArtifactIds(int $tracker_id, array $artifact_ids, array $dates): array
    {
        $in          = EasyStatement::open()->in('?*', $artifact_ids);
        $table_dates = $this->generateTableDates($dates);

        $sql = "SELECT ART.*, CVL.bindvalue_id AS column_id, FROM_UNIXTIME(dates.day, '%Y-%m-%d') AS day
                  FROM tracker_artifact AS ART
                  INNER JOIN (
                      $table_dates->sql
                  ) dates
                  INNER JOIN tracker_changeset AS CHG1 ON (CHG1.artifact_id = ART.id AND CHG1.submitted_on <= dates.day)
                  LEFT JOIN tracker_changeset AS CHG2 ON (CHG2.artifact_id = ART.id AND CHG1.id < CHG2.id AND CHG2.submitted_on <= dates.day)
                  INNER JOIN tracker_changeset_value AS CV2 ON (CHG1.id = CV2.changeset_id)
                  INNER JOIN (
                    SELECT DISTINCT(field_id) FROM tracker_semantic_status WHERE tracker_id = ?
                  ) AS SS ON (CV2.field_id = SS.field_id)
                  INNER JOIN tracker_changeset_value_list AS CVL ON (CV2.id = CVL.changeset_value_id)
                  WHERE CHG2.id IS NULL
                    AND ART.tracker_id = ?
                    AND ART.id IN($in)";

        $parameters = [...$table_dates->parameters, $tracker_id, $tracker_id, ...($in->values())];

        return $this->getDB()->run($sql, ...$parameters);
    }

    private function generateTableDates(array $dates): ParametrizedSQLFragment
    {
        $table_dates_sql_parts = array_fill(0, count($dates), 'SELECT UNIX_TIMESTAMP(?) AS day');

        return new ParametrizedSQLFragment(
            implode(' UNION ALL ', $table_dates_sql_parts),
            $dates
        );
    }
}
