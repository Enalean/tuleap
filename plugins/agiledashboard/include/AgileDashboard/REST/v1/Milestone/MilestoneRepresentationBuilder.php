<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use Tuleap\AgileDashboard\Milestone\PaginatedMilestones;
use Tuleap\AgileDashboard\Milestone\ParentTrackerRetriever;
use Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;

class MilestoneRepresentationBuilder
{
    /**
     * Allow plugins to modify the milestone requested by a RESt call
     *
     * Parameters:
     * 'user'                                       => The user who requested
     * 'milestone'                                  => The Milestone object
     * 'milestone_representation_reference_holder' => The reference to a RESt representation of the milestone
     */
    public const AGILEDASHBOARD_EVENT_REST_GET_MILESTONE = 'agiledashboard_event_rest_get_milestone';

    /**
     * @var \Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var \AgileDashboard_Milestone_Backlog_BacklogFactory
     */
    private $backlog_factory;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var ParentTrackerRetriever
     */
    private $parent_tracker_retriever;
    /**
     * @var \AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder
     */
    private $sub_milestone_finder;
    /**
     * @var \PlanningFactory
     */
    private $planning_factory;
    /**
     * @var ProjectBackgroundConfiguration
     */
    private $project_background_configuration;

    public function __construct(
        \Planning_MilestoneFactory $milestone_factory,
        \AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        \EventManager $event_manager,
        ParentTrackerRetriever $parent_tracker_retriever,
        \AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder $sub_milestone_finder,
        \PlanningFactory $planning_factory,
        ProjectBackgroundConfiguration $project_background_configuration,
    ) {
        $this->milestone_factory                = $milestone_factory;
        $this->backlog_factory                  = $backlog_factory;
        $this->event_manager                    = $event_manager;
        $this->parent_tracker_retriever         = $parent_tracker_retriever;
        $this->sub_milestone_finder             = $sub_milestone_finder;
        $this->planning_factory                 = $planning_factory;
        $this->project_background_configuration = $project_background_configuration;
    }

    public function getMilestoneRepresentation(
        \Planning_Milestone $milestone,
        \PFUser $user,
        string $representation_type,
    ): MilestoneRepresentation {
        $status_count = [];
        if ($representation_type === MilestoneRepresentation::ALL_FIELDS) {
            $status_count = $this->milestone_factory->getMilestoneStatusCount($user, $milestone);
        }

        $backlog_trackers = $this->getBacklogTrackers($milestone);


        $pane_info_collector = new \Tuleap\AgileDashboard\Milestone\Pane\PaneInfoCollector(
            $milestone,
            null,
            [],
            null,
            $user,
        );
        $this->event_manager->processEvent($pane_info_collector);

        $submilestone_tracker = $this->sub_milestone_finder->findFirstSubmilestoneTracker($milestone);

        $original_project_collector = new OriginalProjectCollector($milestone->getArtifact(), $user);
        $this->event_manager->processEvent($original_project_collector);

        $milestone_representation = MilestoneRepresentation::build(
            $milestone,
            $status_count,
            $backlog_trackers,
            $this->parent_tracker_retriever->getCreatableParentTrackers($milestone, $user, $backlog_trackers),
            $this->milestone_factory->userCanChangePrioritiesInMilestone($milestone, $user),
            $representation_type,
            $this->getSubPlanning($milestone),
            $pane_info_collector,
            $submilestone_tracker,
            $original_project_collector,
            $this->project_background_configuration
        );

        $milestone_representation_reference_holder                           = new class
        {
            /**
             * @var MilestoneRepresentation
             */
            public $milestone_representation;
        };
        $milestone_representation_reference_holder->milestone_representation = $milestone_representation;

        $this->event_manager->processEvent(
            self::AGILEDASHBOARD_EVENT_REST_GET_MILESTONE,
            [
                'version'                                   => 'v1',
                'user'                                      => $user,
                'milestone'                                 => $milestone,
                'milestone_representation_reference_holder' => &$milestone_representation_reference_holder,
            ]
        );

        return $milestone_representation_reference_holder->milestone_representation;
    }

    public function buildRepresentationsFromCollection(
        PaginatedMilestones $collection,
        \PFUser $user,
        string $representation_type,
    ): PaginatedMilestonesRepresentations {
        $representations = [];
        foreach ($collection->getMilestones() as $milestone) {
            $representations[] = $this->getMilestoneRepresentation($milestone, $user, $representation_type);
        }

        return new PaginatedMilestonesRepresentations($representations, $collection->getTotalSize());
    }

    /**
     * @return \Tracker[]
     */
    private function getBacklogTrackers(\Planning_Milestone $milestone): array
    {
        return $this->backlog_factory->getBacklog($milestone)->getDescendantTrackers();
    }

    private function getSubPlanning(\Planning_Milestone $milestone): ?\Planning
    {
        $planning = $milestone->getPlanning();

        return $this->planning_factory->getChildrenPlanning($planning);
    }
}
