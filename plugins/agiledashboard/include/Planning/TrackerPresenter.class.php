<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
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

require_once dirname(__FILE__) . '/../../../tracker/include/Tracker/Tracker.class.php';

class Planning_TrackerPresenter
{

    /**
     * @var Planning
     */
    private $planning;

    /**
     * @var Tracker
     */
    private $tracker;

    public function __construct(Planning $planning, Tracker $tracker)
    {
        $this->planning = $planning;
        $this->tracker  = $tracker;
    }

    public function getId()
    {
        return $this->tracker->getId();
    }

    public function getName()
    {
        return $this->tracker->getName();
    }

    public function selectedIfBacklogTracker()
    {
        return (in_array($this->tracker->getId(), $this->planning->getBacklogTrackersIds()));
    }

    public function selectedIfPlanningTracker()
    {
        return ($this->tracker->getId() == $this->planning->getPlanningTrackerId());
    }

    /**
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }
}
