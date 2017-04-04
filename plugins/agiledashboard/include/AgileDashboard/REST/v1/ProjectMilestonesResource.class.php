<?php
/**
 * Copyright (c) Enalean, 2013 – 2017. All Rights Reserved.
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

use \Tracker_ArtifactFactory;
use \Tracker_ArtifactDao;
use \Tracker_FormElementFactory;
use \TrackerFactory;
use \PlanningFactory;
use \Planning_MilestoneFactory;
use \PFUser;
use \Project;
use \Luracast\Restler\RestException;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use \Tuleap\REST\Header;
use \AgileDashboard_Milestone_MilestoneStatusCounter;
use \AgileDashboard_BacklogItemDao;
use \Planning_Milestone;
use \AgileDashboard_Milestone_Backlog_BacklogStrategyFactory;
use \PlanningPermissionsManager;
use AgileDashboard_Milestone_MilestoneRepresentationBuilder;
use EventManager;
use AgileDashboard_Milestone_MilestoneDao;
use Tuleap\AgileDashboard\REST\QueryToCriterionConverter;
use Tuleap\AgileDashboard\REST\MalformedQueryParameterException;

/**
 * Wrapper for milestone related REST methods
 */
class ProjectMilestonesResource {
    const MAX_LIMIT = 50;

    /** @var Tracker_FormElementFactory */
    private $tracker_form_element_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var AgileDashboard_Milestone_MilestoneStatusCounter */
    private $status_counter;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var AgileDashboard_Milestone_MilestoneRepresentationBuilder */
    private $milestone_representation_builder;

    /** @var QueryToCriterionConverter */
    private $query_to_criterion_converter;

    public function __construct() {
        $this->tracker_form_element_factory = Tracker_FormElementFactory::instance();
        $this->planning_factory             = PlanningFactory::build();
        $this->tracker_artifact_factory     = Tracker_ArtifactFactory::instance();
        $this->tracker_factory              = TrackerFactory::instance();
        $this->status_counter               = new AgileDashboard_Milestone_MilestoneStatusCounter(
            new AgileDashboard_BacklogItemDao(),
            new Tracker_ArtifactDao(),
            $this->tracker_artifact_factory
        );

        $scrum_mono_milestone_checker = new ScrumForMonoMilestoneChecker(
            new ScrumForMonoMilestoneDao(),
            $this->planning_factory
        );

        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
            $this->tracker_artifact_factory,
            $this->tracker_form_element_factory,
            $this->tracker_factory,
            $this->status_counter,
            new PlanningPermissionsManager(),
            new AgileDashboard_Milestone_MilestoneDao(),
            new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), $this->planning_factory)
        );

        $backlog_strategy_factory = new AgileDashboard_Milestone_Backlog_BacklogStrategyFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->tracker_artifact_factory,
            $this->planning_factory,
            $scrum_mono_milestone_checker
        );

        $this->milestone_representation_builder = new AgileDashboard_Milestone_MilestoneRepresentationBuilder(
            $this->milestone_factory,
            $backlog_strategy_factory,
            EventManager::instance(),
            $scrum_mono_milestone_checker
        );

        $this->query_to_criterion_converter = new QueryToCriterionConverter();
    }

    /**
     * Get the top milestones of a given project
     */
    public function get(PFUser $user, $project, $representation_type, $query, $limit, $offset, $order) {

        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }

        try {
            $criterion = $this->query_to_criterion_converter->convert($query);
        } catch (MalformedQueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $paginated_top_milestones_representations = $this->milestone_representation_builder
            ->getPaginatedTopMilestonesRepresentations($project, $user, $representation_type, $criterion, $limit, $offset, $order);

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, $paginated_top_milestones_representations->getTotalSize());

        return $paginated_top_milestones_representations->getMilestonesRepresentations();
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    public function options(PFUser $user, Project $project, $limit, $offset) {
        $this->sendAllowHeaders();
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeaders() {
        Header::allowOptionsGet();
    }
}
?>
