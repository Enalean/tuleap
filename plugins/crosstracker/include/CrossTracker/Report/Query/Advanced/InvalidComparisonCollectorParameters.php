<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use PFUser;
use Tracker;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\VisitorParameters;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;

final class InvalidComparisonCollectorParameters implements VisitorParameters
{
    /**
     * @var int[]
     */
    private $tracker_ids;
    /**
     * @var InvalidSearchablesCollection
     */
    private $invalid_searchables_collection;
    /**
     * @var Tracker[]
     */
    private $trackers;
    /**
     * @var PFUser
     */
    private $user;

    public function __construct(
        InvalidSearchablesCollection $invalid_searchables_collection,
        array $trackers,
        PFUser $user,
    ) {
        $this->invalid_searchables_collection = $invalid_searchables_collection;
        $this->trackers                       = $trackers;
        $this->user                           = $user;

        $this->tracker_ids = array_map(
            function (Tracker $tracker) {
                return $tracker->getId();
            },
            $trackers
        );
    }

    /**
     * @return InvalidSearchablesCollection
     */
    public function getInvalidSearchablesCollection()
    {
        return $this->invalid_searchables_collection;
    }

    /**
     * @return Tracker[]
     */
    public function getTrackers()
    {
        return $this->trackers;
    }

    /**
     * @return PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return int[]
     */
    public function getTrackerIds()
    {
        return $this->tracker_ids;
    }
}
