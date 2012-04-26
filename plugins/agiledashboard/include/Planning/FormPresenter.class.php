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

require_once 'TrackerPresenter.class.php';

class Planning_FormPresenter {
    // Manage translation
    public $__ = array(__CLASS__, '__trans');
    
    /**
     * @var int
     */
    public $group_id;
    
    /**
     * @var Planning
     */
    public $planning;
    
    /**
     * @var Array of Tracker
     */
    public $available_trackers;
    
    /**
     * @var Array of Tracker
     */
    public $available_planning_trackers;
    
    public function __construct(Planning $planning, array $available_trackers, array $available_planning_trackers) {
        $this->group_id                    = $planning->getGroupId();
        $this->planning                    = $planning;
        $this->available_trackers          = array_map(array($this, 'getPlanningTrackerPresenter'), $available_trackers);
        $this->available_planning_trackers = array_map(array($this, 'getPlanningTrackerPresenter'), $available_planning_trackers);
    }
    
    public function getPlanningName() {
        return $this->planning->getName();
    }
    
    public function getPlanningId() {
        return $this->planning->getId();
    }
    
    public function getPlanningTrackerPresenter(Tracker $tracker) {
        return new Planning_TrackerPresenter($this->planning, $tracker);
    }
    
    public function createPlanning() {
        return  $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_create');
    }
    
    public function planningName() {
        return  $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_name');
    }
    
    public function btnSubmit() {
        return $GLOBALS['Language']->getText('global', 'btn_submit');
    }
    
    public function __trans($text) {
        $args = explode('|', $text);
        $secondary_key = array_shift($args);
        return $GLOBALS['Language']->getText('plugin_agiledashboard', $secondary_key, $args);
    }
}

?>
