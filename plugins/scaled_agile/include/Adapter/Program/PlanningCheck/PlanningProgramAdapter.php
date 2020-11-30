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

namespace Tuleap\ScaledAgile\Adapter\Program\PlanningCheck;

use Project_AccessDeletedException;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Project_AccessRestrictedException;
use Tuleap\Project\ProjectAccessSuspendedException;
use Tuleap\ScaledAgile\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ScaledAgile\Adapter\Program\Tracker\ProgramTrackerNotFoundException;
use Tuleap\ScaledAgile\Program\Backlog\PlanningCheck\BuildPlanningConfiguration;
use Tuleap\ScaledAgile\Program\Plan\PlanStore;
use Tuleap\ScaledAgile\Program\Plan\ProgramIncrementTracker;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\ScaledAgile\Program\ProgramStore;
use Tuleap\ScaledAgile\Team\Creation\TeamStore;

final class PlanningProgramAdapter implements BuildPlanningConfiguration
{
    /**
     * @var PlanStore
     */
    private $plan_store;
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;
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
        \TrackerFactory $tracker_factory,
        \ProjectManager $project_manager,
        \URLVerification $url_verification,
        PlanStore $plan_store,
        ProgramStore $program_store,
        TeamStore $team_store
    ) {
        $this->plan_store         = $plan_store;
        $this->tracker_factory    = $tracker_factory;
        $this->project_manager    = $project_manager;
        $this->program_store      = $program_store;
        $this->url_verification   = $url_verification;
        $this->team_store         = $team_store;
    }

    /**
     * @throws ConfigurationUserCanNotSeeProgramException
     * @throws ProgramNotFoundException
     * @throws ProgramTrackerNotFoundException
     */
    public function buildProgramIncrementFromProjectId(int $project_id, \PFUser $user): ProgramIncrementTracker
    {
        $program_increment_tracker_id = $this->plan_store->getProgramIncrementTrackerId($project_id);
        if (! $program_increment_tracker_id) {
            throw new ProgramTrackerNotFoundException($project_id);
        }
        $program_increment_tracker    = $this->getValidTracker(
            $program_increment_tracker_id
        );

        if (! $program_increment_tracker->userCanView($user)) {
            throw new ConfigurationUserCanNotSeeProgramException(
                (int) $user->getId(),
                (int) $program_increment_tracker->getId()
            );
        }

        return new ProgramIncrementTracker($program_increment_tracker->getId());
    }

    /**
     * @throws ProgramNotFoundException
     */
    private function getValidTracker(int $tracker_id): \Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);

        if (! $tracker) {
            throw new ProgramNotFoundException($tracker_id);
        }

        return $tracker;
    }

    /**
     * @throws ProjectIsNotAProgramException
     * @throws UserCanNotAccessToProgramException
     */
    public function buildProgramFromTeamProject(\Project $project, \PFUser $user): ?Program
    {
        $team = $this->project_manager->getProject($project->getID());

        $program_increment_id = $this->team_store->getProgramIncrementOfTeam((int) $team->getID());

        if (! $program_increment_id) {
            return null;
        }

        $program              = $this->project_manager->getProject($program_increment_id);

        if (! $this->program_store->isProjectAProgramProject((int) $program->getId())) {
            throw new ProjectIsNotAProgramException((int) $program->getId());
        }

        try {
            $this->url_verification->userCanAccessProject($user, $project);
        } catch (Project_AccessProjectNotFoundException | Project_AccessDeletedException | Project_AccessRestrictedException | Project_AccessPrivateException | ProjectAccessSuspendedException $e) {
            throw new UserCanNotAccessToProgramException($user->getId(), $program->getID(), $e->getMessage());
        }

        return new Program((int) $program->getID());
    }
}
