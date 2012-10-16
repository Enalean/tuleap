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

class Planning_ShortAccess {

    /**
     * @var User
     */
    private $user;

    /**
     * @var Planning
     */
    public $planning;

    /**
     * @var Planning_MilestoneFactory
     */
    protected $milestone_factory;

    public function __construct(Planning $planning, User $user, Planning_MilestoneFactory $milestone_factory, PlanningFactory $planning_factory) {
        $this->user              = $user;
        $this->planning          = $planning;
        $this->milestone_factory = $milestone_factory;
        $this->planning_factory  = $planning_factory;
    }

    public function getLastFiveOpenMilestones() {
        $presenters = array();
        $milestones = $this->milestone_factory->getLastFiveOpenMilestones($this->user, $this->planning);
        foreach ($milestones as $milestone) {
            $presenters[] = new Planning_MilestoneLinkPresenter($milestone, $this->user);
        }
        return $presenters;
    }
    
    public function planningTrackerId() {
        return $this->planning->getPlanningTrackerId();
    }
    
    //TODO: use the one in MilestonePresenter???
    public function createNewItemToPlan() {
        $tracker = $this->planning_factory->getPlanningTracker($this->planning);
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'create_new_item_to_plan', array($tracker->getItemName()));
    }
}
?>
