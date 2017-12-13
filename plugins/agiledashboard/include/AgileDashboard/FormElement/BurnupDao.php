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
                  initial_effort_int_value.value                AS int_value
                FROM tracker_artifact AS parent_art
                  INNER JOIN tracker ON (parent_art.tracker_id = tracker.id)
                  INNER JOIN groups  ON (groups.group_id = tracker.group_id)
                  INNER JOIN tracker_field AS parent_field
                    ON (
                      parent_field.tracker_id            = parent_art.tracker_id
                      AND (parent_field.formElement_type = 'art_link' OR parent_field.formElement_type = 'computed')
                      AND parent_field.use_it            = 1
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
                  LEFT JOIN plugin_agiledashboard_semantic_done
                    ON (linked_art.tracker_id = plugin_agiledashboard_semantic_done.tracker_id)
                  LEFT JOIN (tracker_changeset           AS changeset_done
                      INNER JOIN tracker_changeset_value AS current_semantic_changeset_value
                          ON changeset_done.id = current_semantic_changeset_value.changeset_id
                      INNER JOIN tracker_changeset_value_list AS current_semantic_changeset_value_list
                          ON current_semantic_changeset_value.id = current_semantic_changeset_value_list.changeset_value_id
                      INNER JOIN tracker_field_list_bind_static_value AS current_semantic_done_value
                          ON current_semantic_done_value.field_id= current_semantic_changeset_value.field_id
                          AND current_semantic_changeset_value_list.bindvalue_id = current_semantic_done_value.id
                  ) ON linked_art.last_changeset_id = changeset_done.id
                    AND plugin_agiledashboard_semantic_done.value_id = current_semantic_done_value.id
                  INNER JOIN (
                    plugin_agiledashboard_semantic_initial_effort AS initial_effort
                  ) ON (linked_art.tracker_id = initial_effort.tracker_id)
                  INNER JOIN tracker_changeset_value AS initial_value
                    ON initial_value.changeset_id = linked_art.last_changeset_id
                    AND initial_value.field_id = initial_effort.field_id
                  INNER JOIN tracker_field AS children_field
                    ON  initial_effort.field_id = children_field.id
                  INNER JOIN tracker_changeset_value_int AS initial_effort_int_value
                    ON initial_effort_int_value.changeset_value_id = initial_value.id
                WHERE
                  parent_art.id IN ($artifact_ids)
                  AND groups.status = 'A'
                  AND tracker.deletion_date IS NULL
                  AND changeset_done.id IS NOT NULL
                ";

        return $this->retrieve($sql);
    }
}
