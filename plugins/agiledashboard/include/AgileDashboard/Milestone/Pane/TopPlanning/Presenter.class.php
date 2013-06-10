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

class AgileDashboard_Milestone_Pane_TopPlanning_Presenter {
    private $backlog_item_type = '';
    private $backlog_collection;
    private $milestone_collection;
    private $milestone_id;
    private $milestone_planning_id;
    private $milestone_item_type;
    private $add_new_milestone_url;
    private $can_plan;
    private $can_add_milestone;
    private $redirect_to_self;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $backlog_collection,
        AgileDashboard_Milestone_Pane_TopPlanning_MilestonePresenterCollection $submilestone_collection,
        Planning_Milestone $milestone,
        $parent_item_type,
        $backlog_item_type,
        $milestone_item_type,
        $add_new_milestone_url,
        $can_add_milestone,
        $can_plan,
        $redirect_to_self
    ) {
        $this->backlog_collection       = $backlog_collection;
        $this->milestone_collection     = $submilestone_collection;
        $this->parent_item_type         = $parent_item_type;
        $this->backlog_item_type        = $backlog_item_type;
        $this->milestone_item_type      = $milestone_item_type;
        $this->add_new_milestone_url    = $add_new_milestone_url;
        $this->can_add_milestone        = $can_add_milestone;
        $this->can_plan                 = $can_plan;
        $this->redirect_to_self         = $redirect_to_self;
        $this->milestone_id             = $milestone->getArtifactId();
        $this->milestone_planning_id    = $milestone->getPlanningId();
    }

    public function milestone_id() {
        return $this->milestone_id;
    }

    public function milestone_planning_id() {
        return $this->milestone_planning_id;
    }

    public function backlog_title() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'backlog_title', array($this->milestone_item_type));
    }

    public function list_of_milestone_title() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'list_of_milestone_title', array($this->milestone_item_type));
    }

    public function help_intro() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'help_intro', array($this->milestone_item_type));
    }

    public function help_left() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'help_left', array($this->milestone_item_type, $this->milestone_item_type));
    }

    public function help_right() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'help_right', array($this->milestone_item_type, $this->milestone_item_type));
    }

    public function help_dnd() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'help_dnd', array($this->milestone_item_type, $this->milestone_item_type));
    }

    public function can_add_milestone() {
        return $this->can_add_milestone;
    }

    public function add_new_submilestone_url() {
        return $this->add_new_milestone_url.'&'.$this->redirect_to_self;
    }

    public function add_new_submilestone() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'add_subitem', array($this->milestone_item_type));
    }

    public function title() {
        return $this->backlog_item_type;
    }

    public function points() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_points');
    }

    public function header_status() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'header_status');
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

    public function milestone_collection() {
        return $this->milestone_collection;
    }

    public function lab() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'lab');
    }

    public function can_plan() {
        return ($this->can_plan) ? 'true' : 'false';
    }

    public function empty_element_tip() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'empty_element_tip');
    }

}

?>
