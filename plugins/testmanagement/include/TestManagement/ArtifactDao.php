<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\TestManagement\Nature\NatureCoveredByPresenter;

class ArtifactDao extends DataAccessObject
{
    public function searchPaginatedByTrackerId(int $tracker_id, ?int $milestone_id, int $limit, int $offset, bool $reverse_order): array
    {
        $order = ($reverse_order) ? 'DESC' : 'ASC';

        $milestone_filter = $this->milestoneSQLFilter($milestone_id);
        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*
                FROM tracker_artifact AS A
                $milestone_filter A.tracker_id = ?
                ORDER BY A.id $order
                LIMIT ? OFFSET ?";

        return $this->getDB()->run($sql, $milestone_id, $tracker_id, $limit, $offset);
    }

    public function searchPaginatedOpenByTrackerId(int $tracker_id, ?int $milestone_id, int $limit, int $offset): array
    {
        $milestone_filter = $this->milestoneSQLFilter($milestone_id);
        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = ?)
                    INNER JOIN tracker_changeset AS C ON (A.last_changeset_id = C.id)
                    LEFT JOIN (
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                $milestone_filter (
                    SS.field_id IS NULL
                    OR
                    CVL2.bindvalue_id = SS.open_value_id
                )
                ORDER BY A.id DESC
                LIMIT ? OFFSET ?";

        return $this->getDB()->run($sql, $tracker_id, $milestone_id, $limit, $offset);
    }

    public function searchPaginatedClosedByTrackerId(int $tracker_id, ?int $milestone_id, int $limit, int $offset): array
    {
        $milestone_filter = $this->milestoneSQLFilter($milestone_id);
        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS t ON (A.tracker_id = t.id)
                    INNER JOIN tracker_semantic_status AS ss USING(tracker_id)
                    INNER JOIN tracker_changeset_value AS cv ON(cv.field_id = ss.field_id AND A.last_changeset_id = cv.changeset_id)
                    INNER JOIN tracker_changeset_value_list AS cvl ON(cvl.changeset_value_id = cv.id)
                    INNER JOIN tracker_changeset AS tc ON (tc.artifact_id = A.id)
                    LEFT JOIN tracker_semantic_status AS open_values ON (cvl.bindvalue_id = open_values.open_value_id AND open_values.tracker_id = t.id)
                $milestone_filter open_values.open_value_id IS NULL AND t.id = ?
                GROUP BY A.id
                ORDER BY A.id DESC
                LIMIT ? OFFSET ?";

        return $this->getDB()->run($sql, $milestone_id, $tracker_id, $limit, $offset);
    }

    private function milestoneSQLFilter(?int $milestone_id): string
    {
        if ($milestone_id === 0 || $milestone_id === null) {
            return 'WHERE (0 = IFNULL(?, 0)) AND ';
        }

        return "INNER JOIN (
                   tracker_field AS milestone_f
               ) ON (milestone_f.tracker_id = A.tracker_id AND milestone_f.formElement_type = 'art_link' AND use_it = 1)
               INNER JOIN (
                   tracker_changeset_value AS milestone_cv
               ) ON (milestone_cv.changeset_id = A.last_changeset_id AND milestone_cv.field_id = milestone_f.id)
               INNER JOIN (
                   tracker_changeset_value_artifactlink AS milestone_artlink
               ) ON (milestone_artlink.changeset_value_id = milestone_cv.id AND milestone_artlink.artifact_id = ?) WHERE ";
    }

    /**
     * @param string[] $natures
     * @psalm-param non-empty-array<string> $natures
     * @psalm-param non-empty-array $artifacts_ids
     * @param false|int $target_tracker_id
     */
    public function searchPaginatedLinkedArtifactsByLinkNatureAndTrackerId(
        array $artifacts_ids,
        array $natures,
        $target_tracker_id,
        int $limit,
        int $offset
    ): array {
        $where_statement      = EasyStatement::open()->in('parent_art.id IN (?*)', $artifacts_ids)
            ->andIn('IFNULL(artlink.nature, "") IN (?*)', $natures);

        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (linked_art.tracker_id = t.id AND t.id = ?)
                WHERE $where_statement
                LIMIT ?
                OFFSET ?";

        return $this->getDB()->run($sql, ...array_merge([$target_tracker_id], $where_statement->values(), [$limit, $offset]));
    }

    /**
     * @param false|int $campaign_tracker_id
     */
    public function searchCampaignArtifactForExecution(int $execution_artifact_id, $campaign_tracker_id): ?array
    {
        $sql = "SELECT parent_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id)
                WHERE parent_art.tracker_id = ?
                  AND linked_art.id = ?";

        return $this->getDB()->row($sql, $campaign_tracker_id, $execution_artifact_id);
    }

    public function searchPaginatedExecutionArtifactsForCampaign(
        int $campaign_artifact_id,
        int $execution_tracker_id,
        int $limit,
        int $offset
    ): array {
        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id)
                WHERE parent_art.id = ?
                  AND linked_art.tracker_id = ?
                LIMIT ?
                OFFSET ?";

        return $this->getDB()->run($sql, $campaign_artifact_id, $execution_tracker_id, $limit, $offset);
    }

    public function searchExecutionArtifactsForCampaign(
        int $campaign_artifact_id,
        int $execution_tracker_id
    ): array {
        $sql = "SELECT DISTINCT linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id)
                WHERE parent_art.id = ?
                  AND linked_art.tracker_id = ?";

        return $this->getDB()->run($sql, $campaign_artifact_id, $execution_tracker_id);
    }

    /**
     * @param false|int $test_exec_tracker_id
     */
    public function searchFirstRequirementId(int $test_definition_id, $test_exec_tracker_id): ?array
    {
        $sql = "SELECT DISTINCT a.id
                FROM tracker_changeset_value_artifactlink AS artlink
                    JOIN tracker_changeset_value          AS cv ON (cv.id = artlink.changeset_value_id)
                    JOIN tracker_artifact                 AS a  ON (a.last_changeset_id = cv.changeset_id)
                    JOIN tracker                          AS t  ON (t.id = a.tracker_id)
                WHERE artlink.artifact_id = ?
                  AND artlink.nature = ?
                  AND t.id != ?
                ORDER BY a.id ASC
                LIMIT 1";

        return $this->getDB()->row($sql, $test_definition_id, NatureCoveredByPresenter::NATURE_COVERED_BY, $test_exec_tracker_id);
    }
}
