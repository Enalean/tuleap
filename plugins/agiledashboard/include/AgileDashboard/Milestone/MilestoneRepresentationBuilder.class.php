<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

use Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation;

class AgileDashboard_Milestone_MilestoneRepresentationBuilder {

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory */
    private $backlog_strategy_factory;

    /** @var EventManager */
    private $event_manager;

    public function __construct(
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Backlog_BacklogStrategyFactory $backlog_strategy_factory,
        EventManager $event_manager
    ) {
        $this->milestone_factory        = $milestone_factory;
        $this->backlog_strategy_factory = $backlog_strategy_factory;
        $this->event_manager            = $event_manager;
    }

    public function getMilestoneRepresentation(Planning_Milestone $milestone, PFUser $user) {
        $milestone_representation = new MilestoneRepresentation();
        $milestone_representation->build(
            $milestone,
            $this->milestone_factory->getMilestoneStatusCount($user, $milestone),
            $this->getBacklogTrackers($milestone),
            $this->milestone_factory->userCanChangePrioritiesInMilestone($milestone, $user)
        );

        $this->event_manager->processEvent(
            AGILEDASHBOARD_EVENT_REST_GET_MILESTONE,
            array(
                'version'                  => 'v1',
                'user'                     => $user,
                'milestone'                => $milestone,
                'milestone_representation' => &$milestone_representation,
            )
        );

        return $milestone_representation;
    }

    public function getPaginatedSubMilestonesRepresentations(Planning_Milestone $milestone, PFUser $user, $limit, $offset, $order) {
        $sub_milestones = $this->milestone_factory->getPaginatedSubMilestones($user, $milestone, $limit, $offset, $order);

        $submilestones_representations = array();
        foreach($sub_milestones->getMilestones() as $submilestone) {
            $submilestones_representations[] = $this->getMilestoneRepresentation($submilestone, $user);
        }

        return new AgileDashboard_Milestone_PaginatedMilestonesRepresentations(
            $submilestones_representations,
            $sub_milestones->getTotalSize()
        );
    }

    public function getPaginatedTopMilestonesRepresentations(Project $project, PFUser $user, $limit, $offset, $order) {
        $all_top_milestones = $this->getTopMilestones($user, $project);

        if ($order === 'desc') {
            $all_top_milestones = array_reverse($all_top_milestones);
        }

        $top_milestones                 = array_slice($all_top_milestones, $offset, $limit);
        $top_milestones_representations = array();

        foreach($top_milestones as $top_milestone) {
            $top_milestones_representations[] = $this->getMilestoneRepresentation($top_milestone, $user);
        }

        return new AgileDashboard_Milestone_PaginatedMilestonesRepresentations($top_milestones_representations, count($all_top_milestones));
    }

    private function getBacklogTrackers(Planning_Milestone $milestone) {
        return $this->backlog_strategy_factory->getBacklogStrategy($milestone)->getDescendantTrackers();
    }

    private function getTopMilestones(PFUser $user, Project $project) {
        $top_milestones = array();
        $milestones     = $this->milestone_factory->getSubMilestones($user, $this->milestone_factory->getVirtualTopMilestone($user, $project));

        foreach ($milestones as $milestone) {
            $top_milestones[] = $milestone;
        }

        return $top_milestones;
    }
}
