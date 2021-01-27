<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Adapter\Program\Plan;

use Project_AccessDeletedException;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Project_AccessRestrictedException;
use Tuleap\Project\ProjectAccessSuspendedException;
use Tuleap\ScaledAgile\Program\Backlog\Plan\BuildPlanProgramConfiguration;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\ScaledAgile\Program\ProgramStore;
use Tuleap\ScaledAgile\Team\Creation\TeamStore;

final class PlanProgramAdapter implements BuildPlanProgramConfiguration
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var ProgramStore
     */
    private $program_store;
    /**
     * @var \URLVerification
     */
    private $url_verification;
    /**
     * @var TeamStore
     */
    private $team_store;

    public function __construct(
        \ProjectManager $project_manager,
        \URLVerification $url_verification,
        TeamStore $team_store
    ) {
        $this->project_manager  = $project_manager;
        $this->url_verification = $url_verification;
        $this->team_store       = $team_store;
    }

    /**
     * @throws PlanTrackerException
     */
    public function buildProgramTrackerFromTeamProject(\Project $project, \PFUser $user): ?Program
    {
        $team = $this->project_manager->getProject($project->getID());

        $program_increment_id = $this->team_store->getProgramIncrementOfTeam((int) $team->getID());

        if (! $program_increment_id) {
            return null;
        }

        $program = $this->project_manager->getProject($program_increment_id);

        try {
            $this->url_verification->userCanAccessProject($user, $project);
        } catch (Project_AccessProjectNotFoundException | Project_AccessDeletedException | Project_AccessRestrictedException | Project_AccessPrivateException | ProjectAccessSuspendedException $e) {
            throw new UserCanNotAccessToProgramException((int) $program->getID(), (int) $user->getId(), $e->getMessage());
        }

        return new Program((int) $program->getID());
    }
}
