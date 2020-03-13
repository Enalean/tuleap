<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class AgileDashboard_BacklogItemDao extends DataAccessObject
{
    public const STATUS_OPEN   = 1;
    public const STATUS_CLOSED = 0;

    public function getBacklogArtifacts($milestone_artifact_id)
    {
        $milestone_artifact_id = $this->da->escapeInt($milestone_artifact_id);
        $sql = "SELECT child_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                    INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
                    INNER JOIN plugin_agiledashboard_planning_backlog_tracker backlog ON (backlog.planning_id = planning.id AND child_art.tracker_id = backlog.tracker_id)
                    INNER JOIN tracker_artifact_priority_rank                       ON (tracker_artifact_priority_rank.artifact_id = child_art.id)
                WHERE parent_art.id = $milestone_artifact_id
                ORDER BY tracker_artifact_priority_rank.rank ASC";

        return $this->retrieve($sql);
    }

    public function getBacklogArtifactsWithLimitAndOffset($milestone_artifact_id, $limit, $offset)
    {
        $milestone_artifact_id = $this->da->escapeInt($milestone_artifact_id);
        $limit                 = $this->da->escapeInt($limit);
        $offset                = $this->da->escapeInt($offset);
        $sql = "SELECT SQL_CALC_FOUND_ROWS child_art.*, tracker_artifact_priority_rank.rank as rank
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                    INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
                    INNER JOIN plugin_agiledashboard_planning_backlog_tracker backlog ON (backlog.planning_id = planning.id AND child_art.tracker_id = backlog.tracker_id)
                    INNER JOIN tracker_artifact_priority_rank                       ON (tracker_artifact_priority_rank.artifact_id = child_art.id)
                WHERE parent_art.id = $milestone_artifact_id
                ORDER BY tracker_artifact_priority_rank.rank ASC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function getTopBacklogArtifacts(array $backlog_tracker_ids)
    {
        $backlog_tracker_ids = $this->da->escapeIntImplode($backlog_tracker_ids);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM tracker_artifact
                    INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = tracker_artifact.id)
                WHERE tracker_id IN ($backlog_tracker_ids)
                ORDER BY tracker_artifact_priority_rank.rank ASC";

        return $this->retrieve($sql);
    }

    public function getTopBacklogArtifactsWithLimitAndOffset(array $backlog_tracker_ids, $limit, $offset)
    {
        $backlog_tracker_ids = $this->da->escapeIntImplode($backlog_tracker_ids);
        $limit               = $this->da->escapeInt($limit);
        $offset              = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM tracker_artifact
                    INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = tracker_artifact.id)
                WHERE tracker_id IN ($backlog_tracker_ids)
                ORDER BY tracker_artifact_priority_rank.rank ASC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function getOpenUnplannedTopBacklogArtifacts(array $backlog_tracker_ids, $sub_milestone_ids)
    {
        $backlog_tracker_ids = $this->da->escapeIntImplode($backlog_tracker_ids);
        $sub_milestone_ids   = $this->da->escapeIntImplode($sub_milestone_ids);

        $sql = "SELECT SQL_CALC_FOUND_ROWS art_1.*
                FROM tracker_artifact AS art_1
                    INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = art_1.id)
                    -- Open status section
                    INNER JOIN tracker AS T              ON (art_1.tracker_id = T.id)
                    INNER JOIN groups AS G               ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C    ON (art_1.last_changeset_id = C.id)
                    LEFT JOIN (                                                                -- Look if there is any status /open/ semantic defined
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3       ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                    -- ensure that the artifact is not planned in a milestone by joins + IS NULL (below)
                    LEFT JOIN ( tracker_artifact parent_art
                        INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                        INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                        INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                        INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                        INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
                    ) ON (art_1.id = child_art.id )
                WHERE art_1.tracker_id IN ($backlog_tracker_ids)
                    AND (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                     )
                    AND child_art.id IS NULL
                ORDER BY tracker_artifact_priority_rank.rank ASC";

        return $this->retrieve($sql);
    }

    public function getUnplannedTopBacklogArtifacts(array $backlog_tracker_ids)
    {
        $backlog_tracker_ids = $this->da->escapeIntImplode($backlog_tracker_ids);

        $sql = "SELECT SQL_CALC_FOUND_ROWS art_1.*
                FROM tracker_artifact AS art_1
                    INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = art_1.id)
                    -- Open status section
                    INNER JOIN tracker AS T              ON (art_1.tracker_id = T.id)
                    INNER JOIN groups AS G               ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C    ON (art_1.last_changeset_id = C.id)
                    -- ensure that the artifact is not planned in a milestone by joins + IS NULL (below)
                    LEFT JOIN ( tracker_artifact parent_art
                        INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                        INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                        INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                        INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                        INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
                    ) ON (art_1.id = child_art.id )
                WHERE art_1.tracker_id IN ($backlog_tracker_ids)
                    AND child_art.id IS NULL
                ORDER BY tracker_artifact_priority_rank.rank ASC";

        return $this->retrieve($sql);
    }

    public function getOpenUnplannedTopBacklogArtifactsWithLimitAndOffset(array $backlog_tracker_ids, $limit, $offset)
    {
        $backlog_tracker_ids = $this->da->escapeIntImplode($backlog_tracker_ids);
        $limit               = $this->da->escapeInt($limit);
        $offset              = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS art_1.*
                FROM tracker_artifact AS art_1
                    INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = art_1.id)
                        -- Open status section
                    INNER JOIN tracker AS T              ON (art_1.tracker_id = T.id)
                    INNER JOIN groups AS G               ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C    ON (art_1.last_changeset_id = C.id)
                    -- Look if there is any status /open/ semantic defined
                    LEFT JOIN (
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3       ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                    -- ensure that the artifact is not planned in a milestone by joins + IS NULL (below)
                    LEFT JOIN ( tracker_artifact parent_art
                        INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                        INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                        INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                        INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                        INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
                    ) ON (art_1.id = child_art.id )
                WHERE art_1.tracker_id IN ($backlog_tracker_ids)
                    AND (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                     )
                    AND child_art.id IS NULL
                ORDER BY tracker_artifact_priority_rank.rank ASC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function getUnplannedTopBacklogArtifactsWithLimitAndOffset(array $backlog_tracker_ids, $limit, $offset)
    {
        $backlog_tracker_ids = $this->da->escapeIntImplode($backlog_tracker_ids);
        $limit               = $this->da->escapeInt($limit);
        $offset              = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS art_1.*
                FROM tracker_artifact AS art_1
                    INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = art_1.id)
                        -- Open status section
                    INNER JOIN tracker AS T              ON (art_1.tracker_id = T.id)
                    INNER JOIN groups AS G               ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C    ON (art_1.last_changeset_id = C.id)
                    -- ensure that the artifact is not planned in a milestone by joins + IS NULL (below)
                    LEFT JOIN ( tracker_artifact parent_art
                        INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                        INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                        INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                        INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                        INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
                    ) ON (art_1.id = child_art.id )
                WHERE art_1.tracker_id IN ($backlog_tracker_ids)
                    AND child_art.id IS NULL
                ORDER BY tracker_artifact_priority_rank.rank ASC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function getPlannedItemIds(array $milestone_artifact_ids)
    {
        $milestone_artifact_ids = $this->da->escapeIntImplode($milestone_artifact_ids);

        $sql = "SELECT GROUP_CONCAT(id) AS ids
                FROM (
                    SELECT child_art.*
                    FROM tracker_artifact parent_art
                        INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                        INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                        INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                        INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                        INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
                        INNER JOIN plugin_agiledashboard_planning_backlog_tracker backlog ON (backlog.planning_id = planning.id AND child_art.tracker_id = backlog.tracker_id)
                        INNER JOIN tracker_artifact_priority_rank                       ON (tracker_artifact_priority_rank.artifact_id = child_art.id)
                    WHERE parent_art.id IN ($milestone_artifact_ids)
                    ) AS R";
        $row = $this->retrieve($sql)->getRow();
        if ($row && $row['ids'] != null) {
            return explode(',', $row['ids']);
        }
        return array();
    }

    public function getArtifactsSemantics(array $artifact_ids, array $semantics)
    {
        $artifact_ids = $this->da->escapeIntImplode($artifact_ids);

        $select_fields = array('artifact.id');
        $join_fields   = array();
        if (in_array(Tracker_Semantic_Title::NAME, $semantics)) {
            $select_fields[] = 'CVT.value as ' . Tracker_Semantic_Title::NAME . ', CVT.body_format AS title_format';
            $join_fields[]   = 'LEFT JOIN (
                                  tracker_changeset_value                 AS CV0
                                  INNER JOIN tracker_semantic_title       AS ST  ON (
                                      CV0.field_id = ST.field_id
                                  )
                                  INNER JOIN tracker_changeset_value_text AS CVT ON (
                                      CV0.id       = CVT.changeset_value_id
                                  )
                              ) ON (c.id = CV0.changeset_id)';
        } else {
            $select_fields[] = '"" as title';
        }

        if (in_array(Tracker_Semantic_Status::NAME, $semantics)) {
            $select_fields[] = '(SS0.open_value_id IS NOT NULL OR SS1.open_value_id IS NULL) as ' . Tracker_Semantic_Status::NAME;
            $join_fields[]   = 'LEFT JOIN (
                                    tracker_changeset_value                 AS CV1
                                    INNER JOIN tracker_semantic_status      AS SS0  ON (
                                        CV1.field_id         = SS0.field_id
                                    )
                                    INNER JOIN tracker_changeset_value_list AS CVL ON (
                                        CV1.id                = CVL.changeset_value_id
                                        AND SS0.open_value_id = CVL.bindvalue_id
                                    )
                                 ) ON (c.id = CV1.changeset_id)
                                 LEFT JOIN tracker_semantic_status AS SS1 ON (
                                         artifact.tracker_id = SS1.tracker_id
                                        AND CVL.bindvalue_id IS NULL)';
        } else {
            $select_fields[] = '0 as status';
        }

        $sql = "SELECT " . implode(',', $select_fields) . "
                FROM tracker_artifact AS artifact
                    INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)
                    " . implode('', $join_fields) . "
                WHERE artifact.id IN ($artifact_ids)
                GROUP by artifact.id";
        return $this->retrieve($sql);
    }
}
