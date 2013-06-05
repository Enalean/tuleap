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
class Planning_MilestonePaneFactory {
    const TOP_MILESTONE_DUMMY_ARTIFACT_ID = "ABC";

    /** @var AgileDashboard_PaneInfo[] */
    private $list_of_pane_info = array();

    /** @var AgileDashboard_Pane */
    private $active_pane = array();

    /** @var Planning_Milestone[] */
    private $available_milestones = array();

    /** @var Codendi_Request */
    private $request;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory */
    private $pane_presenter_builder_factory;

    /** @var Planning_MilestoneLegacyPlanningPaneFactory */
    private $legacy_planning_pane_factory;

    /** @var AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder */
    private $submilestone_finder;

    /** @var string */
    private $theme_path;

    public function __construct(
        Codendi_Request $request,
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory $pane_presenter_builder_factory,
        Planning_MilestoneLegacyPlanningPaneFactory $legacy_planning_pane_factory,
        AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder $submilestone_finder,
        $theme_path
    ) {
        $this->request                        = $request;
        $this->milestone_factory              = $milestone_factory;
        $this->pane_presenter_builder_factory = $pane_presenter_builder_factory;
        $this->legacy_planning_pane_factory   = $legacy_planning_pane_factory;
        $this->submilestone_finder            = $submilestone_finder;
        $this->theme_path                     = $theme_path;
    }

    /** @return AgileDashboard_Milestone_Pane_PresenterData */
    public function getPanePresenterData(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId($milestone);
        $available_milestones = (isset($this->available_milestones[$milestone_artifact_id])) ? $this->available_milestones[$milestone_artifact_id] : array();

        return new AgileDashboard_Milestone_Pane_PresenterData(
            $this->getActivePane($milestone),
            $this->getListOfPaneInfo($milestone),
            $available_milestones
        );
    }

    /**
     * @param Planning_Milestone $milestone
     * @return int
     */
    private function getMilestoneArtifactId(Planning_Milestone $milestone) {
        if ($this->isTopMilestonePane()) {
            $milestone_artifact_id = self::TOP_MILESTONE_DUMMY_ARTIFACT_ID;
        } else {
            $milestone_artifact_id = $milestone->getArtifactId();
        }

        return $milestone_artifact_id;
    }

    private function isTopMilestonePane() {
        return $this->request->get('is_top');
    }

    /** @return AgileDashboard_Pane */
    private function getActivePane(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId($milestone);

        if (! isset($this->list_of_pane_info[$milestone_artifact_id])) {
            $this->buildActivePane($milestone);
        }

        return $this->active_pane[$milestone_artifact_id];
    }

    /** @return AgileDashboard_PaneInfo[] */
    public function getListOfPaneInfo(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId($milestone);

        if (! isset($this->list_of_pane_info[$milestone_artifact_id])) {
            $this->buildListOfPaneInfo($milestone);
        }

        return $this->list_of_pane_info[$milestone_artifact_id];
    }

    /** @return string */
    public function getDefaultPaneIdentifier() {
        return AgileDashboard_MilestonePlanningPaneInfo::IDENTIFIER;
    }

    private function buildListOfPaneInfo(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId($milestone);

        $this->active_pane[$milestone_artifact_id] = null;
        if (! $this->isTopMilestonePane() || ! $this->request->getCurrentUser()->useLabFeatures()) {
            $this->list_of_pane_info[$milestone_artifact_id][] = $this->getLegacyPaneInfo($milestone);
        }

        if ($this->request->getCurrentUser()->useLabFeatures()) {
            if ($this->isTopMilestonePane()) {
                $this->list_of_pane_info[$milestone_artifact_id][] = $this->getTopPlanningPaneInfo($milestone);
                $this->list_of_pane_info[$milestone_artifact_id][] = $this->getTopContentPaneInfo($milestone);
            } else {
                $this->list_of_pane_info[$milestone_artifact_id][] = $this->getContentPaneInfo($milestone);
                $this->list_of_pane_info[$milestone_artifact_id][] = $this->getPlanningPaneInfo($milestone);
            }
        }

        $this->buildAdditionnalPanes($milestone);
        $this->list_of_pane_info[$milestone_artifact_id] = array_values(array_filter($this->list_of_pane_info[$milestone_artifact_id]));
    }

    private function getLegacyPaneInfo(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId($milestone);

        if (! isset($this->legacy_planning_pane_info[$milestone_artifact_id])) {
            $this->legacy_planning_pane_info[$milestone_artifact_id] = $this->legacy_planning_pane_factory->getPaneInfo($milestone);
        }

        return $this->legacy_planning_pane_info[$milestone_artifact_id];
    }

    private function buildActivePane(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId($milestone);

        $this->buildListOfPaneInfo($milestone);
        $legacy_planning_pane_info = $this->getLegacyPaneInfo($milestone);

        if (! $this->active_pane[$milestone_artifact_id] && (! $milestone instanceof Planning_TopMilestone)) {
            $legacy_planning_pane_info->setActive(true);
            $this->active_pane[$milestone_artifact_id] = $this->legacy_planning_pane_factory->getPane($milestone, $legacy_planning_pane_info);
            $this->available_milestones[$milestone_artifact_id] = $this->legacy_planning_pane_factory->getAvailableMilestones($milestone);
        } elseif(! $milestone instanceof Planning_TopMilestone) {
            $this->available_milestones[$milestone_artifact_id] = $this->getAvailableMilestones($milestone);
        }
    }

