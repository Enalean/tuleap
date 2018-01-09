<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use DataAccessObject;

class BurnupDao extends DataAccessObject
{
    public function searchArtifactsWithBurnup()
    {
        $type = $this->da->quoteSmart(Burnup::TYPE);

        $sql = "SELECT
                  tracker_artifact.id,
                  SUM(tracker_changeset_value_date.value) AS start_date,
                  SUM(tracker_changeset_value_int.value)  AS duration
            FROM tracker_field AS burnup_field
            INNER JOIN tracker
              ON tracker.id = burnup_field.tracker_id
            INNER JOIN tracker_field AS tracker_field_for_start_date
              ON tracker.id = tracker_field_for_start_date.tracker_id
              AND tracker_field_for_start_date.name = 'start_date'
            INNER JOIN tracker_field AS tracker_field_for_duration
              ON tracker.id = tracker_field_for_duration.tracker_id
              AND tracker_field_for_duration.name = 'duration'
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
              burnup_field.formElement_type = $type
              AND burnup_field.use_it = 1
              GROUP BY tracker_artifact.id, burnup_field.id
              HAVING start_date IS NOT NULL
              AND duration IS NOT NULL
             ORDER BY tracker_artifact.id, start_date DESC";

        return $this->retrieve($sql);
    }

    public function getBurnupManualValueAtGivenTimestamp($artifact_id, $timestamp, $only_done_artifacts)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $timestamp   = $this->da->escapeInt($timestamp);

        $filter_sql = '';
        if ($only_done_artifacts) {
            $filter_sql = "AND parent_semantic_done.value_id IS NOT NULL";
        }

        $sql = "SELECT
                  tracker_changeset.submitted_on           AS last_changeset_date,
                  initial_effort_manual_value.*
                FROM tracker_artifact
                  INNER JOIN tracker_changeset
                    ON (
                    tracker_changeset.artifact_id = tracker_artifact.id
                    AND tracker_changeset.submitted_on <= $timestamp
                    )
                  INNER JOIN tracker_changeset_value
                    ON tracker_changeset_value.changeset_id = tracker_changeset.id
                  LEFT JOIN plugin_agiledashboard_semantic_done AS parent_semantic_done
                    ON (tracker_artifact.tracker_id = parent_semantic_done.tracker_id)
                  LEFT JOIN (tracker_changeset         AS parent_changeset_done
                    INNER JOIN tracker_changeset_value AS parent_semantic_changeset_value
                      ON parent_changeset_done.id = parent_semantic_changeset_value.changeset_id
                    INNER JOIN tracker_changeset_value_list AS parent_semantic_changeset_value_list
                      ON parent_semantic_changeset_value.id = parent_semantic_changeset_value_list.changeset_value_id
                    INNER JOIN tracker_field_list_bind_static_value AS parent_semantic_done_value
                      ON parent_semantic_done_value.field_id = parent_semantic_changeset_value.field_id
                      AND parent_semantic_changeset_value_list.bindvalue_id = parent_semantic_done_value.id
                  ) ON tracker_changeset.id = parent_changeset_done.id
                    AND parent_semantic_done.value_id = parent_semantic_done_value.id
                  LEFT JOIN (
                    plugin_agiledashboard_semantic_initial_effort AS parent_initial_effort
                  ) ON (
                    tracker_artifact.tracker_id = parent_initial_effort.tracker_id
                    $filter_sql
                  )
                  LEFT JOIN tracker_changeset_value AS parent_initial_value
                    ON parent_initial_value.changeset_id = tracker_changeset.id
                    AND parent_initial_value.field_id = parent_initial_effort.field_id
                  LEFT JOIN tracker_changeset_value_computedfield_manual_value AS initial_effort_manual_value
                    ON initial_effort_manual_value.changeset_value_id = parent_initial_value.id
                WHERE tracker_artifact.id = $artifact_id
                ORDER BY last_changeset_date DESC
                LIMIT 1";

