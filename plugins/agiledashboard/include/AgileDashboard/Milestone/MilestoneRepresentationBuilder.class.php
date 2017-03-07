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
use Tuleap\AgileDashboard\ScrumForMonoMilestoneChecker;

class AgileDashboard_Milestone_MilestoneRepresentationBuilder {

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory */
    private $backlog_strategy_factory;

    /** @var EventManager */
    private $event_manager;

    /**
     * @var ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;

    public function __construct(
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Backlog_BacklogStrategyFactory $backlog_strategy_factory,
        EventManager $event_manager,
        ScrumForMonoMilestoneChecker $scrum_mono_milestone_checker
    ) {
        $this->milestone_factory            = $milestone_factory;
        $this->backlog_strategy_factory     = $backlog_strategy_factory;
        $this->event_manager                = $event_manager;
        $this->scrum_mono_milestone_checker = $scrum_mono_milestone_checker;
    }

    public function getMilestoneRepresentation(Planning_Milestone $milestone, PFUser $user, $representation_type) {
        $status_count = array();
        if ($representation_type === MilestoneRepresentation::ALL_FIELDS) {
            $status_count = $this->milestone_factory->getMilestoneStatusCount($user, $milestone);
        }

        $is_scrum_mono_milestone_enabled = $this->scrum_mono_milestone_checker->isMonoMilestoneEnabled(
            $milestone->getProject()->getID()
        );

        $milestone_representation = new MilestoneRepresentation();
        $milestone_representation->build(
            $milestone,
            $status_count,
            $this->getBacklogTrackers($milestone),
            $this->milestone_factory->userCanChangePrioritiesInMilestone($milestone, $user),
            $representation_type,
            $is_scrum_mono_milestone_enabled
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

    public function getPaginatedSubMilestonesRepresentations(
        Planning_Milestone $milestone,
        PFUser $user,
        $representation_type,
        Tuleap\AgileDashboard\Milestone\Criterion\ISearchOnStatus $criterion,
        $limit,
        $offset,
        $order
    ) {
        $sub_milestones = $this->milestone_factory
            ->getPaginatedSubMilestones($user, $milestone, $criterion, $limit, $offset, $order);

        $submilestones_representations = array();
        foreach($sub_milestones->getMilestones() as $submilestone) {
            $submilestones_representations[] = $this->getMilestoneRepresentation($submilestone, $user, $representation_type);
        }

        return new AgileDashboard_Milestone_PaginatedMilestonesRepresentations(
            $submilestones_representations,
            $sub_milestones->getTotalSize()
        );
    }

    public function getPaginatedTopMilestonesRepresentations(
        Project $project,
        PFUser $user,
        $representation_type,
        Tuleap\AgileDashboard\Milestone\Criterion\ISearchOnStatus $criterion,
        $limit,
        $offset,
        $order
    ) {
        $sub_milestones = $this->milestone_factory
            ->getPaginatedTopMilestones($user, $project, $criterion, $limit, $offset, $order);

        $submilestones_representations = array();
        foreach($sub_milestones->getMilestones() as $submilestone) {
            $submilestones_representations[] = $this->getMilestoneRepresentation($submilestone, $user, $representation_type);
        }

        return new AgileDashboard_Milestone_PaginatedMilestonesRepresentations(
            $submilestones_representations,
            $sub_milestones->getTotalSize()
        );
    }

    private function getBacklogTrackers(Planning_Milestone $milestone) {
        return $this->backlog_strategy_factory->getBacklogStrategy($milestone)->getDescendantTrackers();
    }
}
