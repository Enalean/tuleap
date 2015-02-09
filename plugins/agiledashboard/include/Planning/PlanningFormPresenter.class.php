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


class Planning_FormPresenter extends PlanningPresenter {
    // Manage translation
    public $__ = array(__CLASS__, '__trans');

    /**
     * @var PlanningPermissionsManager
     */
    private $planning_permissions_manager;

    /**
     * @var int
     */
    public $planning_id;
    
    /**
     * @var int
     */
    public $group_id;
    
    /**
     * @var Planning
     */
    public $planning;
    
    /**
     * @var Planning_TrackerPresenter[]
     */
    public $available_backlog_trackers;
    
    /**
     * @var Planning_TrackerPresenter[]
     */
    public $available_planning_trackers;

    /**
     * @var string HTML string that allows for the cardwall configuration on a planning
     */
    public $cardwall_admin;

    public function __construct(
        PlanningPermissionsManager $planning_permissions_manager,
        Planning $planning,
        array $available_backlog_trackers,
        array $available_planning_trackers,
        $cardwall_admin
    ) {
        parent::__construct($planning);
        
        $this->planning_permissions_manager = $planning_permissions_manager;
        $this->planning_id                  = $planning->getId();
        $this->group_id                     = $planning->getGroupId();
        $this->available_backlog_trackers   = $available_backlog_trackers;
        $this->available_planning_trackers  = $available_planning_trackers;
        $this->cardwall_admin               = $cardwall_admin;
    }

    public function adminTitle() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'Admin');
    }
    
    public function createPlanning() {
        return  $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_create');
    }
    
    public function editPlanning() {
        return  $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_editing');
    }
    
    public function planningNameFieldLabel() {
        return  $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_name');
    }
    
    public function planningBacklogTitleFieldLabel() {
        return  $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_backlog_title');
    }
    
    public function planningPlanTitleFieldLabel() {
        return  $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_plan_title');
    }
    
    public function planningPriorityChangePermissionFieldLabel() {
        return  $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_priority_change_permission');
    }

    public function priority_change_permission() {
        return $this->planning_permissions_manager->getPlanningPermissionForm($this->planning_id, $this->group_id, PlanningPermissionsManager::PERM_PRIORITY_CHANGE, 'planning['.PlanningPermissionsManager::PERM_PRIORITY_CHANGE.']');
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