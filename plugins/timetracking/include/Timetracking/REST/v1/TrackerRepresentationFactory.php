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
use UserHelper;

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
     * @var UserHelper
     */
    private $user_helper;

    public function __construct(
        TimeDao $time_dao,
        PermissionsRetriever $permissions_retriever,
        UserHelper $user_helper
    ) {
        $this->time_dao              = $time_dao;
        $this->permissions_retriever = $permissions_retriever;
        $this->user_helper           = $user_helper;
    }

    public function getTrackersRepresentationWithTimes(array $trackers, DateTrackingTimesPeriod $dates, \PFUser $user, int $limit, int $offset): array
    {
        $authorized_trackers_ids = [];
        foreach ($trackers as $tracker) {
            if ($this->permissions_retriever->userCanSeeAggregatedTimesInTracker($user, $tracker)) {
                $authorized_trackers_ids[] = (int) $tracker->getId();
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
            $this->user_helper->getDisplayNameSQLQuery(),
            $limit,
            $offset
        );

        foreach ($authorized_trackers_ids as $tracker_id) {
            $tracker_representation = new TimetrackingTrackerReportRepresentation();
            $tracker_representation->build($trackers[$tracker_id], $this->getTrackersRow($tracker_id, $trackers_rows));
            $tracker_representations[] = $tracker_representation;
        }

        return $tracker_representations;
    }

    private function getTrackersRow(int $tracker_id, array $trackers_rows): array
    {
        $selected_rows = [];
        foreach ($trackers_rows as $tracker_row) {
            if ($tracker_id === (int) $tracker_row['tracker_id']) {
                $tracker_user_representation = TimetrackingTrackerUserRepresentation::build(
                    $tracker_row['full_name'],
                    (int) $tracker_row['user_id'],
                    (int) $tracker_row['minutes']
                );
                $selected_rows[] = $tracker_user_representation;
            }
        }

        return $selected_rows;
    }
}
