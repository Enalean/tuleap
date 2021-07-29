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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\SearchIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;

final class IterationsLinkedToProgramIncrementDAO extends DataAccessObject implements SearchIterations
{
    public function searchIterations(ProgramIncrementIdentifier $program_increment): array
    {
        $sql = 'SELECT iteration.id
                    FROM tracker_artifact AS program_increment
                    JOIN plugin_program_management_program AS program
                        ON program.program_increment_tracker_id = program_increment.tracker_id
                    JOIN tracker_changeset_value
                        ON tracker_changeset_value.changeset_id = program_increment.last_changeset_id
                    JOIN tracker_changeset_value_artifactlink AS artifact_link
                        ON artifact_link.changeset_value_id = tracker_changeset_value.id
                    JOIN tracker_artifact AS iteration
                        ON (artifact_link.artifact_id = iteration.id AND program.iteration_tracker_id = iteration.tracker_id)
                WHERE program_increment.id = ? AND artifact_link.nature = ?';

        $rows = $this->getDB()->col(
            $sql,
            0,
            $program_increment->getId(),
            \Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD
        ) ?? [];
        return $rows;
    }
}
