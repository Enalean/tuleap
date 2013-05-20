<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 * I carry data between MilestonePane factories and MilestonePresenter
 */
class AgileDashboard_Milestone_Pane_PresenterData {

    /** @var AgileDashboard_Pane */
    private $active_pane;

    /** @var AgileDashboard_PaneInfo[] */
    private $list_of_pane_info;

    /** @var Planning_Milestone[] */
    private $available_milestones;

    public function __construct(AgileDashboard_Pane $active_pane, array $list_of_pane_info, array $available_milestones) {
        $this->active_pane          = $active_pane;
        $this->list_of_pane_info    = $list_of_pane_info;
        $this->available_milestones = $available_milestones;
    }

    /** @return AgileDashboard_Pane */
    public function getActivePane() {
        return $this->active_pane;
    }

    /** @return AgileDashboard_PaneInfo[] */
    public function getListOfPaneInfo() {
        return $this->list_of_pane_info;
    }

    /** @return Planning_Milestone[] */
    public function getAvailableMilestones() {
        return $this->available_milestones;
    }
}

?>
