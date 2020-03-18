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
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;
use UserManager;
use AgileDashboard_Milestone_MilestoneStatusCounter;
use AgileDashboard_BacklogItemDao;
use Tracker_ArtifactDao;
use URLVerification;
use PlanningPermissionsManager;
use AgileDashboard_Milestone_MilestoneDao;

class PlanningResource extends AuthenticatedResource
{

    public const MAX_LIMIT = 100;

    private $milestone_factory;


    public function __construct()
    {
        $artifact_factory        = \Tracker_ArtifactFactory::instance();
        $status_counter          = new AgileDashboard_Milestone_MilestoneStatusCounter(
            new AgileDashboard_BacklogItemDao(),
            new Tracker_ArtifactDao(),
            $artifact_factory
        );
        $planning_factory        = PlanningFactory::build();
        $form_element_factory    = Tracker_FormElementFactory::instance();
        $this->milestone_factory = new \Planning_MilestoneFactory(
            $planning_factory,
            $artifact_factory,
            \Tracker_FormElementFactory::instance(),
            $status_counter,
            new PlanningPermissionsManager(),
            new AgileDashboard_Milestone_MilestoneDao(),
            new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), $planning_factory),
            new TimeframeBuilder(
                new SemanticTimeframeBuilder(new SemanticTimeframeDao(), $form_element_factory),
                \BackendLogger::getDefaultLogger()
            ),
            new MilestoneBurndownFieldChecker($form_element_factory)
        );
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
     * @param int $limit Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getMilestones($id, $limit = 10, $offset = 0)
    {
        $this->checkAccess();
        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }

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
     * @param int $id
     *
     * @return Planning
     * @throws RestException 403
     * @throws RestException 404
     */
    private function getPlanning($id)
    {
        $planning = PlanningFactory::build()->getPlanning($id);
        $user     = $this->getCurrentUser();

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

    private function limitValueIsAcceptable($limit)
    {
        return $limit <= self::MAX_LIMIT;
    }

    private function getCurrentUser()
    {
        return UserManager::instance()->getCurrentUser();
    }

    private function getMilestonesByPlanning(Planning $planning, $limit, $offset)
    {
        $all_milestones = array();
        $milestones = $this->milestone_factory->getAllBareMilestones($this->getCurrentUser(), $planning);
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
