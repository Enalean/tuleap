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

    const NUMBER_TO_DISPLAY = 5;

    /** @var bool */
    private $is_latest = false;

    /**
     * @var PFUser
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

    /** @var array of Planning_MilestoneLinkPresenter */
    private $presenters;

    /** @var string */
    private $theme_path;

    public function __construct(Planning $planning, PFUser $user, Planning_MilestoneFactory $milestone_factory, $theme_path) {
        $this->user              = $user;
        $this->planning          = $planning;
        $this->milestone_factory = $milestone_factory;
        $this->theme_path        = $theme_path;
    }

    public function getLastOpenMilestones() {
        return array_slice($this->getMilestoneLinkPresenters(), 0, self::NUMBER_TO_DISPLAY);
    }

    /**
     * @return Planning_Milestone
     */
    public function getCurrentMilestone() {
        return $this->milestone_factory->getCurrentMilestone($this->user, $this->planning->getId());
    }

    public function hasMoreMilestone() {
        return count($this->getMilestoneLinkPresenters()) > self::NUMBER_TO_DISPLAY;
    }

    private function getMilestoneLinkPresenters() {
        if (!$this->presenters) {
            $this->presenters = array();
            $milestones = $this->milestone_factory->getLastOpenMilestones($this->user, $this->planning, self::NUMBER_TO_DISPLAY + 1);
            foreach ($milestones as $milestone) {
                $this->presenters[] = new Planning_ShortAccessMilestonePresenter($this, $milestone, $this->milestone_factory, $this->user, $this->theme_path);
            }
            if (!empty($this->presenters)) {
                end($this->presenters)->setIsLatest();
            }
            $this->presenters = array_reverse($this->presenters);
        }
        return $this->presenters;
    }

    public function planningTrackerId() {
        return $this->planning->getPlanningTrackerId();
    }

    public function planningRedirectToNew() {
        return 'planning['. $this->planning->getId() .']=-1';
    }

    public function setIsLatest() {
        $this->is_latest = true;
    }

    public function isLatest() {
        return $this->is_latest;
    }

    //TODO: use the one in MilestonePresenter???
    public function createNewItemToPlan() {
        $tracker = $this->planning->getPlanningTracker();
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'create_new_item_to_plan', array($tracker->getItemName()));
    }

    /**
     *
     * @return Planning
     */
    public function getPlanning() {
        return $this->planning;
    }
}
?>
