<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Dao;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;

class ArtifactDao extends DataAccessObject
{
    /**
     * Return all artifacts linked by the given artifact (possible exclusion)
     *
     * @param non-empty-array<int> $artifact_ids Artifact ids to inspect
     * @param int[] $excluded_ids Exclude those ids from the results
     * @return list<array{
     *     id: int,
     *     tracker_id: int,
     * }>
     */
    public function getLinkedArtifactsByIds(array $artifact_ids, array $excluded_ids = []): array
    {
        $artifact_ids_statement = EasyStatement::open()->in('parent_art.id IN (?*)', $artifact_ids);
        $params                 = $artifact_ids;
        $exclude_statement      = '';
        if ($excluded_ids !== []) {
            $exclude_statement = 'AND ' . EasyStatement::open()->in('linked_art.id NOT IN (?*)', $excluded_ids);
            $params            = [...$excluded_ids, ...$params];
        }
        $sql = <<<SQL
        SELECT linked_art.*
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $exclude_statement)
        INNER JOIN tracker                              linked_tracker ON (linked_art.tracker_id = linked_tracker.id)
        WHERE $artifact_ids_statement AND linked_tracker.deletion_date IS NULL
        SQL;

        return $this->getDB()->run($sql, ...$params);
    }

    public function getChildrenForArtifacts(array $artifact_ids): array
    {
        $artifact_ids_statement = EasyStatement::open()->in('parent_art.id IN (?*)', $artifact_ids);
        $params                 = $artifact_ids;

        $sql      = <<<SQL
        SELECT child_art.*, parent_art.id as parent_id
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
            INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     AS child_art  ON (child_art.id = artlink.artifact_id)
            INNER JOIN tracker                              AS child_tracker ON (child_art.tracker_id = child_tracker.id)
            INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = child_art.id)
        WHERE $artifact_ids_statement
            AND child_tracker.deletion_date IS NULL
            AND artlink.nature=?
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        SQL;
        $params[] = ArtifactLinkField::TYPE_IS_CHILD;

        return $this->getDB()->run($sql, ...$params);
    }

    /**
     * @param int[] $tracker_ids
     * @param int[] $additional_artifacts
     */
    public function getLinkedArtifactsOfTrackersConcatenatedToCustomList(int $artifact_id, array $tracker_ids, array $additional_artifacts): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('linked_art.tracker_id IN (?*)', $tracker_ids);
        $params                = [$artifact_id, ...$tracker_ids];

        $additional_artifacts_statement = '';
        if (! empty($additional_artifacts)) {
            $additional_artifacts_statement = EasyStatement::open()->in('OR linked_art.id IN (?*)', $additional_artifacts);
            $params                         = [...$additional_artifacts, ...$params];
        }

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS linked_art.*
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $additional_artifacts_statement)
            INNER JOIN tracker_artifact_priority_rank                  ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
        WHERE parent_art.id = ?
            AND $tracker_ids_statement
        GROUP BY (linked_art.id)
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        SQL;

        return $this->getDB()->run($sql, ...$params);
    }

    /**
     * @param int[] $tracker_ids
     */
    public function getLinkedArtifactsOfTrackersWithLimitAndOffset(int $artifact_id, array $tracker_ids, ?int $limit, ?int $offset): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('linked_art.tracker_id IN (?*)', $tracker_ids);

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS linked_art.*
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id)
            INNER JOIN tracker_artifact_priority_rank                  ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
        WHERE parent_art.id = ?
            AND $tracker_ids_statement
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        LIMIT ? OFFSET ?
        SQL;

        return $this->getDB()->run($sql, ...[$artifact_id, ...$tracker_ids, $limit, $offset]);
    }

    /**
     * @param int[] $tracker_ids
     * @param int[] $excluded_linked_ids
     * @param int[] $additional_artifacts
     */
    public function getLinkedOpenArtifactsOfTrackersNotLinkedToOthers(int $artifact_id, array $tracker_ids, array $excluded_linked_ids, array $additional_artifacts): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('linked_art.tracker_id IN (?*)', $tracker_ids);

        $additional_artifacts_statement = '';
        if (! empty($additional_artifacts)) {
            $additional_artifacts_statement = EasyStatement::open()->in('OR linked_art.id IN (?*)', $additional_artifacts);
        }

        $exclude      = '';
        $submile_null = '';
        if (count($excluded_linked_ids) > 0) {
            $excluded_linked_statement = EasyStatement::open()->in('submile.id IN (?*)', $excluded_linked_ids);

            $exclude = <<<SQL
            -- exlude all those linked to wrong artifacts
            LEFT JOIN (
                tracker_artifact as submile
                INNER JOIN tracker_field AS f2 ON (f2.tracker_id = submile.tracker_id AND f2.formElement_type = 'art_link' AND f2.use_it = 1)
                INNER JOIN tracker_changeset_value AS excluded_cv ON (excluded_cv.changeset_id = submile.last_changeset_id AND excluded_cv.field_id = f2.id)
                INNER JOIN tracker_changeset_value_artifactlink AS artlink2 ON (artlink2.changeset_value_id = excluded_cv.id)
            ) ON (linked_art.id = artlink2.artifact_id AND $excluded_linked_statement)
            SQL;

            $submile_null = 'AND submile.id IS NULL';
        }

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS linked_art.*
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $additional_artifacts_statement)
            INNER JOIN tracker_artifact_priority_rank                  ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
            $exclude
                -- only those with open status
            INNER JOIN tracker AS T ON (linked_art.tracker_id = T.id)
            INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
            INNER JOIN tracker_changeset AS C ON (linked_art.last_changeset_id = C.id)
            LEFT JOIN (
                tracker_semantic_status as SS
                INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
            ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
            LEFT JOIN (
                tracker_changeset_value AS CV2
                INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
            ) ON (C.id = CV2.changeset_id)
        WHERE parent_art.id = ?
            AND (
                SS.field_id IS NULL
                OR
                CVL2.bindvalue_id = SS.open_value_id
             )
            $submile_null
            AND $tracker_ids_statement
        GROUP BY (linked_art.id)
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        SQL;

        return $this->getDB()->run($sql, ...[...$additional_artifacts, ...$excluded_linked_ids, $artifact_id, ...$tracker_ids]);
    }

    /**
     * @param int[] $tracker_ids
     * @param int[] $excluded_linked_ids
     * @param int[] $additional_artifacts
     */
    public function getLinkedArtifactsOfTrackersNotLinkedToOthers(int $artifact_id, array $tracker_ids, array $excluded_linked_ids, array $additional_artifacts): array
    {
        $tracker_ids_statement = EasyStatement::open()->in('linked_art.tracker_id IN (?*)', $tracker_ids);

        $additional_artifacts_statement = '';
        if (! empty($additional_artifacts)) {
            $additional_artifacts_statement = EasyStatement::open()->in('OR linked_art.id IN (?*)', $additional_artifacts);
        }

        $exclude_statement = '';
        $exclude_where     = '';
        if (count($excluded_linked_ids) > 0) {
            $exclude_statement = EasyStatement::open()->in('AND submile.id IN (?*)', $excluded_linked_ids);
            $exclude_where     = 'AND submile.id IS NULL';
        }

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS linked_art.*
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $additional_artifacts_statement)
            INNER JOIN tracker_artifact_priority_rank                  ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
            -- exlude all those linked to wrong artifacts
            LEFT JOIN (
                tracker_artifact as submile
                INNER JOIN tracker_field AS f2 ON (f2.tracker_id = submile.tracker_id AND f2.formElement_type = 'art_link' AND f2.use_it = 1)
                INNER JOIN tracker_changeset_value AS excluded_cv ON (excluded_cv.changeset_id = submile.last_changeset_id AND excluded_cv.field_id = f2.id)
                INNER JOIN tracker_changeset_value_artifactlink AS artlink2 ON (artlink2.changeset_value_id = excluded_cv.id)
            ) ON (linked_art.id = artlink2.artifact_id $exclude_statement)
                -- only those with open status
            INNER JOIN tracker AS T ON (linked_art.tracker_id = T.id)
            INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
            INNER JOIN tracker_changeset AS C ON (linked_art.last_changeset_id = C.id)
            LEFT JOIN (
                tracker_changeset_value AS CV2
                INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
            ) ON (C.id = CV2.changeset_id)
        WHERE parent_art.id = ?
            $exclude_where
            AND $tracker_ids_statement
        GROUP BY (linked_art.id)
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        SQL;

        return $this->getDB()->run($sql, ...[...$additional_artifacts, ...$excluded_linked_ids, $artifact_id, ...$tracker_ids]);
    }

    /**
     * @param int[] $tracker_ids
     * @param int[] $excluded_linked_ids
     * @param int[] $additional_artifacts
     */
    public function getLinkedOpenArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(
        int $artifact_id,
        array $tracker_ids,
        array $excluded_linked_ids,
        array $additional_artifacts,
        ?int $limit,
        ?int $offset,
    ): array {
        $tracker_ids_statement = EasyStatement::open()->in('linked_art.tracker_id IN (?*)', $tracker_ids);

        $exclude      = '';
        $submile_null = '';
        if (count($excluded_linked_ids) > 0) {
            $excluded_linked_ids_statement = EasyStatement::open()->in('submile.id IN (?*)', $excluded_linked_ids);

            $exclude = <<<SQL
            -- exlude all those linked to wrong artifacts
            LEFT JOIN (
                tracker_artifact as submile
                INNER JOIN tracker_field AS f2 ON (f2.tracker_id = submile.tracker_id AND f2.formElement_type = 'art_link' AND f2.use_it = 1)
                INNER JOIN tracker_changeset_value AS excluded_cv ON (excluded_cv.changeset_id = submile.last_changeset_id AND excluded_cv.field_id = f2.id)
                INNER JOIN tracker_changeset_value_artifactlink AS artlink2 ON (artlink2.changeset_value_id = excluded_cv.id)
            ) ON (linked_art.id = artlink2.artifact_id AND $excluded_linked_ids_statement)
            SQL;

            $submile_null = 'AND submile.id IS NULL';
        }

        $additional_artifacts_statement = '';
        if (! empty($additional_artifacts)) {
            $additional_artifacts_statement = EasyStatement::open()->in('OR linked_art.id IN (?*)', $additional_artifacts);
        }

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS linked_art.*
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $additional_artifacts_statement)
            INNER JOIN tracker_artifact_priority_rank                  ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
             $exclude
            INNER JOIN tracker AS T ON (linked_art.tracker_id = T.id)
            INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
            INNER JOIN tracker_changeset AS C ON (linked_art.last_changeset_id = C.id)
            LEFT JOIN (
                tracker_changeset_value AS CV2
                INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
            ) ON (C.id = CV2.changeset_id)
            -- only those with open status
            LEFT JOIN (
                tracker_semantic_status as SS
                INNER JOIN tracker_changeset_value AS CV3       ON (SS.field_id = CV3.field_id)
                INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
            ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
        WHERE parent_art.id = ?
            $submile_null
            AND $tracker_ids_statement
            AND (
                SS.field_id IS NULL -- Use the status semantic only if it is defined
                OR
                CVL2.bindvalue_id = SS.open_value_id
            )
        GROUP BY (linked_art.id)
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        LIMIT ? OFFSET ?
        SQL;

        return $this->getDB()->run($sql, ...[...$additional_artifacts, ...$excluded_linked_ids, $artifact_id, ...$tracker_ids, $limit, $offset]);
    }

    /**
     * @param int[] $tracker_ids
     * @param int[] $excluded_linked_ids
     * @param int[] $additional_artifacts
     */
    public function getLinkedArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(
        int $artifact_id,
        array $tracker_ids,
        array $excluded_linked_ids,
        array $additional_artifacts,
        ?int $limit,
        ?int $offset,
    ): array {
        $filter = 'AND (SS.field_id IS NULL OR CVL2.bindvalue_id = SS.open_value_id)';

        return $this->getLinkedArtifactsToTrackerWithWhereConditionAndLimitAndOffset(
            $artifact_id,
            $tracker_ids,
            $excluded_linked_ids,
            $additional_artifacts,
            $limit,
            $offset,
            $filter
        );
    }

    /**
     * @param int[] $tracker_ids
     * @param int[] $excluded_linked_ids
     * @param int[] $additional_artifacts
     */
    public function getLinkedOpenClosedArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(
        int $artifact_id,
        array $tracker_ids,
        array $excluded_linked_ids,
        array $additional_artifacts,
        ?int $limit,
        ?int $offset,
    ): array {
        return $this->getLinkedArtifactsToTrackerWithWhereConditionAndLimitAndOffset(
            $artifact_id,
            $tracker_ids,
            $excluded_linked_ids,
            $additional_artifacts,
            $limit,
            $offset,
            ''
        );
    }

    /**
     * @param int[] $tracker_ids
     * @param int[] $excluded_linked_ids
     * @param int[] $additional_artifacts
     */
    private function getLinkedArtifactsToTrackerWithWhereConditionAndLimitAndOffset(
        int $artifact_id,
        array $tracker_ids,
        array $excluded_linked_ids,
        array $additional_artifacts,
        ?int $limit,
        ?int $offset,
        string $filter,
    ): array {
        $tracker_ids_statement = EasyStatement::open()->in('linked_art.tracker_id IN (?*)', $tracker_ids);

        $exclude       = '';
        $exclude_where = '';
        if (count($excluded_linked_ids) > 0) {
            $exclude       = EasyStatement::open()->in('AND submile.id IN (?*)', $excluded_linked_ids);
            $exclude_where = 'AND submile.id IS NULL';
        }

        $additional_artifacts_statement = '';
        if (! empty($additional_artifacts)) {
            $additional_artifacts_statement = EasyStatement::open()->in('OR linked_art.id IN (?*)', $additional_artifacts);
        }

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS linked_art.*
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $additional_artifacts_statement)
            INNER JOIN tracker_artifact_priority_rank                  ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
            -- exlude all those linked to wrong artifacts
            LEFT JOIN (
                tracker_artifact as submile
                INNER JOIN tracker_field AS f2 ON (f2.tracker_id = submile.tracker_id AND f2.formElement_type = 'art_link' AND f2.use_it = 1)
                INNER JOIN tracker_changeset_value AS excluded_cv ON (excluded_cv.changeset_id = submile.last_changeset_id AND excluded_cv.field_id = f2.id)
                INNER JOIN tracker_changeset_value_artifactlink AS artlink2 ON (artlink2.changeset_value_id = excluded_cv.id)
            ) ON (linked_art.id = artlink2.artifact_id $exclude)
                -- only those with open status
            INNER JOIN tracker AS T ON (linked_art.tracker_id = T.id)
            INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
            INNER JOIN tracker_changeset AS C ON (linked_art.last_changeset_id = C.id)
            LEFT JOIN (
                tracker_semantic_status as SS
                INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
            ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
            LEFT JOIN (
                tracker_changeset_value AS CV2
                INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
            ) ON (C.id = CV2.changeset_id)
        WHERE parent_art.id = ?
            $filter
            $exclude_where
            AND $tracker_ids_statement
        GROUP BY (linked_art.id)
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        LIMIT ? OFFSET ?
        SQL;

        return $this->getDB()->run($sql, ...[...$additional_artifacts, ...$excluded_linked_ids, $artifact_id, ...$tracker_ids, $limit, $offset]);
    }

    /**
     * Retrieve all artifacts linked by the given one that are of a specific tracker type
     */
    public function getLinkedArtifactsOfTrackerTypeAsString(int $artifact_id, int $tracker_id): array
    {
        $sql = <<<SQL
        SELECT GROUP_CONCAT(DISTINCT linked_art.id) AS artifact_ids
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id)
        WHERE parent_art.id = ?
            AND linked_art.tracker_id = ?
        SQL;

        return $this->getDB()->row($sql, $artifact_id, $tracker_id);
    }

    /**
     * Retrieve all artifacts linked to any of the given ones that are of a specific tracker type
     */
    public function getLinkedArtifactsOfArtifactsOfTrackerTypeAsString(string $artifact_ids, int $tracker_id): array
    {
        $artifact_ids_statement = EasyStatement::open()->in('parent_art.id IN (?*)', explode(',', $artifact_ids));

        $sql = <<<SQL
        SELECT GROUP_CONCAT(DISTINCT linked_art.id) AS artifact_ids
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id)
        WHERE $artifact_ids_statement
            AND linked_art.tracker_id = ?
        SQL;

        return $this->getDB()->row($sql, ...[...$artifact_ids_statement->values(), $tracker_id]);
    }

    /**
     * Return artifact status (open/closed)
     *
     * @param int[] $artifact_ids
     * @return array<array{id: int, status: string}>
     */
    public function getArtifactsStatusByIds(array $artifact_ids): array
    {
        $artifact_ids_statement = EasyStatement::open()->in('A.id IN (?*)', $artifact_ids);

        $sql = <<<SQL
        SELECT A.id, IF(CVL.bindvalue_id IS NULL, ?, ?) AS status
        FROM tracker_artifact AS A
        LEFT JOIN (
            tracker_changeset_value AS CV
            INNER JOIN tracker_semantic_status SS ON (CV.field_id = SS.field_id)
            INNER JOIN tracker_changeset_value_list CVL ON (CV.id = CVL.changeset_value_id AND CVL.bindvalue_id = SS.open_value_id)
        ) ON (A.last_changeset_id = CV.changeset_id)
        WHERE $artifact_ids_statement
        SQL;

        return $this->getDB()->run($sql, Artifact::STATUS_CLOSED, Artifact::STATUS_OPEN, ...$artifact_ids);
    }
}
