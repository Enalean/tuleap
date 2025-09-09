<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
namespace Tuleap\AgileDashboard\REST\v1;

use PlanningFactory;
use Luracast\Restler\RestException;
use Planning;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\ProjectStatusVerificator;
use UserManager;
use URLVerification;

class PlanningResource extends AuthenticatedResource
{
    public const int MAX_LIMIT = 100;

    private $milestone_factory;


    public function __construct()
    {
        $this->milestone_factory = \Planning_MilestoneFactory::build();
    }

    /**
     * Get milestones
     *
     * Get the milestones of a given planning
     *
     * @url GET {id}/milestones
     * @access hybrid
     *
     * @param int $id Id of the planning
     * @param int $limit Number of elements displayed per page {@min 0} {@max 100}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getMilestones($id, $limit = 10, $offset = 0)
    {
        $this->checkAccess();

        return $this->getMilestonesByPlanning($this->getPlanning($id), $limit, $offset);
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        $this->sendAllowHeaders();
    }

    /**
     * @url OPTIONS {id}/milestones
     */
    public function optionsForMilestones($id)
    {
        $this->sendAllowHeadersForMilestones();
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     */
    private function getPlanning(int $id): Planning
    {
        $user     = $this->getCurrentUser();
        $planning = PlanningFactory::build()->getPlanning($user, $id);

        if (! $planning) {
            throw new RestException(404, 'Planning not found');
        }

        $project = $planning->getPlanningTracker()->getProject();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $project
        );

        ProjectAuthorization::userCanAccessProject(
            $user,
            $project,
            new URLVerification()
        );

        return $planning;
    }

    private function getCurrentUser()
    {
        return UserManager::instance()->getCurrentUser();
    }

    private function getMilestonesByPlanning(Planning $planning, $limit, $offset)
    {
        $all_milestones = [];
        $milestones     = $this->milestone_factory->getAllBareMilestones($this->getCurrentUser(), $planning);
        foreach ($milestones as $milestone) {
            $all_milestones[] = new MilestoneInfoRepresentation($milestone);
        }
        $milestones_representations = array_slice($all_milestones, $offset, $limit);
        $this->sendAllowHeadersForMilestones();
        $this->sendPaginationHeaders($limit, $offset, count($all_milestones));
        return $milestones_representations;
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeaders()
    {
        Header::allowOptions();
    }

    private function sendAllowHeadersForMilestones()
    {
        Header::allowOptionsGet();
    }
}
