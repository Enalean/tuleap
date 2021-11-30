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

namespace Tuleap\ProgramManagement\Domain\Team\Creation;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramCannotBeATeamException;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIsTeamException;
use Tuleap\ProgramManagement\Domain\Team\ProjectIsAProgramException;
use Tuleap\ProgramManagement\Domain\Team\TeamAccessException;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveProject;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyProjectPermission;

final class TeamCreator implements CreateTeam
{
    public function __construct(
        private RetrieveProject $project_retriever,
        private VerifyIsTeam $team_verifier,
        private VerifyProjectPermission $permission_verifier,
        private BuildTeam $team_builder,
        private TeamStore $team_store,
    ) {
    }

    /**
     * @throws ProgramAccessException
     * @throws ProjectIsAProgramException
     * @throws TeamAccessException
     * @throws ProgramIsTeamException
     * @throws ProgramCannotBeATeamException
     */
    public function create(UserReference $user, int $project_id, array $team_ids): void
    {
        if (in_array($project_id, $team_ids, true)) {
            throw new ProgramIsTeamException($project_id);
        }
        $project         = $this->project_retriever->getProjectWithId($project_id);
        $program         = ProgramForAdministrationIdentifier::fromProject(
            $this->team_verifier,
            $this->permission_verifier,
            $user,
            $project
        );
        $teams           = array_map(
            fn(int $team_id): Team => Team::build($this->team_builder, $team_id, $user),
            $team_ids
        );
        $team_collection = TeamCollection::fromProgramAndTeams($program, ...$teams);

        $this->team_store->save($team_collection);
    }
}
