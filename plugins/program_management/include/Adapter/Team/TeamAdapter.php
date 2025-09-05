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

use Luracast\Restler\RestException;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\include\CheckUserCanAccessProjectAndIsAdmin;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveFullProject;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\Team\Creation\BuildTeam;
use Tuleap\ProgramManagement\Domain\Team\ProjectIsAProgramException;
use Tuleap\ProgramManagement\Domain\Team\TeamAccessException;
use Tuleap\ProgramManagement\Domain\Team\TeamMustHaveExplicitBacklogEnabledException;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\REST\ProjectAuthorization;

final readonly class TeamAdapter implements BuildTeam
{
    public function __construct(
        private RetrieveFullProject $retrieve_full_project,
        private VerifyIsProgram $program_verifier,
        private ExplicitBacklogDao $explicit_backlog_dao,
        private RetrieveUser $retrieve_user,
        private CheckUserCanAccessProjectAndIsAdmin $url_verification,
    ) {
    }

    #[\Override]
    public function checkProjectIsATeam(int $team_id, UserIdentifier $user_identifier): void
    {
        $user    = $this->retrieve_user->getUserWithId($user_identifier);
        $project = $this->retrieve_full_project->getProject($team_id);
        try {
            ProjectAuthorization::userCanAccessProjectAndIsProjectAdmin($user, $project, $this->url_verification);
        } catch (RestException $exception) {
            throw new TeamAccessException($team_id);
        }

        $this->checkProject($project);
    }

    #[\Override]
    public function checkProjectIsATeamForRestTestInitialization(int $team_id): void
    {
        $project = $this->retrieve_full_project->getProject($team_id);

        $this->checkProject($project);
    }

    /**
     * @throws ProjectIsAProgramException
     * @throws TeamMustHaveExplicitBacklogEnabledException
     */
    private function checkProject(\Project $project): void
    {
        if ($this->program_verifier->isAProgram((int) $project->getId())) {
            throw new ProjectIsAProgramException((int) $project->getId());
        }

        if (! $this->explicit_backlog_dao->isProjectUsingExplicitBacklog((int) $project->getId())) {
            throw new TeamMustHaveExplicitBacklogEnabledException(ProjectProxy::buildFromProject($project));
        }
    }
}
