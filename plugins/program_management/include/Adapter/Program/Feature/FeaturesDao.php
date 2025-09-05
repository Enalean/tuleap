<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveOpenFeatureCount;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchOpenFeatures;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchPlannableFeatures;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class FeaturesDao extends DataAccessObject implements RetrieveOpenFeatureCount, SearchPlannableFeatures, SearchOpenFeatures
{
    /**
     * @return int[]
     */
    #[\Override]
    public function searchPlannableFeatures(ProgramIdentifier $program): array
    {
        $sql = <<<SQL
        SELECT artifact.id AS artifact_id
            FROM `groups` AS project
                INNER JOIN tracker ON tracker.group_id = project.group_id
                INNER JOIN plugin_program_management_plan AS plan ON plan.plannable_tracker_id = tracker.id
                INNER JOIN tracker_artifact AS artifact ON artifact.tracker_id = tracker.id
                INNER JOIN tracker_changeset ON artifact.last_changeset_id = tracker_changeset.id
                -- get open artifacts
                INNER JOIN (
                tracker_semantic_status AS status
                    INNER JOIN tracker_changeset_value AS status_changeset ON (status.field_id = status_changeset.field_id)
                    INNER JOIN tracker_changeset_value_list AS status_value
                        ON (status_changeset.id = status_value.changeset_value_id AND status.open_value_id = status_value.bindvalue_id)
                ) ON (tracker.id = status.tracker_id AND tracker_changeset.id = status_changeset.changeset_id)
                INNER JOIN plugin_program_management_explicit_top_backlog AS top_backlog ON top_backlog.artifact_id = artifact.id
                INNER JOIN tracker_artifact_priority_rank ON top_backlog.artifact_id = tracker_artifact_priority_rank.artifact_id
        WHERE project.group_id = ?
        ORDER BY tracker_artifact_priority_rank.`rank`
        SQL;

        $rows = $this->getDB()->run($sql, $program->getId());
        return array_map(static fn(array $row): int => $row['artifact_id'], $rows);
    }

    #[\Override]
    public function searchOpenFeatures(int $offset, int $limit, ProgramIdentifier ...$program_identifiers): array
    {
        if (count($program_identifiers) === 0) {
            return [];
        }

        $limit_parameters = [];
        $limit_statement  = '';
        if ($offset !== 0 || $limit !== 0) {
            $limit_statement  = 'LIMIT ? OFFSET ?';
            $limit_parameters = [$limit, $offset];
        }

        $project_ids_condition = $this->getProjectIdsCondition(...$program_identifiers);
        $query                 = $this->getOpenFeaturesQuery(
            'artifact.id AS artifact_id, tracker.group_id AS program_id, title_value.value AS title',
            $project_ids_condition,
        );

        $sql = <<<SQL
            $query
            ORDER BY tracker.id, artifact_id DESC
            $limit_statement
            SQL;

        return $this->getDB()->safeQuery($sql, array_merge($project_ids_condition->values(), [\Project::STATUS_ACTIVE], $limit_parameters));
    }

    /**
     * SQL_CALC_FOUND_ROWS is not used because it's deprecated and slower than just count
     *
     * @see https://dev.mysql.com/worklog/task/?id=12615
     */
    #[\Override]
    public function retrieveOpenFeaturesCount(ProgramIdentifier ...$program_identifiers): int
    {
        if (count($program_identifiers) === 0) {
            return 0;
        }

        $project_ids_condition = $this->getProjectIdsCondition(...$program_identifiers);
        $sql                   = $this->getOpenFeaturesQuery(
            'COUNT(*) AS count',
            $project_ids_condition,
        );

        $rows = $this->getDB()->safeQuery($sql, array_merge($project_ids_condition->values(), [\Project::STATUS_ACTIVE]));
        if (is_array($rows) && count($rows) === 1) {
            return $rows[0]['count'];
        }
        return 0;
    }

    private function getOpenFeaturesQuery(string $selected_fields, EasyStatement $project_ids_condition): string
    {
        return <<<SQL
            SELECT $selected_fields
                FROM `groups` AS project
                    INNER JOIN tracker ON tracker.group_id = project.group_id
                    INNER JOIN plugin_program_management_plan AS plan
                        ON plan.plannable_tracker_id = tracker.id
                    INNER JOIN tracker_artifact AS artifact ON artifact.tracker_id = tracker.id
                    INNER JOIN tracker_changeset ON (artifact.last_changeset_id = tracker_changeset.id)
                    -- get open artifacts
                    INNER JOIN (
                    tracker_semantic_status AS status
                        INNER JOIN tracker_changeset_value AS status_changeset ON (status.field_id = status_changeset.field_id)
                        INNER JOIN tracker_changeset_value_list AS status_value
                            ON (status_changeset.id = status_value.changeset_value_id AND status.open_value_id = status_value.bindvalue_id)
                    ) ON (tracker.id = status.tracker_id AND tracker_changeset.id = status_changeset.changeset_id)
                    -- get title value
                    INNER JOIN (
                        tracker_semantic_title AS title
                            INNER JOIN tracker_changeset_value AS title_changeset ON (title.field_id = title_changeset.field_id)
                            INNER JOIN tracker_changeset_value_text AS title_value on title_changeset.id = title_value.changeset_value_id
                    ) ON (tracker.id = title.tracker_id AND tracker_changeset.id = title_changeset.changeset_id)
            WHERE project.group_id IN ($project_ids_condition)
                AND project.status = ?
                AND tracker.deletion_date IS NULL
            SQL;
    }

    private function getProjectIdsCondition(ProgramIdentifier ...$program_identifiers): EasyStatement
    {
        return EasyStatement::open()->in(
            '?*',
            array_map(static fn (ProgramIdentifier $program) => $program->getId(), $program_identifiers)
        );
    }
}
