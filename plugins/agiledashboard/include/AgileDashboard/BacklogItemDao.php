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

declare(strict_types=1);

namespace Tuleap\AgileDashboard;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;

class BacklogItemDao extends DataAccessObject
{
    public const int STATUS_OPEN   = 1;
    public const int STATUS_CLOSED = 0;

    /**
     * @return array<array{id: int}>
     */
    public function getBacklogArtifacts(?int $milestone_artifact_id): array
    {
        $sql = <<<SQL
        SELECT child_art.*
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
            INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
            INNER JOIN plugin_agiledashboard_planning_backlog_tracker backlog ON (backlog.planning_id = planning.id AND child_art.tracker_id = backlog.tracker_id)
            INNER JOIN tracker_artifact_priority_rank                  ON (tracker_artifact_priority_rank.artifact_id = child_art.id)
        WHERE parent_art.id = ?
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        SQL;

        return $this->getDB()->run($sql, $milestone_artifact_id);
    }

    public function getBacklogArtifactsWithLimitAndOffset(int $milestone_artifact_id, int $limit, int $offset): array
    {
        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS child_art.*, tracker_artifact_priority_rank.`rank` as `rank`
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
            INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
            INNER JOIN plugin_agiledashboard_planning_backlog_tracker backlog ON (backlog.planning_id = planning.id AND child_art.tracker_id = backlog.tracker_id)
            INNER JOIN tracker_artifact_priority_rank                  ON (tracker_artifact_priority_rank.artifact_id = child_art.id)
        WHERE parent_art.id = ?
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        LIMIT ? OFFSET ?
        SQL;

        return $this->getDB()->run($sql, $milestone_artifact_id, $limit, $offset);
    }

