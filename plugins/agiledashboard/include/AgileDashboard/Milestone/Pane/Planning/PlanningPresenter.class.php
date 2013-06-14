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
    private $milestone_id;
    private $milestone_planning_id;
    private $submilestone_item_type;
    private $add_new_submilestone_url;
    private $can_plan;
    private $redirect_to_self;
    private $milestone_item_type;

    /** @var String */
    private $descendant_item_name;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $backlog_collection,
        AgileDashboard_Milestone_Pane_Planning_PlanningSubMilestonePresenterCollection $submilestone_collection,
        Planning_ArtifactMilestone $milestone,
        $parent_item_type,
        $backlog_item_type,
        $submilestone_item_type,
        $add_new_submilestone_url,
        $can_add_submilestone,
        $can_plan,
        $redirect_to_self
    ) {
        $this->backlog_collection       = $backlog_collection;
        $this->submilestone_collection  = $submilestone_collection;
        $this->parent_item_type         = $parent_item_type;
        $this->backlog_item_type        = $backlog_item_type;
        $this->submilestone_item_type   = $submilestone_item_type;
        $this->add_new_submilestone_url = $add_new_submilestone_url;
        $this->can_add_submilestone     = $can_add_submilestone;
        $this->can_plan                 = $can_plan;
        $this->redirect_to_self         = $redirect_to_self;
        $this->milestone_id             = $milestone->getArtifactId();
        $this->milestone_planning_id    = $milestone->getPlanningId();
        $this->milestone_item_type      = $milestone->getArtifactTitle();
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

    public function list_of_submilestone_title() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'list_of_submilestone_title', array($this->submilestone_item_type, $this->milestone_item_type));
    }

    public function help_intro() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'help_intro', array($this->submilestone_item_type));
    }

    public function help_left() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'help_left', array($this->submilestone_item_type, $this->milestone_item_type));
    }

    public function help_right() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'help_right', array($this->submilestone_item_type, $this->milestone_item_type));
    }

    public function help_dnd() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'help_dnd', array($this->submilestone_item_type, $this->milestone_item_type));
    }

    public function can_add_submilestone() {
        return $this->can_add_submilestone;
    }

    public function add_new_submilestone_url() {
        return $this->add_new_submilestone_url.'&'.$this->redirect_to_self;
    }

    public function add_new_submilestone() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'add_subitem', array($this->submilestone_item_type));
    }

    public function setDescendantItemName($descendant_item_name) {
        $this->descendant_item_name = $descendant_item_name;
    }

    public function title() {
        if ($this->descendant_item_name) {
            return $this->descendant_item_name;
        }
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

    public function header_status() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'header_status');
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

    public function can_plan() {
        return ($this->can_plan) ? 'true' : 'false';
    }

    public function empty_element_tip() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_milestone_planning', 'empty_element_tip');
    }

}

?>
