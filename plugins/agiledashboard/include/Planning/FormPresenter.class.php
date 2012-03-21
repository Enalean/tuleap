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

require_once dirname(__FILE__).'/../../../tracker/include/Tracker/TrackerFactory.class.php';
require_once 'TrackerPresenter.class.php';

class Planning_FormPresenter {
    /**
     * @var int
     */
    public $group_id;
    
    /**
     * @var TrackerFactory
     */
    public $tracker_factory;
    
    /**
     * @var Planning
     */
    public $planning;
    
    /**
     * @var Array of Tracker
     */
    private $available_trackers;
    
    public function __construct(/*int*/ $group_id, TrackerFactory $tracker_factory, $planning = null) {
        $this->group_id        = $group_id;
        $this->tracker_factory = $tracker_factory;
        $this->planning        = $planning;
    }
    
    public function getPlanningName() {
        return $this->planning->getName();
    }
    
    public function getPlanningId() {
        return $this->planning->getId();
    }
    
    public function getAvailableTrackers() {
        if ($this->available_trackers == null) {
            $available_trackers = array_values($this->tracker_factory->getTrackersByGroupId($this->group_id));
            $this->available_trackers = array_map(array($this, 'getPlanningTrackerPresenter'), $available_trackers);
        }
        return $this->available_trackers;
    }
    
    public function getPlanningTrackerPresenter(Tracker $tracker) {
        return new Planning_TrackerPresenter($this->planning, $tracker);
    }
}

?>
