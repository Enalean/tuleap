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

namespace Tuleap\ProgramManagement\REST\v1;

use Luracast\Restler\RestException;
use PermissionsOverrider_PermissionsOverriderManager;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\ProgramManagement\Adapter\Program\Hierarchy\HierarchyAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Hierarchy\HierarchyException;
use Tuleap\ProgramManagement\Adapter\Program\Hierarchy\HierarchySaver;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanDao;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ProgramManagement\Adapter\Team\TeamDao;
use Tuleap\ProgramManagement\Adapter\Team\TeamTrackerAdapter;
use Tuleap\ProgramManagement\Program\Hierarchy\HierarchyCreator;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;

final class ProgramManagementResource extends AuthenticatedResource
{
    public const ROUTE = 'program';

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
                new ProjectAccessChecker(
                    PermissionsOverrider_PermissionsOverriderManager::instance(),
                    new RestrictedUserCanAccessProjectVerifier(),
                    \EventManager::instance()
                ),
                new ProgramDao()
            ),
            new HierarchyAdapter(
                new TeamTrackerAdapter($tracker_factory, new TeamDao(), new PlanningAdapter(\PlanningFactory::build())),
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
     * Define a program hierarchy
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
                $representation->team_tracker_ids
            );
        } catch (ProgramAccessException $e) {
            throw new RestException(404, $e->getMessage());
        } catch (PlanTrackerException | HierarchyException | ProgramTrackerException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }
}
