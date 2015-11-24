<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * I build panes for a Planning_Milestone
 */
class Planning_VirtualTopMilestonePaneFactory {
    const TOP_MILESTONE_DUMMY_ARTIFACT_ID = "ABC";

    /** @var AgileDashboard_PaneInfo[] */
    private $list_of_pane_info = array();

    /** @var AgileDashboard_Pane */
    private $active_pane = array();

    /** @var Planning_Milestone[] */
    private $available_milestones = array();

    /** @var Codendi_Request */
    private $request;


    /** @var AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory */
    private $pane_presenter_builder_factory;

    /** @var string */
    private $theme_path;

    public function __construct(
        Codendi_Request $request,
        AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory $pane_presenter_builder_factory,
        $theme_path
    ) {
        $this->request                        = $request;
        $this->pane_presenter_builder_factory = $pane_presenter_builder_factory;
        $this->theme_path                     = $theme_path;
    }

    /** @return AgileDashboard_Milestone_Pane_PresenterData */
    public function getPanePresenterData(Planning_Milestone $milestone) {
        $active_pane = $this->getActivePane($milestone);//This needs to be run first!
        $milestone_artifact_id = $this->getMilestoneArtifactId();

        $available_milestones =
                (isset($this->available_milestones[$milestone_artifact_id])) ?
                $this->available_milestones[$milestone_artifact_id] : array();

        return new AgileDashboard_Milestone_Pane_PresenterData(
            $active_pane,
            $this->getListOfPaneInfo($milestone),
            $available_milestones
        );
    }

    /**
     * @param Planning_Milestone $milestone
     * @return int
     */
    private function getMilestoneArtifactId() {
         return self::TOP_MILESTONE_DUMMY_ARTIFACT_ID;
    }

    /** @return AgileDashboard_Pane */
    public function getActivePane(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId();

        if (! isset($this->list_of_pane_info[$milestone_artifact_id])) {
            $this->buildListOfPaneInfo($milestone);
        }

        return $this->active_pane[$milestone_artifact_id];
    }

    /** @return AgileDashboard_PaneInfo[] */
    public function getListOfPaneInfo(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId();

        if (! isset($this->list_of_pane_info[$milestone_artifact_id])) {
            $this->buildListOfPaneInfo($milestone);
        }

        return $this->list_of_pane_info[$milestone_artifact_id];
    }

    /** @return string */
    public function getDefaultPaneIdentifier() {
        return AgileDashboard_Milestone_Pane_Content_ContentPaneInfo::IDENTIFIER;
    }

    private function buildListOfPaneInfo(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId();

        $this->active_pane[$milestone_artifact_id] = null;

        $this->list_of_pane_info[$milestone_artifact_id][] = $this->getTopContentPaneInfo($milestone);
        $this->list_of_pane_info[$milestone_artifact_id][] = $this->getTopPlanningPaneInfo($milestone);

        if ($this->request->getCurrentUser()->useLabFeatures() && defined('CARDWALL_BASE_URL')) {
            $this->list_of_pane_info[$milestone_artifact_id][] = $this->getTopPlanningV2PaneInfo($milestone);
        }
    }

    /**
     * @return \AgileDashboard_Milestone_Pane_TopContent_TopContentPaneInfo
     */
    private function getTopContentPaneInfo(Planning_Milestone $milestone) {
        $top_pane_info = new AgileDashboard_Milestone_Pane_TopContent_TopContentPaneInfo($milestone, $this->theme_path);
        $milestone_artifact_id = $this->getMilestoneArtifactId();

        if ($this->request->get('pane') == AgileDashboard_Milestone_Pane_TopContent_TopContentPaneInfo::IDENTIFIER) {
            $top_pane_info->setActive(true);
            $this->active_pane[$milestone_artifact_id] = new AgileDashboard_Milestone_Pane_Content_ContentPane(
                $top_pane_info,
                $this->getTopContentPresenterBuilder()->getMilestoneContentPresenter($this->request->getCurrentUser(), $milestone)
            );
        }

        return $top_pane_info;
    }

    /**
     * @return \AgileDashboard_Milestone_Pane_Planning_PlanningPaneInfo
     */
    private function getTopPlanningPaneInfo(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId();

        $milestone_tracker = $milestone->getPlanning()->getPlanningTracker();
        if (! $milestone_tracker) {
            return;
        }

        $pane_info = new AgileDashboard_Milestone_Pane_TopPlanning_TopPlanningPaneInfo($milestone, $this->theme_path, $milestone_tracker);

        if ($this->request->get('pane') == AgileDashboard_Milestone_Pane_TopPlanning_TopPlanningPaneInfo::IDENTIFIER) {
            $pane_info->setActive(true);
            $this->active_pane[$milestone_artifact_id] = new AgileDashboard_Milestone_Pane_Planning_PlanningPane(
                $pane_info,
                $this->getTopPlanningPresenterBuilder()->getMilestoneTopPlanningPresenter($this->request->getCurrentUser(), $milestone, $milestone_tracker)
            );
        }

        return $pane_info;
    }

    /**
     * @return \AgileDashboard_Milestone_Pane_Planning_PlanningPaneInfo
     */
    private function getTopPlanningV2PaneInfo(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId();

        $milestone_tracker = $milestone->getPlanning()->getPlanningTracker();
        if (! $milestone_tracker) {
            return;
        }

        $pane_info = new AgileDashboard_Milestone_Pane_TopPlanning_TopPlanningV2PaneInfo($milestone, $this->theme_path, $milestone_tracker);

        if ($this->request->get('pane') == AgileDashboard_Milestone_Pane_TopPlanning_TopPlanningV2PaneInfo::IDENTIFIER) {
            $pane_info->setActive(true);
            $this->active_pane[$milestone_artifact_id] = new AgileDashboard_Milestone_Pane_Planning_PlanningV2Pane(
                $pane_info,
                new AgileDashboard_Milestone_Pane_Planning_PlanningV2Presenter(
                    $this->request->getCurrentUser(),
                    $this->request->getProject(),
                    $milestone_artifact_id,
                    null
                )
            );
        }

        return $pane_info;
    }

    private function getTopContentPresenterBuilder() {
        return $this->pane_presenter_builder_factory->getTopContentPresenterBuilder();
    }

    private function getTopPlanningPresenterBuilder() {
        return $this->pane_presenter_builder_factory->getTopPlanningPresenterBuilder();
    }
}
?>
