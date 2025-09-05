<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\AllProgramSearcher;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationHasNoProgramException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementHasNoProgramException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfIteration;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProjectAProgramOrUsedInPlan;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;

final class ProgramDaoProject extends DataAccessObject implements RetrieveProgramOfProgramIncrement, SearchTeamsOfProgram, VerifyIsProgram, AllProgramSearcher, RetrieveProgramOfIteration, VerifyIsProjectAProgramOrUsedInPlan
{
    #[\Override]
    public function isAProgram(int $project_id): bool
    {
        $sql = 'SELECT COUNT(*)
                FROM plugin_program_management_team_projects
                WHERE program_project_id = ?';

        return $this->getDB()->exists($sql, $project_id);
    }

    #[\Override]
    public function isProjectAProgramOrIsPartOfPlan(int $project_id): bool
    {
        $sql = 'SELECT COUNT(*)
                FROM plugin_program_management_team_projects
                WHERE program_project_id = ?';

        $sql_plan = 'SELECT COUNT(*)
                FROM plugin_program_management_plan
                WHERE project_id = ?';

        return $this->getDB()->exists($sql, $project_id) || $this->getDB()->exists($sql_plan, $project_id);
    }

    /**
     * @return int[]
     */
    #[\Override]
    public function getAllPrograms(): array
    {
        $sql = 'SELECT program_project_id
                FROM plugin_program_management_team_projects';

        $rows = $this->getDB()->q($sql);
        return array_map(static fn(array $row): int => $row['program_project_id'], $rows);
    }

    #[\Override]
    public function searchTeamIdsOfProgram(int $project_id): array
    {
        $sql = 'SELECT team_project_id
                FROM plugin_program_management_team_projects
                WHERE program_project_id = ?';

        $rows = $this->getDB()->q($sql, $project_id);
        return array_map(static fn(array $row): int => $row['team_project_id'], $rows);
    }

    #[\Override]
    public function getProgramOfProgramIncrement(ProgramIncrementIdentifier $program_increment): int
    {
        $sql = 'SELECT program.program_project_id
                FROM plugin_program_management_program AS program
                    INNER JOIN tracker_artifact AS program_increment ON program.program_increment_tracker_id = program_increment.tracker_id
                WHERE program_increment.id = ?';

        $result = $this->getDB()->cell($sql, $program_increment->getId());
        if ($result === false) {
            throw new ProgramIncrementHasNoProgramException($program_increment);
        }
        return (int) $result;
    }

    #[\Override]
    public function getProgramOfIteration(IterationIdentifier $iteration): int
    {
        $sql = <<<SQL
        SELECT program.program_project_id
        FROM plugin_program_management_program AS program
             INNER JOIN tracker_artifact AS iteration ON program.iteration_tracker_id = iteration.tracker_id
        WHERE iteration.id = ?
        SQL;

        $result = $this->getDB()->cell($sql, $iteration->getId());
        if ($result === false) {
            throw new IterationHasNoProgramException($iteration);
        }
        return (int) $result;
    }
}
