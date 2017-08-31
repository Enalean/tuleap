<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\CrossTracker;

use DataAccessObject;

class CrossTrackerArtifactReportDao extends DataAccessObject
{
    public function searchArtifactsFromTracker(array $tracker_ids, $limit, $offset)
    {
        $tracker_ids = $this->da->escapeIntImplode($tracker_ids);
        $limit       = $this->da->escapeInt($limit);
        $offset      = $this->da->escapeInt($offset);

        $sql = "SELECT
                  SQL_CALC_FOUND_ROWS
                  tracker_artifact.tracker_id,
                  tracker_artifact.id,
                  tracker_changeset_value_title.value AS title
                FROM tracker_artifact
                  INNER JOIN tracker ON (tracker_artifact.tracker_id = tracker.id)
                  INNER JOIN groups  ON (groups.group_id = tracker.group_id)
                  LEFT JOIN (
                      tracker_semantic_status
                      INNER JOIN tracker_changeset_value AS tracker_changeset_value_status
                        ON (tracker_changeset_value_status.field_id = tracker_semantic_status.field_id)
                      INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_list_status
                          ON (tracker_changeset_value_list_status.changeset_value_id = tracker_changeset_value_status.id)
                    ) ON (
                      tracker_semantic_status.tracker_id = tracker_artifact.tracker_id
                      AND tracker_changeset_value_status.changeset_id = tracker_artifact.last_changeset_id
                    )
                  LEFT JOIN (
                      tracker_changeset_value AS changeset_value_title
                      INNER JOIN tracker_semantic_title
                        ON (tracker_semantic_title.field_id = changeset_value_title.field_id)
                      INNER JOIN tracker_changeset_value_text AS tracker_changeset_value_title
                          ON (tracker_changeset_value_title.changeset_value_id = changeset_value_title.id)
                    ) ON (
                      tracker_semantic_title.tracker_id = tracker_artifact.tracker_id
                      AND changeset_value_title.changeset_id = tracker_artifact.last_changeset_id
                    )
                WHERE (
                        tracker_semantic_status.field_id IS NULL -- Use the status semantic only if it is defined
                        OR
                        tracker_changeset_value_list_status.bindvalue_id = tracker_semantic_status.open_value_id
                      )
                      AND groups.status = 'A'
                      AND tracker.deletion_date IS NULL
                      AND tracker_artifact.tracker_id IN ($tracker_ids)
                ORDER BY tracker_artifact.id DESC
                LIMIT $offset, $limit";

        return $this->retrieve($sql);
    }
}
