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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIteration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class IterationsDAO extends DataAccessObject implements VerifyIsIterationTracker, RetrieveIterationTracker, RetrieveIterationLabels, VerifyIsIteration
{
    #[\Override]
    public function isIterationTracker(int $tracker_id): bool
    {
        $sql  = 'SELECT NULL FROM plugin_program_management_program WHERE iteration_tracker_id = ?';
        $rows = $this->getDB()->run($sql, $tracker_id);

        return count($rows) > 0;
    }

    #[\Override]
    public function isIteration(int $artifact_id): bool
    {
        $sql = 'SELECT COUNT(*) FROM plugin_program_management_program AS program
                JOIN tracker_artifact ON tracker_artifact.tracker_id = program.iteration_tracker_id
                WHERE tracker_artifact.id = ?';
        return $this->getDB()->exists($sql, $artifact_id);
    }

    #[\Override]
    public function getIterationTrackerId(ProgramIdentifier $program_identifier): ?int
    {
        $sql = 'SELECT iteration_tracker_id FROM plugin_program_management_program
                INNER JOIN tracker ON tracker.id = plugin_program_management_program.iteration_tracker_id
                    WHERE tracker.group_id = ?';

        $tracker_id = $this->getDB()->cell($sql, $program_identifier->getId());
        if ($tracker_id === false) {
            return null;
        }

        return $tracker_id;
    }

    #[\Override]
    public function getIterationTrackerIdFromIteration(IterationIdentifier $iteration): int
    {
        $sql = <<<SQL
            SELECT program.iteration_tracker_id FROM plugin_program_management_program AS program
            JOIN tracker_artifact ON tracker_artifact.tracker_id = program.iteration_tracker_id
            WHERE tracker_artifact.id = ?
        SQL;

        $iteration_tracker_id = $this->getDB()->cell($sql, $iteration->getId());
        if ($iteration_tracker_id === false) {
            throw new IterationTrackerNotFoundException($iteration);
        }

        return $iteration_tracker_id;
    }

    /**
     * @psalm-return null|array{iteration_label: ?string, iteration_sub_label: ?string}
     */
    #[\Override]
    public function getIterationLabels(int $iteration_tracker_id): ?array
    {
        $sql       = 'SELECT iteration_label, iteration_sub_label FROM plugin_program_management_program WHERE iteration_tracker_id = ?';
        $row       = $this->getDB()->row($sql, $iteration_tracker_id);
        $label     = $this->emptyStringToNull($row['iteration_label']);
        $sub_label = $this->emptyStringToNull($row['iteration_sub_label']);
        return ['iteration_label' => $label, 'iteration_sub_label' => $sub_label];
    }

    private function emptyStringToNull(?string $result): ?string
    {
        if ($result === '') {
            return null;
        }
        return $result;
    }
}
