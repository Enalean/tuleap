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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\SearchFeatures;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;

final class ContentDao extends DataAccessObject implements SearchFeatures
{
    #[\Override]
    public function searchFeatures(ProgramIncrementIdentifier $program_increment): array
    {
        $sql = <<<SQL
        SELECT artifact_link_value.artifact_id AS artifact_id
        FROM tracker_artifact                               AS program_increment
            INNER JOIN tracker_changeset                    AS tc                        ON (program_increment.id = tc.artifact_id AND tc.id = program_increment.last_changeset_id)
            INNER JOIN tracker_changeset_value              AS cv                        ON tc.id = cv.changeset_id
            INNER JOIN tracker_changeset_value_artifactlink AS artifact_link_value       ON artifact_link_value.changeset_value_id = cv.id
            INNER JOIN tracker_artifact                     AS feature_artifact          ON artifact_link_value.artifact_id = feature_artifact.id
            INNER JOIN plugin_program_management_plan       AS plan                      ON feature_artifact.tracker_id = plan.plannable_tracker_id
            INNER JOIN tracker_changeset                    AS feature_changeset         ON (
                feature_artifact.id = feature_changeset.artifact_id
                AND feature_changeset.id = feature_artifact.last_changeset_id
            )
            LEFT JOIN plugin_program_management_explicit_top_backlog AS top_backlog ON top_backlog.artifact_id = program_increment.id
            INNER JOIN tracker_artifact_priority_rank ON feature_artifact.id = tracker_artifact_priority_rank.artifact_id
        WHERE program_increment.id =  ?
          AND top_backlog.artifact_id IS NULL
        ORDER BY tracker_artifact_priority_rank.`rank`
        SQL;

        $rows = $this->getDB()->run($sql, $program_increment->getId());
        return array_map(static fn(array $row): int => $row['artifact_id'], $rows);
    }
}
