<?php
/**
 * Copyright (c) Enalean, 2013 – 2018. All Rights Reserved.
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

use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\PanePresenterData;
use Tuleap\AgileDashboard\Milestone\Pane\TopPlanning\TopPlanningV2PaneInfo;

/**
 * I build panes for a Planning_Milestone
 */
class Planning_VirtualTopMilestonePaneFactory
{
    public const TOP_MILESTONE_DUMMY_ARTIFACT_ID = "ABC";

    /**
     * If PRELOAD_ENABLED is set to true, planning v2 data will be injected to the view.
     * If it's set to false, data will be asynchronously fetched via REST calls.
     */
    public const PRELOAD_ENABLED                 = false;
    public const PRELOAD_PAGINATION_LIMIT        = 50;
    public const PRELOAD_PAGINATION_OFFSET       = 0;
    public const PRELOAD_PAGINATION_ORDER        = 'desc';

    /** @var PaneInfo[] */
    private $list_of_pane_info = array();

    /** @var AgileDashboard_Pane */
    private $active_pane = array();

    /** @var Codendi_Request */
    private $request;

    /** @var AgileDashboard_Milestone_MilestoneRepresentationBuilder */
    private $milestone_representation_builder;

    /** @var AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder */
    private $paginated_backlog_items_representations_builder;
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    public function __construct(
        Codendi_Request $request,
        AgileDashboard_Milestone_MilestoneRepresentationBuilder $milestone_representation_builder,
        AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder $paginated_backlog_items_representations_builder,
        ExplicitBacklogDao $explicit_backlog_dao
    ) {
        $this->request                                         = $request;
        $this->milestone_representation_builder                = $milestone_representation_builder;
        $this->paginated_backlog_items_representations_builder = $paginated_backlog_items_representations_builder;
        $this->explicit_backlog_dao                            = $explicit_backlog_dao;
    }

    /** @return PanePresenterData */
    public function getPanePresenterData(Planning_Milestone $milestone)
    {
        $active_pane = $this->getActivePane($milestone);//This needs to be run first!

        return new PanePresenterData(
            $active_pane,
            $this->getListOfPaneInfo($milestone)
        );
    }

    /**
     * @param Planning_Milestone $milestone
     * @return int
     */
    private function getMilestoneArtifactId()
    {
         return self::TOP_MILESTONE_DUMMY_ARTIFACT_ID;
    }

    /** @return AgileDashboard_Pane */
    public function getActivePane(Planning_Milestone $milestone)
    {
        $milestone_artifact_id = $this->getMilestoneArtifactId();

        if (! isset($this->list_of_pane_info[$milestone_artifact_id])) {
            $this->buildListOfPaneInfo($milestone);
        }

        return $this->active_pane[$milestone_artifact_id];
    }

    /** @return AgileDashboard_PaneInfo[] */
    public function getListOfPaneInfo(Planning_Milestone $milestone)
    {
        $milestone_artifact_id = $this->getMilestoneArtifactId();

        if (! isset($this->list_of_pane_info[$milestone_artifact_id])) {
            $this->buildListOfPaneInfo($milestone);
        }

        return $this->list_of_pane_info[$milestone_artifact_id];
    }

    /** @return string */
    public function getDefaultPaneIdentifier()
    {
        return DetailsPaneInfo::IDENTIFIER;
    }

    private function buildListOfPaneInfo(Planning_Milestone $milestone)
    {
        $milestone_artifact_id = $this->getMilestoneArtifactId();

        $this->active_pane[$milestone_artifact_id] = null;

        $this->list_of_pane_info[$milestone_artifact_id][] = $this->getTopPlanningV2PaneInfo($milestone);
    }

    /**
     * @return \AgileDashboard_Milestone_Pane_Planning_PlanningPaneInfo
     */
    private function getTopPlanningV2PaneInfo(Planning_Milestone $milestone)
    {
        $milestone_artifact_id = $this->getMilestoneArtifactId();

        $milestone_tracker = $milestone->getPlanning()->getPlanningTracker();
        if (! $milestone_tracker) {
            return;
        }

        $pane_info = new TopPlanningV2PaneInfo($milestone, $milestone_tracker);
        $pane_info->setActive(true);
        $project                                   = $this->request->getProject();
        $user                                      = $this->request->getCurrentUser();
        $this->active_pane[$milestone_artifact_id] = new AgileDashboard_Milestone_Pane_Planning_PlanningV2Pane(
            $pane_info,
            new AgileDashboard_Milestone_Pane_Planning_PlanningV2Presenter(
                $user,
                $project,
                $milestone_artifact_id,
                null,
                $this->getPaginatedBacklogItemsRepresentationsForTopMilestone($milestone, $user),
                $this->getPaginatedTopMilestonesRepresentations($project, $user),
                $this->explicit_backlog_dao->isProjectUsingExplicitBacklog((int) $project->getID())
            )
        );

        return $pane_info;
    }

    private function getPaginatedBacklogItemsRepresentationsForTopMilestone(Planning_Milestone $milestone, PFUser $user)
    {
        if (! self::PRELOAD_ENABLED) {
            return null;
        }

        return $this->paginated_backlog_items_representations_builder->getPaginatedBacklogItemsRepresentationsForTopMilestone(
            $user,
            $milestone,
            self::PRELOAD_PAGINATION_LIMIT,
            self::PRELOAD_PAGINATION_OFFSET
        );
    }

    private function getPaginatedTopMilestonesRepresentations(Project $project, PFUser $user)
    {
        if (! self::PRELOAD_ENABLED) {
            return null;
        }

        return $this->milestone_representation_builder->getPaginatedTopMilestonesRepresentations(
            $project,
            $user,
            new Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen(),
            self::PRELOAD_PAGINATION_LIMIT,
            self::PRELOAD_PAGINATION_OFFSET,
            self::PRELOAD_PAGINATION_ORDER
        );
    }
}
