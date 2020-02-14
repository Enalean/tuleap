<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registered trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\Tracker\REST\v1\Event;

use Project;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

class GetTrackersWithCriteria implements Dispatchable
{
    public const NAME = "getTrackersWithCriteria";

    /**
     * @var Project
     */
    private $project;

    /**
     * @var array
     */
    private $query;
    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var String
     */
    private $representation;

    /**
     * @var int
     */
    private $total_trackers = 0;

    /**
     * @var MinimalTrackerRepresentation[]
     */
    private $trackers_with_criteria = [];

    public function __construct(array $query, $limit, $offset, Project $project, $representation = "full")
    {
        $this->query                  = $query;
        $this->limit                  = $limit;
        $this->offset                 = $offset;
        $this->project                = $project;
        $this->representation         = $representation;
        $this->total_trackers         = 0;
        $this->trackers_with_criteria = [];
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return String
     */
    public function getRepresentation()
    {
        return $this->representation;
    }

    /**
     * @return int
     */
    public function getTotalTrackers()
    {
        return $this->total_trackers;
    }

    /**
     * @param int $total_trackers
     */
    public function setTotalTrackers($total_trackers)
    {
        $this->total_trackers = $total_trackers;
    }

    /**
     * @return array
     */
    public function getTrackersWithCriteria()
    {
        return $this->trackers_with_criteria;
    }

    public function addTrackersWithCriteria(array $trackers)
    {
        $this->trackers_with_criteria = array_merge($this->trackers_with_criteria, $trackers);
    }
}
