<?php
/**
 * Copyright (c) Enalean, 2013 – Present. All Rights Reserved.
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

use Tracker_ArtifactFactory;
use Tracker_ArtifactDao;
use Tracker_FormElementFactory;
use PlanningFactory;
use Planning_MilestoneFactory;
use PFUser;
use Project;
use Luracast\Restler\RestException;
use Tuleap\AgileDashboard\Milestone\CurrentMilestoneRepresentationBuilder;
use Tuleap\AgileDashboard\Milestone\ParentTrackerRetriever;
use Tuleap\AgileDashboard\Milestone\FutureMilestoneRepresentationBuilder;
use Tuleap\AgileDashboard\MonoMilestone\MonoMilestoneBacklogItemDao;
use Tuleap\AgileDashboard\MonoMilestone\MonoMilestoneItemsFinder;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker;
use Tuleap\AgileDashboard\REST\MalformedQueryParameterException;
use Tuleap\AgileDashboard\REST\QueryToMilestoneRepresentationBuilderConverter;
use Tuleap\REST\Header;
use AgileDashboard_Milestone_MilestoneStatusCounter;
use AgileDashboard_BacklogItemDao;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use PlanningPermissionsManager;
use AgileDashboard_Milestone_MilestoneRepresentationBuilder;
use EventManager;
use AgileDashboard_Milestone_MilestoneDao;
use Tuleap\AgileDashboard\REST\QueryToCriterionStatusConverter;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;
use Tuleap\AgileDashboard\REST\QueryToPeriodMilestoneRepresentationBuilderConverter;

/**
 * Wrapper for milestone related REST methods
 */
class ProjectMilestonesResource
{
    public const MAX_LIMIT = 50;

    /** @var Tracker_FormElementFactory */
    private $tracker_form_element_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var AgileDashboard_Milestone_MilestoneStatusCounter */
    private $status_counter;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var QueryToMilestoneRepresentationBuilderConverter */
    private $query_to_milestone_representation_builder_converter;

    public function __construct()
    {
        $this->tracker_form_element_factory = Tracker_FormElementFactory::instance();
        $this->planning_factory             = PlanningFactory::build();
        $this->tracker_artifact_factory     = Tracker_ArtifactFactory::instance();
        $this->status_counter               = new AgileDashboard_Milestone_MilestoneStatusCounter(
            new AgileDashboard_BacklogItemDao(),
            new Tracker_ArtifactDao(),
            $this->tracker_artifact_factory
        );

        $scrum_mono_milestone_checker = new ScrumForMonoMilestoneChecker(
            new ScrumForMonoMilestoneDao(),
            $this->planning_factory
        );

        $mono_milestone_items_finder = new MonoMilestoneItemsFinder(
            new MonoMilestoneBacklogItemDao(),
            $this->tracker_artifact_factory
        );

        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
            $this->tracker_artifact_factory,
            $this->tracker_form_element_factory,
            $this->status_counter,
            new PlanningPermissionsManager(),
            new AgileDashboard_Milestone_MilestoneDao(),
            new ScrumForMonoMilestoneChecker(new ScrumForMonoMilestoneDao(), $this->planning_factory),
            new TimeframeBuilder(
                new SemanticTimeframeBuilder(new SemanticTimeframeDao(), $this->tracker_form_element_factory),
                \BackendLogger::getDefaultLogger()
            ),
            new MilestoneBurndownFieldChecker($this->tracker_form_element_factory)
        );

        $backlog_factory = new AgileDashboard_Milestone_Backlog_BacklogFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->tracker_artifact_factory,
            $this->planning_factory,
            $scrum_mono_milestone_checker,
            $mono_milestone_items_finder
        );

        $parent_tracker_retriever = new ParentTrackerRetriever($this->planning_factory);

        $milestone_representation_builder = new AgileDashboard_Milestone_MilestoneRepresentationBuilder(
            $this->milestone_factory,
            $backlog_factory,
            EventManager::instance(),
            $scrum_mono_milestone_checker,
            $parent_tracker_retriever
        );

        $future_milestone_representation_builder = new FutureMilestoneRepresentationBuilder(
            $milestone_representation_builder,
            $this->milestone_factory
        );

        $current_milestone_representation_builder = new CurrentMilestoneRepresentationBuilder(
            $milestone_representation_builder,
            $this->milestone_factory
        );

        $this->query_to_milestone_representation_builder_converter = new QueryToMilestoneRepresentationBuilderConverter(
            $milestone_representation_builder,
            new QueryToPeriodMilestoneRepresentationBuilderConverter(
                $future_milestone_representation_builder,
                $current_milestone_representation_builder
            ),
            new QueryToCriterionStatusConverter()
        );
    }

    /**
     * Get the top milestones of a given project
     * @throws RestException
     */
    public function get(PFUser $user, $project, $representation_type, $query, $limit, $offset, $order)
    {
        if (! $this->limitValueIsAcceptable($limit)) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }

        try {
            $builder = $this->query_to_milestone_representation_builder_converter->convert($query);
        } catch (MalformedQueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
        $this->sendAllowHeaders();

        try {
            $paginated_top_milestones_representations = $builder
                ->getPaginatedTopMilestonesRepresentations($project, $user, $representation_type, $limit, $offset, $order);
            $this->sendPaginationHeaders($limit, $offset, $paginated_top_milestones_representations->getTotalSize());
            return $paginated_top_milestones_representations->getMilestonesRepresentations();
        } catch (\Planning_NoPlanningsException $e) {
            $this->sendPaginationHeaders($limit, $offset, 0);
            return [];
        }
    }

    private function limitValueIsAcceptable($limit)
    {
        return $limit <= self::MAX_LIMIT;
    }

    public function options(PFUser $user, Project $project, $limit, $offset)
    {
        $this->sendAllowHeaders();
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGet();
    }
}
