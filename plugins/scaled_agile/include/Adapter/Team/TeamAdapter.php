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

namespace Tuleap\ScaledAgile\Adapter\Team;

use Luracast\Restler\RestException;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\ScaledAgile\Program\ProgramStore;
use Tuleap\ScaledAgile\Program\ToBeCreatedProgram;
use Tuleap\ScaledAgile\Team\Creation\BuildTeam;
use Tuleap\ScaledAgile\Team\Creation\Team;
use Tuleap\ScaledAgile\Team\Creation\TeamCollection;

final class TeamAdapter implements BuildTeam
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var ProgramStore
     */
    private $program_dao;

    public function __construct(\ProjectManager $project_manager, ProgramStore $program_store)
    {
        $this->project_manager = $project_manager;
        $this->program_dao     = $program_store;
    }

    /**
     * @throws AtLeastOneTeamShouldBeDefinedException
     * @throws ProjectIsAProgramException
     * @throws TeamAccessException
     */
    public function buildTeamProject(array $team_ids, ToBeCreatedProgram $program, \PFUser $user): TeamCollection
    {
        $team_list = [];
        foreach ($team_ids as $team_id) {
            $project = $this->project_manager->getProject($team_id);
            try {
                ProjectAuthorization::userCanAccessProjectAndIsProjectAdmin($user, $project);
            } catch (RestException $exception) {
                throw new TeamAccessException($team_id);
            }

            if ($this->program_dao->isProjectAProgramProject((int) $project->getId())) {
                throw new ProjectIsAProgramException((int) $project->getId());
            }

            $team_list[] = new Team((int) $project->getID());
        }

        if (empty($team_list)) {
            throw new AtLeastOneTeamShouldBeDefinedException();
        }

        return new TeamCollection($team_list, $program);
    }
}
