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

class AgileDashboard_Milestone_Pane_Planning_PlanningPresenter {
    private $backlog_item_type = '';
    private $parent_item_type  = '';
    private $backlog_collection;
    private $submilestone_collection;

    public function __construct(
            AgileDashboard_Milestone_Pane_ContentRowPresenterCollection $backlog_collection,
            AgileDashboard_Milestone_Pane_Planning_PlanningSubMilestonePresenterCollection $submilestone_collection
            ) {
        $this->backlog_collection      = $backlog_collection;
        $this->submilestone_collection = $submilestone_collection;
    }

    public function title() {
        return $this->backlog_item_type;
    }

    public function points() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_points');
    }

    public function parent() {
        if ($this->parent_item_type) {
            return $this->parent_item_type;
        } else {
            return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_parent');
        }
    }

    public function backlog_collection() {
        return $this->backlog_collection;
    }

    public function submilestone_collection() {
        return $this->submilestone_collection;
    }

    public function lab() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'lab');
    }

}

?>
