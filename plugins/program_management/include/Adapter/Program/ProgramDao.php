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
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\ProgramManagement\Domain\Program\SearchProgram;

final class ProgramDao extends DataAccessObject implements ProgramStore, SearchProgram
{
    public function isProjectAProgramProject(int $project_id): bool
    {
        $sql = "SELECT COUNT(*)
                FROM plugin_program_management_team_projects
                WHERE program_project_id = ?";

        return $this->getDB()->exists($sql, $project_id);
    }

    /**
     * @psalm-return list<array{team_project_id:int}>
     */
    public function getTeamProjectIdsForGivenProgramProject(int $project_id): array
    {
        $sql = "SELECT team_project_id
                FROM plugin_program_management_team_projects
                WHERE program_project_id = ?";

        return $this->getDB()->run($sql, $project_id);
    }

    public function saveProgram(int $program_project_id, int $team_project_id): void
    {
        $sql = ['program_project_id' => $program_project_id, 'team_project_id' => $team_project_id];
        $this->getDB()->insert('plugin_program_management_team_projects', $sql);
    }

    public function searchProgramOfProgramIncrement(int $program_increment_id): ?int
    {
        $sql = 'SELECT program.program_project_id
                FROM plugin_program_management_program AS program
                    INNER JOIN tracker ON tracker.id = program.program_increment_tracker_id
                    INNER JOIN tracker_artifact AS program_increment ON tracker.id = program_increment.tracker_id
                WHERE program_increment.id = ?';

        $result = $this->getDB()->cell($sql, $program_increment_id);
        return ($result !== false) ? (int) $result : null;
    }
}
