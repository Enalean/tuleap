<?php
/**
 * Copyright Enalean (c) 2019. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registrated trademarks owned by
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

declare(strict_types=1);

namespace Tuleap\Timetracking\Time;

use Tracker;

class TimetrackingReport
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Tracker[]
     */
    private $trackers = [];

    /**
     * @var Tracker[]
     */
    private $valid_trackers = [];

    /**
     * @var Tracker[]
     */
    private $invalid_trackers = [];

    public function __construct(int $id, array $trackers)
    {
        $this->id       = $id;
        $this->trackers = $trackers;
    }

    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return Tracker[]
     */
    public function getTrackers() : array
    {
        if (count($this->valid_trackers) === 0 && count($this->invalid_trackers) === 0) {
            $this->populateValidityTrackers();
        }
        return $this->valid_trackers;
    }

    /**
     * @return Tracker[]
     */
    public function getInvalidTrackers() : array
    {
        if (count($this->valid_trackers) === 0 && count($this->invalid_trackers) === 0) {
            $this->populateValidityTrackers();
        }
        return $this->invalid_trackers;
    }

    private function populateValidityTrackers() : void
    {
        $this->valid_trackers   = [];
        $this->invalid_trackers = [];
        foreach ($this->trackers as $tracker) {
            $project = $tracker->getProject();
            if ($project === null || ! $project->isActive()) {
                $this->invalid_trackers[] = $tracker;
            } else {
                $this->valid_trackers[] = $tracker;
            }
        }
    }
}
