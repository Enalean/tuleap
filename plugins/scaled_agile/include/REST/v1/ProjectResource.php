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

namespace Tuleap\ScaledAgile\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\ScaledAgile\Adapter\Plan\PlanDao;
use Tuleap\ScaledAgile\Adapter\Plan\PlannableTrackerCannotBeEmptyException;
use Tuleap\ScaledAgile\Adapter\Plan\PlanTrackerDoesNotBelongToProjectException;
use Tuleap\ScaledAgile\Adapter\Plan\PlanTrackerNotFoundException;
use Tuleap\ScaledAgile\Adapter\Plan\ProgramTrackerAdapter;
use Tuleap\ScaledAgile\Adapter\Program\ProgramAccessException;
use Tuleap\ScaledAgile\Adapter\Program\ProgramAdapter;
use Tuleap\ScaledAgile\Adapter\Program\ProjectIsNotAProgramException;
use Tuleap\ScaledAgile\Adapter\Team\ProjectIsAProgramException;
use Tuleap\ScaledAgile\Adapter\Team\TeamAccessException;
use Tuleap\ScaledAgile\Adapter\Team\TeamAdapter;
use Tuleap\ScaledAgile\Adapter\Team\TeamDao;
use Tuleap\ScaledAgile\Program\Plan\CannotPlanIntoItselfException;
use Tuleap\ScaledAgile\Program\Plan\CreatePlan;
use Tuleap\ScaledAgile\Program\Plan\PlanCreator;
use Tuleap\ScaledAgile\Program\ProgramDao;
use Tuleap\ScaledAgile\Team\Creation\TeamCreator;

final class ProjectResource extends AuthenticatedResource
{
    /**
     * @var TeamCreator
     */
    private $team_creator;
    /**
     * @var CreatePlan
     */
    private $plan_creator;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct()
    {
        $this->user_manager = \UserManager::instance();
        $plan_dao           = new PlanDao();
        $tracker_adapter    = new ProgramTrackerAdapter(\TrackerFactory::instance());
        $project_manager    = \ProjectManager::instance();
        $program_dao        = new ProgramDao();
        $project_adapter    = new ProgramAdapter($project_manager, $program_dao);
        $this->plan_creator = new PlanCreator($project_adapter, $tracker_adapter, $plan_dao);

        $team_adapter       = new TeamAdapter($project_manager, $program_dao);
        $team_dao           = new TeamDao();
        $this->team_creator = new TeamCreator($project_adapter, $team_adapter, $team_dao);
    }

    /**
     * @url OPTIONS {id}/scaled_agile_plan
     *
     * @param int $id Id of the project
     */
    public function options(int $id): void
    {
        Header::allowOptionsPut();
    }

    /**
     * Define Scaled agile program plan
     *
     * Define the program increment and the tracker plannable inside
     *
     * @url    PUT {id}/scaled_agile_plan
     *
     * @param int                                  $id Id of the program project
     * @param ProjectResourcePutPlanRepresentation $representation {@from body}
     *
     *
     * @throws RestException 404
     * @throws RestException 400
     */
    protected function putPlan(int $id, ProjectResourcePutPlanRepresentation $representation): void
    {
        $user = $this->user_manager->getCurrentUser();
        try {
            $this->plan_creator->create(
                $user,
                $id,
                $representation->program_increment_tracker_id,
                $representation->plannable_tracker_ids
            );
        } catch (PlanTrackerDoesNotBelongToProjectException | ProjectIsNotAProgramException | PlannableTrackerCannotBeEmptyException | PlanTrackerNotFoundException | CannotPlanIntoItselfException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (ProgramAccessException $e) {
            throw new RestException(404, $e->getMessage());
        }
    }

    /**
     * Define Scaled agile team projects
     *
     * Define the team project of a program
     *
     * @url    PUT {id}/scaled_agile_teams
     *
     * @param int                                   $id Id of the program project
     * @param ProjectResourcePutTeamsRepresentation $representation {@from body}
     *
     *
     * @throws RestException 404
     * @throws RestException 400
     */
    protected function putTeam(int $id, ProjectResourcePutTeamsRepresentation $representation): void
    {
        $user = $this->user_manager->getCurrentUser();
        try {
            $this->team_creator->create(
                $user,
                $id,
                $representation->team_ids
            );
        } catch (ProjectIsNotAProgramException | TeamAccessException | ProjectIsAProgramException | ProjectIsNotAProgramException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (ProgramAccessException $e) {
            throw new RestException(404, $e->getMessage());
        }
    }
}
