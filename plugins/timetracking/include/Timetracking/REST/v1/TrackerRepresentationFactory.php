<?php
/**
 * Copyright Enalean (c) 2019. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

declare(strict_types=1);

namespace Tuleap\Timetracking\REST\v1;

use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\Time\TimeDao;

class TrackerRepresentationFactory
{
    /**
     * @var TimeDao
     */
    private $time_dao;

    /**
     * @var PermissionsRetriever
     */
    private $permissions_retriever;

    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        TimeDao $time_dao,
        PermissionsRetriever $permissions_retriever,
        \TrackerFactory $tracker_factory,
        \Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->time_dao              = $time_dao;
        $this->permissions_retriever = $permissions_retriever;
        $this->tracker_factory       = $tracker_factory;
        $this->artifact_factory      = $artifact_factory;
    }

    public function getTrackersRepresentationWithTimes(array $trackers, DateTrackingTimesPeriod $dates, \PFUser $user, int $limit, int $offset) : array
    {
        $authorized_trackers_ids = [];
        foreach ($trackers as $tracker) {
            if ($this->permissions_retriever->userCanSeeAggregatedTimesInTracker($user, $tracker)) {
                $authorized_trackers_ids[] = (int)$tracker->getId();
            }
        }

        $tracker_representations = [];
        if (empty($authorized_trackers_ids)) {
            return $tracker_representations;
        }

        $trackers_rows = $this->time_dao->getTotalTimeByTracker(
            $authorized_trackers_ids,
            $dates->getStartDate(),
            $dates->getEndDate(),
            $limit,
            $offset
        );

        foreach ($authorized_trackers_ids as $tracker_id) {
            $tracker_representation = new TimetrackingTrackerReportRepresentation();
            $tracker_representation->build($trackers[$tracker_id], $this->getMinutesFromRows($trackers_rows, $tracker_id));
            $tracker_representations[] = $tracker_representation;
        }

        return $tracker_representations;
    }

    private function getMinutesFromRows(array $trackers_rows, int $tracker_id) : int
    {
        $minutes = 0;
        foreach ($trackers_rows as $key => $tracker_row) {
            if ($tracker_row["tracker_id"] === $tracker_id) {
                $minutes = (int)($tracker_row[ "minutes" ]);
                unset($trackers_rows[$key]);
                break;
            }
        }

        return $minutes;
    }
}