    /**
     * @return \AgileDashboard_Milestone_Pane_Content_ContentPaneInfo
     */
    private function getContentPaneInfo(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId($milestone);
        $pane_info = new AgileDashboard_Milestone_Pane_Content_ContentPaneInfo($milestone, $this->theme_path);

        if ($this->request->get('pane') == AgileDashboard_Milestone_Pane_Content_ContentPaneInfo::IDENTIFIER) {
            $pane_info->setActive(true);
            $this->active_pane[$milestone_artifact_id] = new AgileDashboard_Milestone_Pane_Content_ContentPane(
                $pane_info,
                $this->getContentPresenterBuilder()->getMilestoneContentPresenter($this->request->getCurrentUser(), $milestone)
            );
        }

        return $pane_info;
    }

    /**
     * @return \AgileDashboard_Milestone_Pane_TopContent_PaneInfo
     */
    private function getTopContentPaneInfo(Planning_Milestone $milestone) {
        $top_pane_info = new AgileDashboard_Milestone_Pane_TopContent_PaneInfo($milestone, $this->theme_path);
        $milestone_artifact_id = $this->getMilestoneArtifactId($milestone);

        if ($this->request->get('pane') == AgileDashboard_Milestone_Pane_TopContent_PaneInfo::IDENTIFIER) {
            $top_pane_info->setActive(true);
            $this->active_pane[$milestone_artifact_id] = new AgileDashboard_Milestone_Pane_TopContent_Pane(
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
        $milestone_artifact_id = $this->getMilestoneArtifactId($milestone);

        $milestone_tracker = $milestone->getPlanning()->getPlanningTracker();
        if (! $milestone_tracker) {
            return;
        }

        $pane_info = new AgileDashboard_Milestone_Pane_TopPlanning_PaneInfo($milestone, $this->theme_path, $milestone_tracker);

        if ($this->request->get('pane') == AgileDashboard_Milestone_Pane_TopPlanning_PaneInfo::IDENTIFIER) {
            $pane_info->setActive(true);
            $this->active_pane[$milestone_artifact_id] = new AgileDashboard_Milestone_Pane_TopPlanning_Pane(
                $pane_info,
                $this->getTopPlanningPresenterBuilder()->getMilestoneTopPlanningPresenter($this->request->getCurrentUser(), $milestone, $milestone_tracker)
            );
        }

        return $pane_info;
    }

    /**
     * @return \AgileDashboard_Milestone_Pane_Planning_PlanningPaneInfo
     */
    private function getPlanningPaneInfo(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId($milestone);

        $submilestone_tracker = $this->submilestone_finder->findFirstSubmilestoneTracker($milestone);
        if (! $submilestone_tracker) {
            return;
        }

        $pane_info = new AgileDashboard_Milestone_Pane_Planning_PlanningPaneInfo($milestone, $this->theme_path, $submilestone_tracker);

        if ($this->request->get('pane') == AgileDashboard_Milestone_Pane_Planning_PlanningPaneInfo::IDENTIFIER) {
            $pane_info->setActive(true);
            $this->active_pane[$milestone_artifact_id] = new AgileDashboard_Milestone_Pane_Planning_PlanningPane(
                $pane_info,
                $this->getPlanningPresenterBuilder()->getMilestonePlanningPresenter($this->request->getCurrentUser(), $milestone, $submilestone_tracker)
            );
        }
        
        return $pane_info;
    }

    private function buildAdditionnalPanes(Planning_Milestone $milestone) {
        $milestone_artifact_id = $this->getMilestoneArtifactId($milestone);

        if ($milestone->getArtifact()) {
            EventManager::instance()->processEvent(
                AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE,
                array(
                    'milestone'         => $milestone,
                    'request'           => $this->request,
                    'user'              => $this->request->getCurrentUser(),
                    'panes'             => &$this->list_of_pane_info[$milestone_artifact_id],
                    'active_pane'       => &$this->active_pane[$milestone_artifact_id],
                    'milestone_factory' => $this->milestone_factory,
                )
            );
        }
    }

    protected function getAvailableMilestones(Planning_Milestone $milestone) {
        if ($milestone->hasAncestors()) {
            return $this->milestone_factory->getSiblingMilestones($this->request->getCurrentUser(), $milestone);
        } else {
            return $this->getAllMilestonesOfCurrentPlanning($milestone);
        }
    }

    private function getAllMilestonesOfCurrentPlanning(Planning_Milestone $milestone) {
        return $this->milestone_factory->getAllBareMilestones($this->request->getCurrentUser(), $milestone->getPlanning());
    }

    private function getContentPresenterBuilder() {
        return $this->pane_presenter_builder_factory->getContentPresenterBuilder();
    }

    private function getTopContentPresenterBuilder() {
        return $this->pane_presenter_builder_factory->getTopContentPresenterBuilder();
    }

    private function getPlanningPresenterBuilder() {
        return $this->pane_presenter_builder_factory->getPlanningPresenterBuilder();
    }

    private function getTopPlanningPresenterBuilder() {
        return $this->pane_presenter_builder_factory->getTopPlanningPresenterBuilder();
    }
}

?>
