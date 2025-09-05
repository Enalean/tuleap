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

namespace Tuleap\ProgramManagement\Adapter\Team;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamCollection;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamStore;
use Tuleap\ProgramManagement\Domain\Team\SearchProgramsOfTeam;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeamOfProgram;
use Tuleap\ProgramManagement\ProgramService;

final class TeamDao extends DataAccessObject implements TeamStore, VerifyIsTeam, VerifyIsTeamOfProgram, SearchProgramsOfTeam
{
    /**
     * @throws \Throwable
     */
    #[\Override]
    public function save(TeamCollection $team_collection): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($team_collection): void {
            $sql = 'DELETE FROM plugin_program_management_team_projects WHERE program_project_id = ?';

            $program_id = $team_collection->getProgram()->id;
            $this->getDB()->run($sql, $program_id);

            if (count($team_collection->getTeamIds()) === 0) {
                return;
            }

            $insert = [];
            foreach ($team_collection->getTeamIds() as $plannable_tracker_id) {
                $insert[] = ['program_project_id' => $program_id, 'team_project_id' => $plannable_tracker_id];
            }

            $this->getDB()->insertMany('plugin_program_management_team_projects', $insert);

            $in_statement = EasyStatement::open()->in('group_id IN (?*)', $team_collection->getTeamIds());
            $sql          = "UPDATE service SET is_used = 0 WHERE short_name= ? AND $in_statement";

            $parameters = array_merge([ProgramService::SERVICE_SHORTNAME], $in_statement->values());
            $this->getDB()->safeQuery($sql, $parameters);
        });
    }

    #[\Override]
    public function isATeam(int $project_id): bool
    {
        $sql = 'SELECT * FROM plugin_program_management_team_projects WHERE team_project_id = ?';

        return $this->getDB()->exists($sql, $project_id);
    }

    #[\Override]
    public function isATeamFromProgram(int $program_id, int $team_id): bool
    {
        $sql = 'SELECT * FROM plugin_program_management_team_projects WHERE program_project_id = ? AND team_project_id = ?';

        return $this->getDB()->exists($sql, $program_id, $team_id);
    }

    /**
     * @return int[]
     */
    #[\Override]
    public function searchProgramIdsOfTeam(int $project_id): array
    {
        $sql = 'SELECT program_project_id
                FROM plugin_program_management_team_projects
                WHERE team_project_id = ?';

        $rows = $this->getDB()->q($sql, $project_id);
        return array_map(static fn(array $row): int => $row['program_project_id'], $rows);
    }
}
