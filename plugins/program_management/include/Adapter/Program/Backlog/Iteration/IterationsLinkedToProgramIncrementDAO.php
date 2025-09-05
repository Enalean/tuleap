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
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\SearchIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIterationHasBeenLinkedBefore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;

final class IterationsLinkedToProgramIncrementDAO extends DataAccessObject implements SearchIterations, VerifyIterationHasBeenLinkedBefore
{
    #[\Override]
    public function searchIterations(ProgramIncrementIdentifier $program_increment): array
    {
        $sql = "SELECT iteration.id AS id, iteration.last_changeset_id AS changeset_id
                    FROM tracker_artifact AS program_increment
                    JOIN tracker AS tpi
                        ON (program_increment.tracker_id = tpi.id AND tpi.deletion_date IS NULL)
                    JOIN tracker_field AS f
                        ON (tpi.id = f.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
                    JOIN plugin_program_management_program AS program
                        ON program.program_increment_tracker_id = program_increment.tracker_id
                    JOIN tracker_changeset_value AS tcv
                        ON (tcv.changeset_id = program_increment.last_changeset_id AND tcv.field_id = f.id)
                    JOIN tracker_changeset_value_artifactlink AS artifact_link
                        ON artifact_link.changeset_value_id = tcv.id
                    JOIN tracker_artifact AS iteration
                        ON (artifact_link.artifact_id = iteration.id AND program.iteration_tracker_id = iteration.tracker_id)
                    JOIN tracker AS ti
                        ON (iteration.tracker_id = ti.id AND ti.deletion_date IS NULL)
                WHERE program_increment.id = ? AND artifact_link.nature = ?";

        $rows = $this->getDB()->run(
            $sql,
            $program_increment->getId(),
            \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::TYPE_IS_CHILD
        ) ?? [];
        return $rows;
    }

    #[\Override]
    public function hasIterationBeenLinkedBefore(
        ProgramIncrementIdentifier $program_increment,
        IterationIdentifier $iteration,
    ): bool {
        // Return 1 if the given iteration has been linked by given program increment at least once
        // before the last changeset
        $sql = 'SELECT COUNT(*)
                    FROM tracker_artifact AS program_increment
                    JOIN plugin_program_management_program AS program
                        ON program.program_increment_tracker_id = program_increment.tracker_id
                    JOIN tracker_changeset
                        ON (tracker_changeset.artifact_id = program_increment.id
                        AND tracker_changeset.id != program_increment.last_changeset_id)
                    JOIN tracker_changeset_value
                        ON tracker_changeset_value.changeset_id = tracker_changeset.id
                    JOIN tracker_changeset_value_artifactlink AS artifact_link
                        ON artifact_link.changeset_value_id = tracker_changeset_value.id
                    JOIN tracker_artifact AS iteration
                        ON (artifact_link.artifact_id = iteration.id
                        AND program.iteration_tracker_id = iteration.tracker_id)
                    WHERE program_increment.id = ?
                      AND iteration.id = ?
                      AND artifact_link.nature = ?';

        return $this->getDB()->exists(
            $sql,
            $program_increment->getId(),
            $iteration->getId(),
            \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::TYPE_IS_CHILD
        );
    }
}
