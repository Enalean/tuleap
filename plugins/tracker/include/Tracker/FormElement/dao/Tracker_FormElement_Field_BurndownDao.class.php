<?php
/**
 * Copyright (c) Enalean SAS 2014 - 2016. All rights reserved
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

/**
 *  Data Access Object for Tracker_FormElement_Field
 */
class Tracker_FormElement_Field_BurndownDao extends Tracker_FormElement_SpecificPropertiesDao
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
     * @param int $to_field_id the field id target
     *
     * @return boolean true if ok, false otherwise
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

    public function getArtifactsWithBurndown()
    {
        $sql = "SELECT
                  tracker_artifact.id,
                  SUM(tracker_changeset_value_date.value)      AS start_date,
                  SUM(tracker_changeset_value_int.value)       AS duration,
                  tracker_field_for_start_date.id              AS start_date_field_id,
                  tracker_field_for_duration.id                AS duration_field_id,
                  tracker_field_for_remaining_effort.id        AS remaining_effort_field_id,
                  burndown_field.id                            AS burndown_field_id,
                  DATE_ADD(
                    DATE_FORMAT(FROM_UNIXTIME(SUM(tracker_changeset_value_date.value)), '%Y-%m-%d 00:00:00'),
                    INTERVAL SUM(tracker_changeset_value_int.value) +1 DAY
                  ) AS end_date
            FROM tracker_field AS burndown_field
            INNER JOIN tracker
              ON tracker.id = burndown_field.tracker_id
            INNER JOIN tracker_field AS tracker_field_for_start_date
              ON tracker.id = tracker_field_for_start_date.tracker_id
              AND tracker_field_for_start_date.name = 'start_date'
            INNER JOIN tracker_field AS tracker_field_for_duration
              ON tracker.id = tracker_field_for_duration.tracker_id
              AND tracker_field_for_duration.name = 'duration'
            INNER JOIN tracker_field AS tracker_field_for_remaining_effort
              ON tracker.id = tracker_field_for_remaining_effort.tracker_id
              AND tracker_field_for_remaining_effort.name = 'remaining_effort'
            INNER JOIN tracker_field_date AS field_start_date
              ON field_start_date.field_id = tracker_field_for_start_date.id
            INNER JOIN tracker_field_int AS field_duration
              ON field_duration.field_id = tracker_field_for_duration.id
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
              GROUP BY tracker_artifact.id
              HAVING start_date IS NOT NULL
              AND duration IS NOT NULL
             ORDER BY tracker_artifact.id, start_date DESC";

        return $this->retrieve($sql);
    }

    public function getBurndownInformation($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT
                  tracker_artifact.id,
                  SUM(tracker_changeset_value_date.value)      AS start_date,
                  SUM(tracker_changeset_value_int.value)       AS duration,
                  tracker_field_for_start_date.id              AS start_date_field_id,
                  tracker_field_for_duration.id                AS duration_field_id,
                  tracker_field_for_remaining_effort.id        AS remaining_effort_field_id,
                  burndown_field.id                            AS burndown_field_id,
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
              AND tracker_field_for_start_date.name = 'start_date'
            INNER JOIN tracker_field AS tracker_field_for_duration
              ON tracker.id = tracker_field_for_duration.tracker_id
              AND tracker_field_for_duration.name = 'duration'
            INNER JOIN tracker_field AS tracker_field_for_remaining_effort
              ON tracker.id = tracker_field_for_remaining_effort.tracker_id
              AND tracker_field_for_remaining_effort.name = 'remaining_effort'
            INNER JOIN tracker_field_date AS field_start_date
              ON field_start_date.field_id = tracker_field_for_start_date.id
            INNER JOIN tracker_field_int AS field_duration
              ON field_duration.field_id = tracker_field_for_duration.id
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
              GROUP BY tracker_artifact.id
              HAVING start_date IS NOT NULL
              AND duration IS NOT NULL
              AND end_date >= CURRENT_DATE() - INTERVAL 1 DAY";

        return $this->retrieveFirstRow($sql);
    }
}
