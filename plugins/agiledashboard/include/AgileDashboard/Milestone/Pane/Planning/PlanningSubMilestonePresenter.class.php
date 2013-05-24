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

class AgileDashboard_Milestone_Pane_Planning_PlanningSubMilestonePresenter {
    private $id;
    private $planning_id;
    private $milestone_title   = '';
    private $backlog_item_type = '';
    private $parent_item_type  = '';

    public function __construct(Planning_Milestone $milestone) {
        $this->id                   = $milestone->getArtifactId();
        $this->planning_id          = $milestone->getPlanningId();
        $this->milestone_title      = $milestone->getArtifact()->getTitle();
        $this->milestone_start_date = $milestone->getStartDate();
        $this->milestone_end_date   = $milestone->getEndDate();
        $this->milestone_status     = $milestone->getArtifact()->getStatus();

    }

    public function id() {
        return $this->id;
    }

    public function planning_id() {
        return $this->planning_id;
    }

    public function milestone_title() {
        return $this->milestone_title;
    }

    public function milestone_url() {
        return '#';
    }

    public function display_milestone_dates() {
        return $this->milestone_start_date || $this->milestone_end_date;
    }

    public function start_date() {
        if (! $this->milestone_start_date) {
            return null;
        }
        return $this->formatDate($this->milestone_start_date);
    }

    public function end_date() {
        if (! $this->milestone_end_date) {
            return null;
        }
        return $this->formatDate($this->milestone_end_date);
    }

    private function formatDate($date) {
        return date($GLOBALS['Language']->getText('system', 'datefmt_day_and_month'), $date);
    }

    public function status() {
        return strtolower($this->milestone_status);
    }

    public function has_status() {
        return ! empty($this->milestone_status);
    }

    public function backlog_item_title() {
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
}

?>
