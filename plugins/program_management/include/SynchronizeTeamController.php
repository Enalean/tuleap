<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\ProgramManagement;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DispatchMirroredTimeboxesSynchronization;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StorePendingTeamSynchronization;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\TeamSynchronizationCommand;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Team\SearchVisibleTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class SynchronizeTeamController implements DispatchableWithRequest, DispatchableWithProject
{
    public function __construct(
        private \ProjectManager $project_manager,
        private DispatchMirroredTimeboxesSynchronization $synchronization_dispatcher,
        private SearchVisibleTeamsOfProgram $teams_searcher,
        private BuildProgram $build_program,
        private StorePendingTeamSynchronization $store_pending_team_synchronization,
    ) {
    }

    /**
     * @throws NotFoundException
     */
    #[\Override]
    public function getProject(array $variables): \Project
    {
        $project = $this->project_manager->getProjectByUnixName($variables['project_name']);
        if (! $project) {
            throw new NotFoundException();
        }

        return $project;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);
        if (! $project->usesService(ProgramService::SERVICE_SHORTNAME)) {
            throw new NotFoundException(
                dgettext('tuleap-program_management', 'Program management service is disabled.')
            );
        }

        $user = UserProxy::buildFromPFUser($request->getCurrentUser());
        try {
            $program = ProgramIdentifier::fromId(
                $this->build_program,
                (int) $project->getID(),
                $user
            );

            $team = TeamIdentifier::buildTeamOfProgramById(
                $this->teams_searcher,
                $program,
                $user,
                $variables['team_id']
            );
        } catch (
            Domain\Program\Plan\ProgramAccessException |
            Domain\Program\Plan\ProjectIsNotAProgramException |
            Domain\Team\TeamIsNotVisibleException $e
        ) {
            throw new ForbiddenException($e->getI18NExceptionMessage());
        } catch (Domain\Team\TeamIsNotAggregatedByProgramException $e) {
            throw new NotFoundException($e->getI18NExceptionMessage());
        }

        \Tuleap\Project\ServiceInstrumentation::increment('program_management');

        $this->store_pending_team_synchronization->storePendingTeamSynchronization($program, $team);
        $this->synchronization_dispatcher->dispatchSynchronizationCommand(
            TeamSynchronizationCommand::fromProgramAndTeam($program, $team, $user)
        );
    }
}
