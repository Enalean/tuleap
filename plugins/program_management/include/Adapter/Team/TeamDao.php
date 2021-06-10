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

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamCollection;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamStore;

final class TeamDao extends DataAccessObject implements TeamStore
{
    /**
     * @throws \Throwable
     */
    public function save(TeamCollection $team_collection): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($team_collection): void {
            $sql = 'DELETE FROM plugin_program_management_team_projects WHERE program_project_id = ?';

            $program_id = $team_collection->getProgram()->getId();
            $this->getDB()->run($sql, $program_id);

            $insert = [];
            foreach ($team_collection->getTeamIds() as $plannable_tracker_id) {
                $insert[] = ['program_project_id' => $program_id, 'team_project_id' => $plannable_tracker_id];
            }

            $this->getDB()->insertMany('plugin_program_management_team_projects', $insert);
        });
    }

    public function isATeam(int $team_project_id): bool
    {
        $sql = 'SELECT * FROM plugin_program_management_team_projects WHERE team_project_id = ?';

        return $this->getDB()->exists($sql, $team_project_id);
    }
}
