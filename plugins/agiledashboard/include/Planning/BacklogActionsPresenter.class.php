<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Planning_BacklogActionsPresenter {
    /**
     * @var Tracker
     */
    private $tracker;
    private $planning_redirect_parameter;
    private $current_milestone;

    public function __construct(Tracker $tracker, Planning_Milestone $current_milestone, $planning_redirect_parameter) {
        $this->tracker                     = $tracker;
        $this->current_milestone           = $current_milestone;
        $this->planning_redirect_parameter = $planning_redirect_parameter;
    }
    
    public function getBacklogTracker() {
        return $this->tracker;
    }
    
    public function addLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'backlog_add');
    }

    public function planning_redirect_parameter() {
        return $this->planning_redirect_parameter;
    }

    public function current_milestone_id() {
        return $this->current_milestone->getArtifactId();
    }
    
    /**
     * @return string
     */
    public function backlogHelp() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'backlog_destination_help');
    }
}

?>
