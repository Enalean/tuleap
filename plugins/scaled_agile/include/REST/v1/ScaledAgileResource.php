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
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\HierarchyAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\HierarchyException;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\HierarchySaver;
use Tuleap\ScaledAgile\Adapter\Program\Plan\PlanDao;
use Tuleap\ScaledAgile\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ScaledAgile\Adapter\Program\Plan\ProgramAccessException;
use Tuleap\ScaledAgile\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Adapter\Program\ProgramDao;
use Tuleap\ScaledAgile\Adapter\Program\Tracker\ProgramTrackerAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ScaledAgile\Adapter\Team\TeamDao;
use Tuleap\ScaledAgile\Adapter\Team\TeamTrackerAdapter;
use Tuleap\ScaledAgile\Program\Hierarchy\HierarchyCreator;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;

final class ScaledAgileResource extends AuthenticatedResource
{
    public const ROUTE = 'scaled_agile';

    /**
     * @var HierarchyCreator
     */
    private $hierarchy_creator;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct()
    {
        $this->user_manager      = \UserManager::instance();
        $tracker_factory         = \TrackerFactory::instance();
        $this->hierarchy_creator = new HierarchyCreator(
            new ProgramAdapter(
                \ProjectManager::instance(),
                new ProgramDao(),
                new ExplicitBacklogDao()
            ),
            new HierarchyAdapter(
                new PlanningAdapter(\PlanningFactory::build()),
                new TeamTrackerAdapter($tracker_factory, new TeamDao()),
                new ProgramTrackerAdapter($tracker_factory, new PlanDao()),
            ),
            new HierarchySaver(new HierarchyDAO())
        );
    }

    /**
     * @url OPTIONS {id}/hierarchy
     *
     * @param int $id Id of the program project
     */
    public function options(int $id): void
    {
        Header::allowOptionsPut();
    }

    /**
     * Define Scaled agile program hierarchy
     *
     * Define the hierarchy between a tracker of program and a tracker of its team
     *
     * @url    PUT {id}/hierarchy
     *
     * @param int                                  $id Id of the program project
     * @param ProjectResourcePutHierarchyRepresentation $representation {@from body}
     *
     *
     * @throws RestException 404
     * @throws RestException 400
     */
    protected function putPlan(int $id, ProjectResourcePutHierarchyRepresentation $representation): void
    {
        $user = $this->user_manager->getCurrentUser();
        try {
            $this->hierarchy_creator->create(
                $user,
                $id,
                $representation->program_tracker_id,
                $representation->team_tracker_id
            );
        } catch (ProgramAccessException $e) {
            throw new RestException(404, $e->getMessage());
        } catch (PlanTrackerException | HierarchyException | TopPlanningNotFoundInProjectException | ProgramTrackerException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }
}
