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

namespace Tuleap\ScaledAgile\Adapter\Program;

use Tuleap\DB\DataAccessObject;
use Tuleap\ScaledAgile\Program\ProgramStore;

class ProgramDao extends DataAccessObject implements ProgramStore
{
    public function isProjectAProgramProject(int $project_id): bool
    {
        $sql = "SELECT COUNT(*)
                FROM plugin_scaled_agile_team_projects
                WHERE program_project_id = ?";

        return $this->getDB()->exists($sql, $project_id);
    }

    /**
     * @psalm-return list<array{team_project_id:int}>
     */
    public function getTeamProjectIdsForGivenProgramProject(int $project_id): array
    {
        $sql = "SELECT team_project_id
                FROM plugin_scaled_agile_team_projects
                WHERE program_project_id = ?";

        return $this->getDB()->run($sql, $project_id);
    }

    public function saveProgram(int $program_project_id, int $team_project_id): void
    {
        $sql = ['program_project_id' => $program_project_id, 'team_project_id' => $team_project_id];
        $this->getDB()->insert('plugin_scaled_agile_team_projects', $sql);
    }
}