    /**
     * @param int[] $backlog_tracker_ids
     */
    public function getTopBacklogArtifacts(array $backlog_tracker_ids): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('tracker_id IN (?*)', $backlog_tracker_ids);

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS *
        FROM tracker_artifact
            INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = tracker_artifact.id)
        WHERE $tracker_ids_statement
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        SQL;

        return $this->getDB()->run($sql, ...$backlog_tracker_ids);
    }

    /**
     * @param int[] $backlog_tracker_ids
     */
    public function getTopBacklogArtifactsWithLimitAndOffset(array $backlog_tracker_ids, int $limit, int $offset): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('tracker_id IN (?*)', $backlog_tracker_ids);

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS *
        FROM tracker_artifact
            INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = tracker_artifact.id)
        WHERE $tracker_ids_statement
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        LIMIT ? OFFSET ?
        SQL;

        return $this->getDB()->run($sql, ...[...$backlog_tracker_ids, $limit, $offset]);
    }

    /**
     * @param int[] $backlog_tracker_ids
     */
    public function getOpenUnplannedTopBacklogArtifacts(array $backlog_tracker_ids): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('art_1.tracker_id IN (?*)', $backlog_tracker_ids);

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS art_1.*
        FROM tracker_artifact AS art_1
            INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = art_1.id)
            -- Open status section
            INNER JOIN tracker AS T              ON (art_1.tracker_id = T.id)
            INNER JOIN `groups` AS G               ON (G.group_id = T.group_id)
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
        WHERE $tracker_ids_statement
            AND (
                SS.field_id IS NULL -- Use the status semantic only if it is defined
                OR
                CVL2.bindvalue_id = SS.open_value_id
             )
            AND child_art.id IS NULL
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        SQL;

        return $this->getDB()->run($sql, ...$backlog_tracker_ids);
    }

    /**
     * @param int[] $backlog_tracker_ids
     */
    public function getUnplannedTopBacklogArtifacts(array $backlog_tracker_ids): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('art_1.tracker_id IN (?*)', $backlog_tracker_ids);

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS art_1.*
        FROM tracker_artifact AS art_1
            INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = art_1.id)
            -- Open status section
            INNER JOIN tracker AS T              ON (art_1.tracker_id = T.id)
            INNER JOIN `groups` AS G               ON (G.group_id = T.group_id)
            INNER JOIN tracker_changeset AS C    ON (art_1.last_changeset_id = C.id)
            -- ensure that the artifact is not planned in a milestone by joins + IS NULL (below)
            LEFT JOIN ( tracker_artifact parent_art
                INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
            ) ON (art_1.id = child_art.id )
        WHERE $tracker_ids_statement
            AND child_art.id IS NULL
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        SQL;

        return $this->getDB()->run($sql, ...$backlog_tracker_ids);
    }

    /**
     * @param int[] $backlog_tracker_ids
     */
    public function getOpenUnplannedTopBacklogArtifactsWithLimitAndOffset(array $backlog_tracker_ids, int $limit, int $offset): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('art_1.tracker_id IN (?*)', $backlog_tracker_ids);

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS art_1.*
        FROM tracker_artifact AS art_1
            INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = art_1.id)
                -- Open status section
            INNER JOIN tracker AS T              ON (art_1.tracker_id = T.id)
            INNER JOIN `groups` AS G               ON (G.group_id = T.group_id)
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
        WHERE $tracker_ids_statement
            AND (
                SS.field_id IS NULL -- Use the status semantic only if it is defined
                OR
                CVL2.bindvalue_id = SS.open_value_id
             )
            AND child_art.id IS NULL
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        LIMIT ? OFFSET ?
        SQL;

        return $this->getDB()->run($sql, ...[...$backlog_tracker_ids, $limit, $offset]);
    }

    /**
     * @param int[] $backlog_tracker_ids
     */
    public function getUnplannedTopBacklogArtifactsWithLimitAndOffset(array $backlog_tracker_ids, int $limit, int $offset): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('art_1.tracker_id IN (?*)', $backlog_tracker_ids);

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS art_1.*
        FROM tracker_artifact AS art_1
            INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = art_1.id)
                -- Open status section
            INNER JOIN tracker AS T              ON (art_1.tracker_id = T.id)
            INNER JOIN `groups` AS G               ON (G.group_id = T.group_id)
            INNER JOIN tracker_changeset AS C    ON (art_1.last_changeset_id = C.id)
            -- ensure that the artifact is not planned in a milestone by joins + IS NULL (below)
            LEFT JOIN ( tracker_artifact parent_art
                INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
            ) ON (art_1.id = child_art.id )
        WHERE $tracker_ids_statement
            AND child_art.id IS NULL
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        LIMIT ? OFFSET ?
        SQL;

        return $this->getDB()->run($sql, ...[...$backlog_tracker_ids, $limit, $offset]);
    }

    /**
     * @param int[] $backlog_tracker_ids
     */
    public function getOpenClosedUnplannedTopBacklogArtifactsWithLimitAndOffset(array $backlog_tracker_ids, ?int $limit, ?int $offset): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('art_1.tracker_id IN (?*)', $backlog_tracker_ids);

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS art_1.*
        FROM tracker_artifact AS art_1
            INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = art_1.id)
            INNER JOIN tracker AS T              ON (art_1.tracker_id = T.id)
            INNER JOIN `groups` AS G               ON (G.group_id = T.group_id)
            INNER JOIN tracker_changeset AS C    ON (art_1.last_changeset_id = C.id)
            -- ensure that the artifact is not planned in a milestone by joins
            LEFT JOIN ( tracker_artifact parent_art
                INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                INNER JOIN tracker_artifact                     AS child_art  ON (child_art.id = artlink.artifact_id)
                INNER JOIN plugin_agiledashboard_planning       AS planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
            ) ON (art_1.id = child_art.id )
        WHERE $tracker_ids_statement
            AND child_art.id IS NULL
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        LIMIT ? OFFSET ?
        SQL;

        return $this->getDB()->run($sql, ...[...$backlog_tracker_ids, $limit, $offset]);
    }

    /**
     * @param int[] $milestone_artifact_ids
     * @return int[]
     */
    public function getPlannedItemIds(array $milestone_artifact_ids): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('parent_art.id IN (?*)', $milestone_artifact_ids);

        $sql = <<<SQL
        SELECT child_art.id
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
            INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
            INNER JOIN plugin_agiledashboard_planning_backlog_tracker backlog ON (backlog.planning_id = planning.id AND child_art.tracker_id = backlog.tracker_id)
            INNER JOIN tracker_artifact_priority_rank                  ON (tracker_artifact_priority_rank.artifact_id = child_art.id)
        WHERE $tracker_ids_statement
        SQL;
        return $this->getDB()->column($sql, $milestone_artifact_ids);
    }

    /**
     * @param int[] $artifact_ids
     * @param string[] $semantics
     */
    public function getArtifactsSemantics(array $artifact_ids, array $semantics): array
    {
        $artifact_id_statement = EasyStatement::open()->in('artifact.id IN (?*)', $artifact_ids);

        $select_fields = ['artifact.id'];
        $join_fields   = [];
        if (in_array(TrackerSemanticTitle::NAME, $semantics)) {
            $select_fields[] = 'CVT.value as title, CVT.body_format AS title_format';
            $join_fields[]   = <<<SQL
            LEFT JOIN (
                tracker_changeset_value                 AS CV0
                INNER JOIN tracker_semantic_title       AS ST  ON (
                    CV0.field_id = ST.field_id
                )
                INNER JOIN tracker_changeset_value_text AS CVT ON (
                    CV0.id       = CVT.changeset_value_id
                )
            ) ON (c.id = CV0.changeset_id)
            SQL;
        } else {
            $select_fields[] = '"" as title';
        }

        if (in_array(TrackerSemanticStatus::NAME, $semantics)) {
            $select_fields[] = '(SS0.open_value_id IS NOT NULL OR SS1.open_value_id IS NULL) as status';
            $join_fields[]   = <<<SQL
            LEFT JOIN (
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
                AND CVL.bindvalue_id IS NULL
            )
            SQL;
        } else {
            $select_fields[] = '0 as status';
        }

        $select_statement = implode(', ', $select_fields);
        $join_statement   = implode("\n", $join_fields);

        $sql = <<<SQL
        SELECT $select_statement
        FROM tracker_artifact AS artifact
            INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)
            $join_statement
        WHERE $artifact_id_statement
        SQL;
        return $this->getDB()->run($sql, ...$artifact_ids);
    }
}
