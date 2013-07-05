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
 * I build panes info for a Planning_Milestone
 */
class AgileDashboard_PaneInfoFactory {

    /** @var PFUser */
    private $user;

    /** @var Planning_MilestoneLegacyPlanningPaneFactory */
    private $legacy_planning_pane_factory;

    /** @var AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder */
    private $submilestone_finder;

    /** @var string */
    private $theme_path;

    public function __construct(
        PFUser $user,
        Planning_MilestoneLegacyPlanningPaneFactory $legacy_planning_pane_factory,
        AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder $submilestone_finder,
        $theme_path
    ) {
        $this->user                         = $user;
        $this->legacy_planning_pane_factory = $legacy_planning_pane_factory;
        $this->submilestone_finder          = $submilestone_finder;
        $this->theme_path                   = $theme_path;
    }

    /** @return AgileDashboard_PaneInfo[] */
    public function getListOfPaneInfo(Planning_Milestone $milestone) {
        $panes_info = array();

        $panes_info[] = $this->getLegacyPaneInfo($milestone);
        if ($this->user->useLabFeatures()) {
            $panes_info[] = $this->getContentPaneInfo($milestone);
            $panes_info[] = $this->getPlanningPaneInfo($milestone);
        }
        $this->buildAdditionnalPanesInfo($milestone, $panes_info);

        return array_values(array_filter($panes_info));
    }

    public function getLegacyPaneInfo(Planning_Milestone $milestone) {
        return $this->legacy_planning_pane_factory->getPaneInfo($milestone);
    }

    public function getContentPaneInfo(Planning_Milestone $milestone) {
        return new AgileDashboard_Milestone_Pane_Content_ContentPaneInfo($milestone, $this->theme_path);
    }

    public function getPlanningPaneInfo(Planning_Milestone $milestone) {
        $submilestone_tracker = $this->submilestone_finder->findFirstSubmilestoneTracker($milestone);
        if (! $submilestone_tracker) {
            return;
        }

        return new AgileDashboard_Milestone_Pane_Planning_PlanningPaneInfo($milestone, $this->theme_path, $submilestone_tracker);
    }

    private function buildAdditionnalPanesInfo(Planning_Milestone $milestone, array &$panes_info) {
        if (! $milestone->getArtifact()) {
            return;
        }

        EventManager::instance()->processEvent(
            AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_INFO_ON_MILESTONE,
            array(
                'milestone'      => $milestone,
                'user'           => $this->user,
                'pane_info_list' => &$panes_info,
            )
        );
    }
}
?>
