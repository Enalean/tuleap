<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
    public function getBurnupComputedValue(array $artifact_ids)
    {
        $artifact_ids = $this->da->escapeIntImplode($artifact_ids);

        $sql = "SELECT
                  linked_art.id                                 AS id,
                  parent_art.id                                 AS parent_id,
                  linked_art.id                                 AS artifact_link_id,
                  children_field.formElement_type               AS type,
                  initial_effort_int_value.value                AS int_value,
                  initial_effort_float_value.value              AS float_value,
                  initial_effort_manual_value.value             AS value,
                  selectbox_value.label                         AS sb_value,
                  selectbox_value.label                         AS rb_value
                FROM tracker_artifact AS parent_art
                  INNER JOIN tracker ON (parent_art.tracker_id = tracker.id)
                  INNER JOIN groups  ON (groups.group_id = tracker.group_id)
                  LEFT JOIN plugin_agiledashboard_semantic_done AS parent_semantic_done
                    ON (parent_art.tracker_id = parent_semantic_done.tracker_id)
                  LEFT JOIN (tracker_changeset         AS parent_changeset_done
                    INNER JOIN tracker_changeset_value AS parent_semantic_changeset_value
                      ON parent_changeset_done.id = parent_semantic_changeset_value.changeset_id
                    INNER JOIN tracker_changeset_value_list AS parent_semantic_changeset_value_list
                      ON parent_semantic_changeset_value.id = parent_semantic_changeset_value_list.changeset_value_id
                    INNER JOIN tracker_field_list_bind_static_value AS parent_semantic_done_value
                      ON parent_semantic_done_value.field_id = parent_semantic_changeset_value.field_id
                         AND parent_semantic_changeset_value_list.bindvalue_id = parent_semantic_done_value.id
                    ) ON parent_art.last_changeset_id = parent_changeset_done.id
                         AND parent_semantic_done.value_id = parent_semantic_done_value.id
                  LEFT JOIN (
                    plugin_agiledashboard_semantic_initial_effort AS parent_initial_effort
                    ) ON (
                    parent_art.tracker_id = parent_initial_effort.tracker_id
                    AND parent_semantic_done.value_id IS NOT NULL
                    )
                  LEFT JOIN tracker_changeset_value AS parent_initial_value
                    ON parent_initial_value.changeset_id = parent_art.last_changeset_id
                       AND parent_initial_value.field_id = parent_initial_effort.field_id
                  LEFT JOIN tracker_changeset_value_computedfield_manual_value AS initial_effort_manual_value
                    ON initial_effort_manual_value.changeset_value_id = parent_initial_value.id
                       AND parent_changeset_done.id IS NOT NULL
                  INNER JOIN tracker_field AS parent_field
                    ON (
                      parent_field.tracker_id            = parent_art.tracker_id
                      AND (parent_field.formElement_type = 'art_link' OR parent_field.formElement_type = 'computed')
                      AND parent_field.use_it            = 1
                      AND (
                        (
                          parent_semantic_done.value_id IS NOT NULL
                          AND parent_semantic_changeset_value_list.bindvalue_id = parent_semantic_done.value_id
                        )
                        OR
                        (parent_semantic_done.value_id IS NULL)
                      )
                    )
                  INNER JOIN tracker_changeset_value AS parent_value
                    ON (
                    parent_value.changeset_id = parent_art.last_changeset_id
                    AND parent_value.field_id = parent_field.id
                    )
                  LEFT JOIN (
                      tracker_changeset_value_artifactlink    AS  parent_artifact_link_value
                      INNER JOIN tracker_artifact             AS linked_art
                        ON (linked_art.id = parent_artifact_link_value.artifact_id)
                    )
                    ON (
                    parent_artifact_link_value.changeset_value_id = parent_value.id
                    AND parent_value.changeset_id          = parent_art.last_changeset_id
                    AND parent_value.field_id              = parent_field.id
                    AND parent_field.tracker_id            = parent_art.tracker_id
                    AND parent_artifact_link_value.nature  IS NULL
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
                  LEFT JOIN (
                    plugin_agiledashboard_semantic_initial_effort AS initial_effort
                    ) ON (linked_art.tracker_id = initial_effort.tracker_id)
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
                  parent_art.id IN ($artifact_ids)
                  AND groups.status = 'A'
                  AND tracker.deletion_date IS NULL
                  AND (
                    (children_changeset_done.id IS NOT NULL)
                     OR ( parent_changeset_done.id IS NOT NULL AND linked_art.id IS NULL)
                  )";

        return $this->retrieve($sql);
    }
}
