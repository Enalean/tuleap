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
use Tuleap\ScaledAgile\Adapter\Plan\ProgramAccessException;
use Tuleap\ScaledAgile\Adapter\Plan\ProgramAdapter;
use Tuleap\ScaledAgile\Adapter\Plan\ProgramTrackerAdapter;
use Tuleap\ScaledAgile\Adapter\Plan\ProjectIsNotAProgramException;
use Tuleap\ScaledAgile\Program\Plan\CannotPlanIntoItselfException;
use Tuleap\ScaledAgile\Program\Plan\CreatePlan;
use Tuleap\ScaledAgile\Program\Plan\PlanCreator;
use Tuleap\ScaledAgile\Program\ProgramDao;

final class ProjectResource extends AuthenticatedResource
{
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
        $project_adapter    = new ProgramAdapter(\ProjectManager::instance(), new ProgramDao());
        $this->plan_creator = new PlanCreator($project_adapter, $tracker_adapter, $plan_dao);
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
     * @access hybrid
     *
     * @param int $id Id of the project
     * @param ProjectResourcePutRepresentation $representation {@from body}
     *
     *
     * @throws RestException 404
     * @throws RestException 400
     */
    public function putPlan(int $id, ProjectResourcePutRepresentation $representation): void
    {
        $user = $this->user_manager->getCurrentUser();
        if (! $user) {
            throw new RestException(404, "User not found.");
        }
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
}
