<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Timetracking\Permissions;

use PFUser;
use Tracker;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;

class PermissionsRetriever
{
    /**
     * @var TimetrackingUgroupRetriever
     */
    private $timetracking_ugroup_retriever;

    public function __construct(TimetrackingUgroupRetriever $timetracking_ugroup_retriever)
    {
        $this->timetracking_ugroup_retriever = $timetracking_ugroup_retriever;
    }

    public function userCanAddTimeInTracker(PFUser $user, Tracker $tracker)
    {
        foreach ($this->timetracking_ugroup_retriever->getWriterIdsForTracker($tracker) as $ugroup_id) {
            if ($user->isMemberOfUGroup($ugroup_id, $tracker->getGroupId(), $tracker->getId())) {
                return true;
            }
        }

        return false;
    }

    public function userCanSeeAggregatedTimesInTracker(PFUser $user, Tracker $tracker)
    {
        foreach ($this->timetracking_ugroup_retriever->getReaderIdsForTracker($tracker) as $ugroup_id) {
            if ($user->isMemberOfUGroup($ugroup_id, $tracker->getGroupId(), $tracker->getId())) {
                return true;
            }
        }

        return false;
    }
}