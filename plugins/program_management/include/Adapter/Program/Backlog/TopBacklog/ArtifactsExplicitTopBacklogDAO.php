<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\VerifyIsInTopBacklog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\RemovePlannedFeaturesFromTopBacklog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogStore;

final class ArtifactsExplicitTopBacklogDAO extends DataAccessObject implements TopBacklogStore, RemovePlannedFeaturesFromTopBacklog, VerifyIsInTopBacklog
{
    #[\Override]
    public function isInTheExplicitTopBacklog(int $artifact_id): bool
    {
        return $this->getDB()->exists(
            'SELECT COUNT(artifact_id) FROM plugin_program_management_explicit_top_backlog WHERE artifact_id = ?',
            $artifact_id
        );
    }

    /**
     * @psalm-param non-empty-array<int> $artifact_ids
     */
    #[\Override]
    public function addArtifactsToTheExplicitTopBacklog(array $artifact_ids): void
    {
        $statement_artifacts = EasyStatement::open()->in('all_feature_non_present_in_top_backlog_artifact.id IN (?*)', $artifact_ids);
        $this->getDB()->run(
            "INSERT INTO plugin_program_management_explicit_top_backlog(artifact_id)
                SELECT all_feature_non_present_in_top_backlog_artifact.id
                FROM (
                     SELECT feature_artifact.id
                     FROM tracker_artifact AS feature_artifact
                     JOIN plugin_program_management_plan ON (plugin_program_management_plan.plannable_tracker_id = feature_artifact.tracker_id)
                     LEFT JOIN plugin_program_management_explicit_top_backlog ON (plugin_program_management_explicit_top_backlog.artifact_id = feature_artifact.id)
                     WHERE plugin_program_management_explicit_top_backlog.artifact_id IS NULL
                ) AS all_feature_non_present_in_top_backlog_artifact
                LEFT JOIN (
                    SELECT planned_feature_artifact.id
                    FROM tracker_artifact AS planned_feature_artifact
                    JOIN plugin_program_management_plan ON (plugin_program_management_plan.plannable_tracker_id = planned_feature_artifact.tracker_id)
                    JOIN plugin_program_management_program ON (plugin_program_management_program.program_project_id = plugin_program_management_plan.project_id)
                    JOIN tracker AS program_increment_tracker ON (program_increment_tracker.id = plugin_program_management_program.program_increment_tracker_id)
                    JOIN tracker_artifact AS program_increment_artifact ON (program_increment_artifact.tracker_id = program_increment_tracker.id)
                    JOIN tracker_changeset AS program_increment_changeset ON (program_increment_changeset.id = program_increment_artifact.last_changeset_id)
                    JOIN tracker_changeset_value AS program_increment_changeset_value ON (program_increment_changeset_value.changeset_id = program_increment_changeset.id)
                    JOIN tracker_changeset_value_artifactlink AS program_increment_changeset_value_artifact_link ON (
                        program_increment_changeset_value_artifact_link.changeset_value_id = program_increment_changeset_value.id AND program_increment_changeset_value_artifact_link.artifact_id = planned_feature_artifact.id
                    )
                ) AS planned_feature_artifact ON (planned_feature_artifact.id = all_feature_non_present_in_top_backlog_artifact.id)
                WHERE planned_feature_artifact.id IS NULL AND $statement_artifacts",
            ...$statement_artifacts->values()
        );
    }

    /**
     * @psalm-param non-empty-array<int> $artifact_ids
     */
    #[\Override]
    public function removeArtifactsFromExplicitTopBacklog(array $artifact_ids): void
    {
        $statement_artifacts = EasyStatement::open()->in('artifact_id IN (?*)', $artifact_ids);
        $this->getDB()->run(
            "DELETE FROM plugin_program_management_explicit_top_backlog WHERE $statement_artifacts",
            ...$statement_artifacts->values()
        );
    }

    #[\Override]
    public function removeFeaturesPlannedInAProgramIncrementFromTopBacklog(int $potential_program_increment_id): void
    {
        $this->getDB()->run(
            'DELETE plugin_program_management_explicit_top_backlog.*
            FROM tracker_changeset_value_artifactlink
            JOIN tracker_changeset_value ON (tracker_changeset_value.id = tracker_changeset_value_artifactlink.changeset_value_id)
            JOIN tracker_changeset ON (tracker_changeset.id = tracker_changeset_value.changeset_id)
            JOIN tracker_artifact ON (tracker_artifact.last_changeset_id = tracker_changeset.id)
            JOIN plugin_program_management_program ON (tracker_artifact.tracker_id = plugin_program_management_program.program_increment_tracker_id)
            JOIN plugin_program_management_explicit_top_backlog ON (plugin_program_management_explicit_top_backlog.artifact_id = tracker_changeset_value_artifactlink.artifact_id)
            WHERE tracker_changeset.artifact_id = ?',
            $potential_program_increment_id
        );
    }
}
