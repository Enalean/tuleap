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
          DISTINCT linked_art.id                        AS id,
          tracker_artifact.id                           AS parent_id,
          linked_art.id                                 AS artifact_link_id,
          plugin_agiledashboard_semantic_done.value_id  AS done_id,
          tracker_field_list_bind_static_value.id       AS done_value,
          'computed'                                    AS type
        FROM tracker_artifact
          INNER JOIN tracker ON (tracker_artifact.tracker_id = tracker.id)
          INNER JOIN groups  ON (groups.group_id = tracker.group_id)
          INNER JOIN tracker_field AS parent_field ON (
            parent_field.tracker_id            = tracker_artifact.tracker_id
            AND (parent_field.formElement_type = 'art_link' OR parent_field.formElement_type = 'computed')
            AND parent_field.use_it            = 1
            )
          INNER JOIN tracker_changeset_value AS parent_value ON (
            parent_value.changeset_id = tracker_artifact.last_changeset_id
            AND parent_value.field_id = parent_field.id
            )
          LEFT JOIN (
            plugin_agiledashboard_semantic_done
            ) ON (tracker_artifact.tracker_id = plugin_agiledashboard_semantic_done.tracker_id)
          LEFT JOIN (tracker_changeset
            INNER JOIN tracker_changeset_value AS current_semantic_value
              ON tracker_changeset.id = current_semantic_value.changeset_id
            INNER JOIN tracker_changeset_value_list
              ON current_semantic_value.id = tracker_changeset_value_list.changeset_value_id
            INNER JOIN tracker_field_list_bind_static_value
              ON tracker_field_list_bind_static_value.field_id= current_semantic_value.field_id
                 AND tracker_changeset_value_list.bindvalue_id = tracker_field_list_bind_static_value.id
            ) ON tracker_artifact.last_changeset_id = tracker_changeset.id
                 AND plugin_agiledashboard_semantic_done.value_id = tracker_field_list_bind_static_value.id
          LEFT JOIN (
                tracker_changeset_value_artifactlink    AS  artifact_link_value
                INNER JOIN tracker_artifact                     AS linked_art
                ON (linked_art.id = artifact_link_value.artifact_id)
              )
              ON (
                artifact_link_value.changeset_value_id = parent_value.id
                AND parent_value.changeset_id          = tracker_artifact.last_changeset_id
                AND parent_value.field_id              = parent_field.id
                AND parent_field.tracker_id            = tracker_artifact.tracker_id
                AND artifact_link_value.nature         IS NULL
              )
        WHERE
          tracker_artifact.id IN ($artifact_ids)
          AND groups.status = 'A'
          AND tracker.deletion_date IS NULL";

        return $this->retrieve($sql);
    }
}
