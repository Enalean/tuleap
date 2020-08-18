<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\PanePresenterData;
use Tuleap\AgileDashboard\Milestone\Pane\TopPlanning\TopPlanningV2PaneInfo;
use Tuleap\AgileDashboard\Planning\AllowedAdditionalPanesToDisplayCollector;
use Tuleap\AgileDashboard\Planning\RootPlanning\DisplayTopPlanningAppEvent;

/**
 * I build panes for a Planning_Milestone
 */
class Planning_VirtualTopMilestonePaneFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const TOP_MILESTONE_DUMMY_ARTIFACT_ID = "ABC";

    /** @var PaneInfo[] */
    private $list_of_pane_info = [];

    /**
     * @var AgileDashboard_Pane[]
     * @psalm-var array<int|string, AgileDashboard_Pane>
     */
    private $active_pane = [];

    /** @var Codendi_Request */
    private $request;

    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        Codendi_Request $request,
        ExplicitBacklogDao $explicit_backlog_dao,
        EventManager $event_manager
    ) {
        $this->request              = $request;
        $this->explicit_backlog_dao = $explicit_backlog_dao;
        $this->event_manager        = $event_manager;
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

    private function buildListOfPaneInfo(Planning_Milestone $milestone)
    {
        $milestone_artifact_id = $this->getMilestoneArtifactId();
        $top_planning_pane = $this->getTopPlanningV2PaneInfo($milestone);

        $this->list_of_pane_info[$milestone_artifact_id][] = $top_planning_pane;
    }

    private function getTopPlanningV2PaneInfo(Planning_Milestone $milestone): ?TopPlanningV2PaneInfo
    {
        $milestone_artifact_id = $this->getMilestoneArtifactId();

        $milestone_tracker = $milestone->getPlanning()->getPlanningTracker();
        if (! $milestone_tracker) {
            return null;
        }

        $allowed_additional_panes_to_display_collector = new AllowedAdditionalPanesToDisplayCollector();
        $this->event_manager->processEvent($allowed_additional_panes_to_display_collector);

        $pane_info = new TopPlanningV2PaneInfo($milestone, $milestone_tracker);
        $pane_info->setActive(true);
        $project                                   = $this->request->getProject();
        $user                                      = $this->request->getCurrentUser();

        assert($milestone instanceof Planning_VirtualTopMilestone);
        $display_pv2_event = new DisplayTopPlanningAppEvent(
            $milestone,
            $user
        );
        $this->event_manager->processEvent($display_pv2_event);

        $this->active_pane[$milestone_artifact_id] = new AgileDashboard_Milestone_Pane_Planning_PlanningV2Pane(
            $pane_info,
            new AgileDashboard_Milestone_Pane_Planning_PlanningV2Presenter(
                $user,
                $project,
                $milestone_artifact_id,
                $this->explicit_backlog_dao->isProjectUsingExplicitBacklog((int) $project->getID()),
                $allowed_additional_panes_to_display_collector->getIdentifiers(),
                $display_pv2_event->canUserCreateMilestone(),
                $display_pv2_event->canBacklogItemsBeAdded()
            )
        );

        return $pane_info;
    }
}
