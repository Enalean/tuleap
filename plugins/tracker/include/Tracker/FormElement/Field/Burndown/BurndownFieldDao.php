<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\FormElement\Field\Burndown;

use DataAccessResult;
use Tuleap\Tracker\FormElement\SpecificPropertiesDao;

class BurndownFieldDao extends SpecificPropertiesDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_burndown';
    }

    public function save($field_id, $row)
    {
        $field_id  = $this->da->escapeInt($field_id);
        $use_cache = (int) (isset($row['use_cache']) && $row['use_cache']);

        $sql = "REPLACE INTO tracker_field_burndown (field_id, use_cache)
                VALUES ($field_id, $use_cache)";

        return $this->update($sql);
    }

    /**
     * Duplicate specific properties of field
     *
     * @param int $from_field_id the field id source
     * @param int $to_field_id   the field id target
     *
     * @return bool true if ok, false otherwise
     */
    public function duplicate($from_field_id, $to_field_id)
    {
        $from_field_id = $this->da->escapeInt($from_field_id);
        $to_field_id   = $this->da->escapeInt($to_field_id);

        $sql = "REPLACE INTO tracker_field_burndown (field_id, use_cache)
                SELECT $to_field_id, use_cache
                FROM $this->table_name
                WHERE field_id = $from_field_id";

        return $this->update($sql);
    }

    /**
     * SUM(): Magic trick
     * The request returns values for 2 fields, start_date and duration
     * SUM of null + value give us the value for field in one single line
     *
     * @return DataAccessResult|false
     */
    public function getArtifactsWithBurndown()
    {
        $sql = "SELECT
                  tracker_artifact.id,
                  start_date_value.value AS start_date,
                  null AS duration,
                  end_date_value.value AS end_date,
                  tracker_field_for_remaining_effort.id AS remaining_effort_field_id
            FROM tracker_field AS burnup_field
            INNER JOIN tracker
              ON tracker.id = burnup_field.tracker_id
                AND burnup_field.formElement_type = 'burndown'
                AND burnup_field.use_it = 1
            INNER JOIN tracker_semantic_timeframe AS timeframe
                ON (timeframe.tracker_id = tracker.id)
            INNER JOIN tracker_artifact
              ON tracker.id = tracker_artifact.tracker_id
            INNER JOIN tracker_changeset
              ON tracker_changeset.id = tracker_artifact.last_changeset_id
            INNER JOIN tracker_field AS tracker_field_for_remaining_effort
              ON tracker.id = tracker_field_for_remaining_effort.tracker_id
              AND tracker_field_for_remaining_effort.name = 'remaining_effort'
            INNER JOIN tracker_changeset_value AS start_date_changeset_value
              ON start_date_changeset_value.changeset_id = tracker_changeset.id
                AND start_date_changeset_value.field_id = timeframe.start_date_field_id
            INNER JOIN tracker_changeset_value_date AS start_date_value
              ON start_date_value.changeset_value_id = start_date_changeset_value.id
                AND start_date_value.value IS NOT NULL
            INNER JOIN tracker_changeset_value AS end_date_changeset_value
              ON end_date_changeset_value.changeset_id = tracker_changeset.id
                AND end_date_changeset_value.field_id = timeframe.end_date_field_id
            INNER JOIN tracker_changeset_value_date AS end_date_value
              ON end_date_value.changeset_value_id = end_date_changeset_value.id
                AND end_date_value.value IS NOT NULL

            UNION

             SELECT
                  tracker_artifact.id,
                  start_date_value.value AS start_date,
                  duration_value.value AS duration,
                  null AS end_date,
                  tracker_field_for_remaining_effort.id AS remaining_effort_field_id
            FROM tracker_field AS burnup_field
            INNER JOIN tracker
              ON tracker.id = burnup_field.tracker_id
                AND burnup_field.formElement_type = 'burndown'
                AND burnup_field.use_it = 1
            INNER JOIN tracker_semantic_timeframe AS timeframe
                ON (timeframe.tracker_id = tracker.id)
            INNER JOIN tracker_artifact
              ON tracker.id = tracker_artifact.tracker_id
            INNER JOIN tracker_changeset
              ON tracker_changeset.id = tracker_artifact.last_changeset_id
            INNER JOIN tracker_field AS tracker_field_for_remaining_effort
              ON tracker.id = tracker_field_for_remaining_effort.tracker_id
              AND tracker_field_for_remaining_effort.name = 'remaining_effort'
            INNER JOIN tracker_changeset_value AS start_date_changeset_value
              ON start_date_changeset_value.changeset_id = tracker_changeset.id
                AND start_date_changeset_value.field_id = timeframe.start_date_field_id
            INNER JOIN tracker_changeset_value_date AS start_date_value
              ON start_date_value.changeset_value_id = start_date_changeset_value.id
                AND start_date_value.value IS NOT NULL
            INNER JOIN tracker_changeset_value AS duration_changeset_value
              ON duration_changeset_value.changeset_id = tracker_changeset.id
                AND duration_changeset_value.field_id = timeframe.duration_field_id
            INNER JOIN tracker_changeset_value_int AS duration_value
              ON duration_value.changeset_value_id = duration_changeset_value.id
                AND duration_value.value IS NOT NULL";

        return $this->retrieve($sql);
    }

    /**
     * SUM(): Magic trick
     * The request returns values for 2 fields, start_date and duration
     * SUM of null + value give us the value for field in one single line
     *
     * @return array|false
     */
    public function getBurndownInformationBasedOnDuration(
        int $artifact_id,
        int $start_date_field_id,
        int $duration_field_id
    ) {
        $artifact_id         = $this->da->escapeInt($artifact_id);
        $start_date_field_id = $this->da->escapeInt($start_date_field_id);
        $duration_field_id   = $this->da->escapeInt($duration_field_id);

        $sql = "SELECT
                  tracker_artifact.id,
                  SUM(tracker_changeset_value_date.value)      AS start_date,
                  SUM(tracker_changeset_value_int.value)       AS duration,
                  tracker_field_for_start_date.id              AS start_date_field_id,
                  tracker_field_for_duration.id                AS duration_field_id,
                  tracker_field_for_remaining_effort.id        AS remaining_effort_field_id,
                  DATE_ADD(
                    DATE_FORMAT(FROM_UNIXTIME(SUM(tracker_changeset_value_date.value)), '%Y-%m-%d 00:00:00'),
                    INTERVAL SUM(tracker_changeset_value_int.value) +1 DAY
                  ) AS end_date,
                 UNIX_TIMESTAMP(DATE_ADD(
                    (FROM_UNIXTIME(SUM(tracker_changeset_value_date.value))),
                    INTERVAL SUM(tracker_changeset_value_int.value) +1 DAY
                  )) AS timestamp_end_date
            FROM tracker_field AS burndown_field
            INNER JOIN tracker
              ON tracker.id = burndown_field.tracker_id
            INNER JOIN tracker_field AS tracker_field_for_start_date
              ON tracker.id = tracker_field_for_start_date.tracker_id
              AND tracker_field_for_start_date.id = $start_date_field_id
            INNER JOIN tracker_field AS tracker_field_for_duration
              ON tracker.id = tracker_field_for_duration.tracker_id
              AND tracker_field_for_duration.id = $duration_field_id
            INNER JOIN tracker_field AS tracker_field_for_remaining_effort
              ON tracker.id = tracker_field_for_remaining_effort.tracker_id
              AND tracker_field_for_remaining_effort.name = 'remaining_effort'
            INNER JOIN tracker_artifact
              ON tracker.id = tracker_artifact.tracker_id
            INNER JOIN tracker_changeset
              ON tracker_changeset.id = tracker_artifact.last_changeset_id
            INNER JOIN tracker_changeset_value
              ON tracker_changeset_value.changeset_id = tracker_changeset.id
            LEFT JOIN tracker_changeset_value_date
              ON tracker_changeset_value_date.changeset_value_id = tracker_changeset_value.id
              AND tracker_field_for_start_date.id = tracker_changeset_value.field_id
            LEFT JOIN tracker_changeset_value_int
              ON tracker_changeset_value_int.changeset_value_id = tracker_changeset_value.id
              AND tracker_field_for_duration.id = tracker_changeset_value.field_id
            WHERE
              burndown_field.formElement_type = 'burndown'
              AND tracker_artifact.id = $artifact_id
              AND burndown_field.use_it = 1
            GROUP BY tracker_artifact.id, burndown_field.id
            HAVING start_date IS NOT NULL
            AND duration IS NOT NULL";

        return $this->retrieveFirstRow($sql);
    }

    /**
     * SUM(): Magic trick
     * The request returns values for 2 fields, start_date and duration
     * SUM of null + value give us the value for field in one single line
     *
     * @return array|false
     */
    public function getBurndownInformationBasedOnEndDate(
        int $artifact_id,
        int $start_date_field_id,
        int $end_date_field_id
    ) {
        $artifact_id         = $this->da->escapeInt($artifact_id);
        $start_date_field_id = $this->da->escapeInt($start_date_field_id);
        $end_date_field_id   = $this->da->escapeInt($end_date_field_id);

        $sql = "SELECT
                  tracker_artifact.id,
                  SUM(start_date_value.value) AS start_date,
                  SUM(end_date_value.value) AS end_date,
                  tracker_field_for_remaining_effort.id AS remaining_effort_field_id
            FROM tracker_field AS burndown_field
            INNER JOIN tracker
              ON tracker.id = burndown_field.tracker_id
            INNER JOIN tracker_field AS tracker_field_for_start_date
              ON tracker.id = tracker_field_for_start_date.tracker_id
              AND tracker_field_for_start_date.id = $start_date_field_id
            INNER JOIN tracker_field AS tracker_field_for_end_date
              ON tracker.id = tracker_field_for_end_date.tracker_id
              AND tracker_field_for_end_date.id = $end_date_field_id
            INNER JOIN tracker_field AS tracker_field_for_remaining_effort
              ON tracker.id = tracker_field_for_remaining_effort.tracker_id
              AND tracker_field_for_remaining_effort.name = 'remaining_effort'
            INNER JOIN tracker_artifact
              ON tracker.id = tracker_artifact.tracker_id
            INNER JOIN tracker_changeset
              ON tracker_changeset.id = tracker_artifact.last_changeset_id
            INNER JOIN tracker_changeset_value
              ON tracker_changeset_value.changeset_id = tracker_changeset.id
            LEFT JOIN tracker_changeset_value_date AS start_date_value
              ON start_date_value.changeset_value_id = tracker_changeset_value.id
              AND tracker_field_for_start_date.id = tracker_changeset_value.field_id
            LEFT JOIN tracker_changeset_value_date AS end_date_value
              ON end_date_value.changeset_value_id = tracker_changeset_value.id
              AND tracker_field_for_end_date.id = tracker_changeset_value.field_id
            WHERE
              burndown_field.formElement_type = 'burndown'
              AND tracker_artifact.id = $artifact_id
              AND burndown_field.use_it = 1
            GROUP BY tracker_artifact.id, burndown_field.id
            HAVING start_date IS NOT NULL
            AND end_date IS NOT NULL";

        return $this->retrieveFirstRow($sql);
    }
}
