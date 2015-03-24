<?php
/**
 * Copyright (c) Enalean, 2014-2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class AgileDashboard_KanbanItemDao extends DataAccessObject {

    public function getAllKanbanItemIds($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT id
                FROM tracker_artifact
                WHERE tracker_id = $tracker_id
                    AND last_changeset_id IS NOT NULL";

        return $this->retrieve($sql);
    }

    /**
     * Backlog items for a kanban are artifacts that have no value for the semantic status field
     */
    public function searchPaginatedBacklogItemsByTrackerId($tracker_id, $limit, $offset) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN (
                            SELECT distinct(field_id) FROM tracker_semantic_status WHERE tracker_id = $tracker_id
                        ) AS SS ON (CV2.field_id = SS.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL ON (CV2.id = CVL.changeset_value_id)
                    ) ON (A.last_changeset_id = CV2.changeset_id)
                    INNER JOIN tracker_artifact_priority AS P ON (P.curr_id = A.id)
                WHERE CVL.bindvalue_id IS NULL
                   OR CVL.bindvalue_id = 100
                ORDER BY P.rank
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function getKanbanBacklogItemIds($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT A.id
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN (
                            SELECT distinct(field_id) FROM tracker_semantic_status WHERE tracker_id = $tracker_id
                        ) AS SS ON (CV2.field_id = SS.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL ON (CV2.id = CVL.changeset_value_id)
                    ) ON (A.last_changeset_id = CV2.changeset_id)
                    INNER JOIN tracker_artifact_priority AS P ON (P.curr_id = A.id)
                WHERE CVL.bindvalue_id IS NULL
                   OR CVL.bindvalue_id = 100
                ORDER BY P.rank";

        return $this->retrieve($sql);
    }

    /**
     * Archived items for a kanban are artifacts that have "closed" value for the semantic status field
     */
    public function searchPaginatedArchivedItemsByTrackerId($tracker_id, $limit, $offset) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN (
                            SELECT distinct(field_id) FROM tracker_semantic_status WHERE tracker_id = $tracker_id
                        ) AS SS ON (CV2.field_id = SS.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL ON (CV2.id = CVL.changeset_value_id)
                    ) ON (A.last_changeset_id = CV2.changeset_id)
                    LEFT JOIN tracker_semantic_status SS2 ON (SS2.field_id = CV2.field_id AND SS2.open_value_id = CVL.bindvalue_id)
                    INNER JOIN tracker_artifact_priority AS P ON (P.curr_id = A.id)
                WHERE SS2.open_value_id IS NULL
                  AND CVL.bindvalue_id IS NOT NULL
                  AND CVL.bindvalue_id <> 100
                ORDER BY P.rank
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function getKanbanArchiveItemIds($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT A.id
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN (
                            SELECT distinct(field_id) FROM tracker_semantic_status WHERE tracker_id = $tracker_id
                        ) AS SS ON (CV2.field_id = SS.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL ON (CV2.id = CVL.changeset_value_id)
                    ) ON (A.last_changeset_id = CV2.changeset_id)
                    LEFT JOIN tracker_semantic_status SS2 ON (SS2.field_id = CV2.field_id AND SS2.open_value_id = CVL.bindvalue_id)
                    INNER JOIN tracker_artifact_priority AS P ON (P.curr_id = A.id)
                WHERE SS2.open_value_id IS NULL
                  AND CVL.bindvalue_id IS NOT NULL
                  AND CVL.bindvalue_id <> 100
                ORDER BY P.rank";

        return $this->retrieve($sql);
    }

    public function searchPaginatedItemsInColumn($tracker_id, $column_id, $limit, $offset) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $column_id  = $this->da->escapeInt($column_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN (
                            SELECT distinct(field_id) FROM tracker_semantic_status WHERE tracker_id = $tracker_id
                        ) AS SS ON (CV2.field_id = SS.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL ON (CV2.id = CVL.changeset_value_id)
                    ) ON (A.last_changeset_id = CV2.changeset_id)
                    INNER JOIN tracker_artifact_priority AS P ON (P.curr_id = A.id)
                WHERE CVL.bindvalue_id = $column_id
                ORDER BY P.rank
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function getItemsInColumn($tracker_id, $column_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $column_id  = $this->da->escapeInt($column_id);

        $sql = "SELECT A.id
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN (
                            SELECT distinct(field_id) FROM tracker_semantic_status WHERE tracker_id = $tracker_id
                        ) AS SS ON (CV2.field_id = SS.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL ON (CV2.id = CVL.changeset_value_id)
                    ) ON (A.last_changeset_id = CV2.changeset_id)
                    INNER JOIN tracker_artifact_priority AS P ON (P.curr_id = A.id)
                WHERE CVL.bindvalue_id = $column_id
                ORDER BY P.rank";

        return $this->retrieve($sql);
    }

    public function getOpenItemIds($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT A.id
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN (
                            SELECT distinct(field_id), open_value_id FROM tracker_semantic_status WHERE tracker_id = $tracker_id
                        ) AS SS ON (CV2.field_id = SS.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL ON (CV2.id = CVL.changeset_value_id)
                    ) ON (A.last_changeset_id = CV2.changeset_id)
                    INNER JOIN tracker_artifact_priority AS P ON (P.curr_id = A.id)
                WHERE CVL.bindvalue_id IN (open_value_id)
                ORDER BY P.rank";

        return $this->retrieve($sql);
    }

    public function searchTimeInfoForItem($tracker_id, $item_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $item_id    = $this->da->escapeInt($item_id);

        $sql = "SELECT CVL.bindvalue_id AS column_id, MAX(C.submitted_on) AS submitted_on
                FROM tracker_artifact AS A
                    INNER JOIN tracker_changeset AS C ON (C.artifact_id = A.id)
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN (
                        tracker_changeset_value AS CV
                        INNER JOIN (
                            SELECT distinct(field_id) FROM tracker_semantic_status WHERE tracker_id = $tracker_id
                        ) AS SS ON (CV.field_id = SS.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL ON (CV.id = CVL.changeset_value_id)
                    ) ON (C.id = CV.changeset_id)
                WHERE A.id = $item_id
                    AND CV.has_changed = 1
                    AND CVL.bindvalue_id <> 100
                    GROUP BY CVL.bindvalue_id";

        return $this->retrieve($sql);
    }
}