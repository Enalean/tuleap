<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class BurnupDataDAO extends DataAccessObject
{
    public function searchArtifactsWithBurnup(): array
    {
        $sql = "SELECT
                  tracker_artifact.id,
                  start_date_value.value AS start_date,
                  null AS duration,
                  end_date_value.value AS end_date
            FROM tracker_field AS burnup_field
            INNER JOIN tracker
              ON tracker.id = burnup_field.tracker_id
                AND burnup_field.formElement_type = 'burnup'
                AND burnup_field.use_it = 1
            INNER JOIN tracker_semantic_timeframe AS timeframe
                ON (timeframe.tracker_id = tracker.id)
            INNER JOIN tracker_artifact
              ON tracker.id = tracker_artifact.tracker_id
            INNER JOIN tracker_changeset
              ON tracker_changeset.id = tracker_artifact.last_changeset_id
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
                  null AS end_date
            FROM tracker_field AS burnup_field
            INNER JOIN tracker
              ON tracker.id = burnup_field.tracker_id
                AND burnup_field.formElement_type = 'burnup'
                AND burnup_field.use_it = 1
            INNER JOIN tracker_semantic_timeframe AS timeframe
                ON (timeframe.tracker_id = tracker.id)
            INNER JOIN tracker_artifact
              ON tracker.id = tracker_artifact.tracker_id
            INNER JOIN tracker_changeset
              ON tracker_changeset.id = tracker_artifact.last_changeset_id
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

        return $this->getDB()->run($sql);
    }

    /**
     * @psalm-return array{id:int, start_date:int, duration:int}|null
     */
    public function getBurnupInformationBasedOnDuration(int $artifact_id, int $start_date_field_id, int $duration_field_id): ?array
    {
        $sql = "SELECT
                  tracker_artifact.id,
                  SUM(tracker_changeset_value_date.value) AS start_date,
                  SUM(tracker_changeset_value_int.value)  AS duration
            FROM tracker_field AS burnup_field
            INNER JOIN tracker
              ON tracker.id = burnup_field.tracker_id
            INNER JOIN tracker_field AS tracker_field_for_start_date
              ON tracker.id = tracker_field_for_start_date.tracker_id
              AND tracker_field_for_start_date.id = ?
            INNER JOIN tracker_field AS tracker_field_for_duration
              ON tracker.id = tracker_field_for_duration.tracker_id
              AND tracker_field_for_duration.id = ?
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
              burnup_field.formElement_type = 'burnup'
              AND burnup_field.use_it = 1
              AND tracker_artifact.id = ?
              GROUP BY tracker_artifact.id, burnup_field.id
              HAVING start_date IS NOT NULL
              AND duration IS NOT NULL";

        return $this->getDB()->row($sql, $start_date_field_id, $duration_field_id, $artifact_id);
    }

    /**
     * @psalm-return array{id:int, start_date:int, end_date:int}|null
     */
    public function getBurnupInformationBasedOnEndDate(int $artifact_id, int $start_date_field_id, int $end_date_field_id): ?array
    {
        $sql = "SELECT
                  tracker_artifact.id,
                  SUM(start_date_value.value) AS start_date,
                  SUM(end_date_value.value) AS end_date
            FROM tracker_field AS burnup_field
            INNER JOIN tracker
              ON tracker.id = burnup_field.tracker_id
            INNER JOIN tracker_field AS tracker_field_for_start_date
              ON tracker.id = tracker_field_for_start_date.tracker_id
              AND tracker_field_for_start_date.id = ?
            INNER JOIN tracker_field AS tracker_field_for_end_date
              ON tracker.id = tracker_field_for_end_date.tracker_id
              AND tracker_field_for_end_date.id = ?
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
              burnup_field.formElement_type = 'burnup'
              AND burnup_field.use_it = 1
              AND tracker_artifact.id = ?
              GROUP BY tracker_artifact.id, burnup_field.id
              HAVING start_date IS NOT NULL
              AND end_date IS NOT NULL";

        return $this->getDB()->row($sql, $start_date_field_id, $end_date_field_id, $artifact_id);
    }

    /**
     * @return list<array{id: int}>
     */
    public function searchLinkedArtifactsAtGivenTimestamp(int $artifact_id, int $timestamp, array $backlog_trackers_ids): array
    {
        $in_statement = EasyStatement::open()->in(
            'linked_art.tracker_id IN ( ?* )',
            $backlog_trackers_ids
        );

        $sql = "SELECT linked_art.id AS id
                FROM tracker_artifact AS parent_art
                    INNER JOIN tracker ON parent_art.tracker_id = tracker.id
                    INNER JOIN tracker_changeset AS changeset1 ON changeset1.artifact_id = parent_art.id
                    LEFT JOIN  tracker_changeset AS changeset2
                        ON (
                            changeset2.artifact_id = parent_art.id
                            AND changeset1.id < changeset2.id
                            AND changeset2.submitted_on <= ?
                        )
                    INNER JOIN tracker_field AS f
                        ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value AS cv
                        ON (cv.changeset_id = changeset1.id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink
                        ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact AS linked_art
                        ON (linked_art.id = artlink.artifact_id)
                WHERE parent_art.id = ?
                    AND tracker.deletion_date IS NULL
                    AND changeset2.id IS NULL
                    AND changeset1.submitted_on <= ?
                    AND $in_statement";


        return $this->getDB()->run($sql, $timestamp, $artifact_id, $timestamp, ...$in_statement->values());
    }
}
