<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\SearchProgramIncrementLinkedToFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\SearchProgramIncrementsOfProgram;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class ProgramIncrementsDAO extends DataAccessObject implements VerifyIsProgramIncrementTracker, RetrieveProgramIncrementTracker, RetrieveProgramIncrementLabels, VerifyIsProgramIncrement, SearchProgramIncrementLinkedToFeature, SearchProgramIncrementsOfProgram
{
    #[\Override]
    public function searchOpenProgramIncrements(ProgramIdentifier $program): array
    {
        $sql = 'SELECT artifact.id
                FROM tracker_artifact AS artifact
                JOIN tracker_changeset ON (artifact.last_changeset_id = tracker_changeset.id)
                -- get open artifacts
                LEFT JOIN (
                    tracker_semantic_status AS status
                    JOIN tracker_changeset_value AS status_changeset ON (status.field_id = status_changeset.field_id)
                    JOIN tracker_changeset_value_list AS status_value ON (status_changeset.id = status_value.changeset_value_id)
                ) ON (artifact.tracker_id = status.tracker_id AND tracker_changeset.id = status_changeset.changeset_id)
                JOIN plugin_program_management_program AS program ON (program.program_increment_tracker_id = artifact.tracker_id)
                WHERE (status.open_value_id = status_value.bindvalue_id OR status.field_id IS NULL) AND program.program_project_id = ?';

        $rows = $this->getDB()->run($sql, $program->getId());
        return array_map(static fn(array $row): int => $row['id'], $rows);
    }

    /**
     * @return array{id: int}[]
     */
    #[\Override]
    public function getProgramIncrementsLinkToFeatureId(int $artifact_id): array
    {
        $sql = "SELECT parent_art.id AS id
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = parent_art.tracker_id)
                    INNER JOIN tracker                              AS t_linked   ON (t_linked.id = linked_art.tracker_id AND t.group_id = t_linked.group_id)
                    INNER JOIN plugin_program_management_program                  ON (plugin_program_management_program.program_increment_tracker_id = parent_art.tracker_id)
                WHERE linked_art.id = ?";

        return $this->getDB()->run($sql, $artifact_id);
    }

    #[\Override]
    public function isProgramIncrementTracker(int $tracker_id): bool
    {
        $sql  = 'SELECT NULL FROM plugin_program_management_program WHERE program_increment_tracker_id = ?';
        $rows = $this->getDB()->run($sql, $tracker_id);

        return count($rows) > 0;
    }

    #[\Override]
    public function isProgramIncrement(int $artifact_id): bool
    {
        $sql = 'SELECT 1 FROM plugin_program_management_program AS program
                INNER JOIN tracker_artifact ON tracker_artifact.tracker_id = program.program_increment_tracker_id
                WHERE tracker_artifact.id = ?';
        return $this->getDB()->exists($sql, $artifact_id);
    }

    #[\Override]
    public function getProgramIncrementTrackerId(int $project_id): ?int
    {
        $sql = 'SELECT program_increment_tracker_id FROM plugin_program_management_program
                INNER JOIN tracker ON tracker.id = plugin_program_management_program.program_increment_tracker_id
                    WHERE tracker.group_id = ?';

        $tracker_id = $this->getDB()->cell($sql, $project_id);
        if ($tracker_id === false) {
            return null;
        }

        return $tracker_id;
    }

    #[\Override]
    public function getProgramIncrementTrackerIdFromProgramIncrement(ProgramIncrementIdentifier $program_increment): int
    {
        $sql = 'SELECT program_increment_tracker_id FROM plugin_program_management_program AS program
                JOIN tracker_artifact ON tracker_artifact.tracker_id = program.program_increment_tracker_id
                WHERE tracker_artifact.id = ?';

        $program_increment_tracker_id = $this->getDB()->cell($sql, $program_increment->getId());
        if ($program_increment_tracker_id === false) {
            throw new ProgramIncrementTrackerNotFoundException($program_increment);
        }

        return $program_increment_tracker_id;
    }

    /**
     * @psalm-return null|array{program_increment_label: ?string, program_increment_sub_label: ?string}
     */
    #[\Override]
    public function getProgramIncrementLabels(int $program_increment_tracker_id): ?array
    {
        $sql = 'SELECT program_increment_label, program_increment_sub_label FROM plugin_program_management_program WHERE program_increment_tracker_id = ?';
        return $this->getDB()->row($sql, $program_increment_tracker_id);
    }
}