        return $this->retrieveFirstRow($sql);
    }

    public function getBurnupComputedValueAtGivenTimestamp(array $artifacts_id, $timestamp, $only_done_artifacts)
    {
        $artifacts_id = $this->da->escapeIntImplode($artifacts_id);
        $timestamp    = $this->da->escapeInt($timestamp);

        $filter_sql = "";
        if ($only_done_artifacts) {
            $filter_sql = "AND children_semantic_done_value.id IS NOT NULL";
        }

        $sql = "SELECT
                  DISTINCT
                  linked_art.id                                 AS id,
                  linked_art.id                                 AS artifact_link_id,
                  children_field.formElement_type               AS type,
                  initial_effort_int_value.value                AS int_value,
                  initial_effort_float_value.value              AS float_value,
                  selectbox_value.label                         AS sb_value,
                  selectbox_value.label                         AS rb_value
                FROM tracker_artifact parent_art
                  INNER JOIN tracker ON (parent_art.tracker_id = tracker.id)
                  INNER JOIN groups  ON (groups.group_id = tracker.group_id)
                  INNER JOIN tracker_changeset                    AS cs_parent_art1
                    ON (
                      cs_parent_art1.artifact_id = parent_art.id
                      AND cs_parent_art1.submitted_on <= $timestamp
                    )
                  LEFT JOIN  tracker_changeset                    AS cs_parent_art2
                    ON (
                      cs_parent_art2.artifact_id = parent_art.id
                      AND cs_parent_art1.id < cs_parent_art2.id
                      AND cs_parent_art2.submitted_on <= $timestamp
                    )
                  LEFT JOIN tracker_field                        AS field_artifact_link
                    ON (
                      field_artifact_link.tracker_id = parent_art.tracker_id
                      AND field_artifact_link.formElement_type = 'art_link'
                      AND field_artifact_link.use_it = 1
                    )
                  LEFT JOIN tracker_changeset_value              AS changeset_artifact_link
                    ON (
                      changeset_artifact_link.changeset_id = cs_parent_art1.id
                      AND changeset_artifact_link.field_id = field_artifact_link.id
                    )
                  LEFT JOIN tracker_changeset_value_artifactlink AS artlink
                    ON artlink.changeset_value_id = changeset_artifact_link.id
                  LEFT JOIN tracker_artifact                     AS linked_art
                    ON linked_art.id = artlink.artifact_id
                  LEFT JOIN tracker_changeset                    AS cs_linked_art1
                    ON (
                      cs_linked_art1.artifact_id = linked_art.id
                      AND cs_linked_art1.submitted_on <= $timestamp
                    )
                  LEFT JOIN  tracker_changeset                    AS cs_linked_art2
                    ON (
                      cs_linked_art2.artifact_id = linked_art.id
                      AND cs_linked_art1.id < cs_linked_art2.id
                      AND cs_linked_art2.submitted_on <= $timestamp
                    )
                  LEFT JOIN plugin_agiledashboard_semantic_done AS children_semantic_done
                    ON (linked_art.tracker_id = children_semantic_done.tracker_id)
                  LEFT JOIN (tracker_changeset         AS children_changeset_done
                    INNER JOIN tracker_changeset_value AS children_semantic_changeset_value
                      ON children_changeset_done.id = children_semantic_changeset_value.changeset_id
                    INNER JOIN tracker_changeset_value_list AS children_semantic_changeset_value_list
                      ON children_semantic_changeset_value.id = children_semantic_changeset_value_list.changeset_value_id
                    INNER JOIN tracker_field_list_bind_static_value AS children_semantic_done_value
                      ON children_semantic_done_value.field_id = children_semantic_changeset_value.field_id
                         AND children_semantic_changeset_value_list.bindvalue_id = children_semantic_done_value.id
                    ) ON linked_art.last_changeset_id = children_changeset_done.id
                    AND children_semantic_done.value_id = children_semantic_done_value.id
                  LEFT JOIN plugin_agiledashboard_semantic_initial_effort AS initial_effort
                   ON linked_art.tracker_id = initial_effort.tracker_id
                  LEFT JOIN tracker_changeset_value AS initial_value
                    ON initial_value.changeset_id = linked_art.last_changeset_id
                       AND initial_value.field_id = initial_effort.field_id
                  LEFT JOIN tracker_field AS children_field
                    ON  initial_effort.field_id = children_field.id
                  LEFT JOIN tracker_changeset_value_int AS initial_effort_int_value
                    ON initial_effort_int_value.changeset_value_id = initial_value.id
                  LEFT JOIN tracker_changeset_value_float AS initial_effort_float_value
                    ON initial_effort_float_value.changeset_value_id = initial_value.id
                  LEFT JOIN tracker_changeset_value_list AS list_value
                    ON (
                      list_value.changeset_value_id = initial_value.id
                      AND initial_value.field_id    = children_field.id
                    )
                  LEFT JOIN tracker_field_list_bind_static_value AS selectbox_value
                    ON (
                      list_value.bindvalue_id = selectbox_value.id
                      AND selectbox_value.label REGEXP '^[[:digit:]]+([.][[:digit:]]+)?$'
                    )
                WHERE
                  parent_art.id IN ($artifacts_id)
                  AND groups.status = 'A'
                  AND tracker.deletion_date IS NULL
                  $filter_sql
                ";

        return $this->retrieve($sql);
    }

    public function getBurnupInformation($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $type        = $this->da->quoteSmart(Burnup::TYPE);

        $sql = "SELECT
                  tracker_artifact.id,
                  SUM(tracker_changeset_value_date.value) AS start_date,
                  SUM(tracker_changeset_value_int.value)  AS duration
            FROM tracker_field AS burnup_field
            INNER JOIN tracker
              ON tracker.id = burnup_field.tracker_id
            INNER JOIN tracker_field AS tracker_field_for_start_date
              ON tracker.id = tracker_field_for_start_date.tracker_id
              AND tracker_field_for_start_date.name = 'start_date'
            INNER JOIN tracker_field AS tracker_field_for_duration
              ON tracker.id = tracker_field_for_duration.tracker_id
              AND tracker_field_for_duration.name = 'duration'
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
              burnup_field.formElement_type = $type
              AND burnup_field.use_it = 1
              AND tracker_artifact.id = $artifact_id
              GROUP BY tracker_artifact.id, burnup_field.id
              HAVING start_date IS NOT NULL
              AND duration IS NOT NULL";

        return $this->retrieveFirstRow($sql);
    }
}
