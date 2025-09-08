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

namespace Tuleap\ProgramManagement\Adapter\Team;

use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveFullProject;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\ProgramHasNoTeamException;
use Tuleap\ProgramManagement\Domain\Team\SearchVisibleTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\TeamIsNotAggregatedByProgramException;
use Tuleap\ProgramManagement\Domain\Team\TeamIsNotVisibleException;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeamOfProgram;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Project\CheckProjectAccess;

final class VisibleTeamSearcher implements SearchVisibleTeamsOfProgram
{
    public function __construct(
        private SearchTeamsOfProgram $teams_searcher,
        private RetrieveUser $user_retriever,
        private RetrieveFullProject $project_retriever,
        private CheckProjectAccess $access_checker,
        private VerifyIsTeamOfProgram $verify_is_team_of_program,
    ) {
    }

    #[\Override]
    public function searchTeamIdsOfProgram(ProgramIdentifier $program, UserIdentifier $user): array
    {
        $team_ids = $this->teams_searcher->searchTeamIdsOfProgram($program->getId());
        if (empty($team_ids)) {
            throw new ProgramHasNoTeamException($program);
        }
        $pfuser = $this->user_retriever->getUserWithId($user);
        foreach ($team_ids as $team_id) {
            $project = $this->project_retriever->getProject($team_id);
            try {
                $this->access_checker->checkUserCanAccessProject($pfuser, $project);
            } catch (\Project_AccessException $e) {
                throw new TeamIsNotVisibleException($program, $user, (string) $project->getPublicName());
            }
        }
        return $team_ids;
    }

    #[\Override]
    public function searchTeamWithIdInProgram(ProgramIdentifier $program, UserIdentifier $user, int $team_id): int
    {
        if (! $this->verify_is_team_of_program->isATeamFromProgram($program->getId(), $team_id)) {
            throw new TeamIsNotAggregatedByProgramException($program->getId(), $team_id);
        }

        $pfuser  = $this->user_retriever->getUserWithId($user);
        $project = $this->project_retriever->getProject($team_id);

        try {
            $this->access_checker->checkUserCanAccessProject($pfuser, $project);
        } catch (\Project_AccessException $e) {
            throw new TeamIsNotVisibleException($program, $user, (string) $project->getPublicName());
        }

        return $team_id;
    }
}
