<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
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

namespace Tuleap\Timetracking\REST\v1;

use PFUser;
use Project;
use Tracker_REST_TrackerRestBuilder;
use TrackerFactory;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

class TimetrackingOverviewRepresentationsBuilder
{
    /**
     * @var AdminDao
     */
    private $admin_dao;

    /**
     * @var PermissionsRetriever
     */
    private $permissions_retriever;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_REST_TrackerRestBuilder
     */
    private $tracker_rest_builder;

    public function __construct(
        AdminDao $admin_dao,
        $permissions_retriever,
        TrackerFactory $tracker_factory,
        Tracker_REST_TrackerRestBuilder $tracker_rest_builder
    ) {
        $this->admin_dao             = $admin_dao;
        $this->permissions_retriever = $permissions_retriever;
        $this->tracker_factory       = $tracker_factory;
        $this->tracker_rest_builder  = $tracker_rest_builder;
    }

    /**
     * @param int     $limit
     * @param int     $offset
     * @return array
     */
    public function getTrackersMinimalRepresentationsWithTimetracking(PFUser $user, Project $project, $limit, $offset)
    {
        $tracker_representations = [];
        $trackers_row            = $this->admin_dao->getProjectTrackersWithEnabledTimetracking(
            $project->getID(),
            $limit,
            $offset
        );
        $total_rows              = (int) $this->admin_dao->foundRows();

        foreach ($trackers_row as $tracker_row) {
            $tracker_representation = new MinimalTrackerRepresentation();
            $tracker                = $this->tracker_factory->getTrackerById($tracker_row["tracker_id"]);
            if ($tracker === null) {
                throw new \RuntimeException('Tracker does not exist');
            }
            if ($this->checkTrackerAndPermissions($tracker, $user)) {
                $tracker_representation->build($tracker);
                $tracker_representations["trackers"][] = $tracker_representation;
            }
        }
        $tracker_representations['total_trackers'] = $total_rows;

        return $tracker_representations;
    }

    /**
     * @param int     $limit
     * @param int     $offset
     * @return array
     */
    public function getTrackersFullRepresentationsWithTimetracking(PFUser $user, Project $project, $limit, $offset)
    {
        $tracker_representations = [];
        $trackers_row            = $this->admin_dao->getProjectTrackersWithEnabledTimetracking(
            $project->getID(),
            $limit,
            $offset
        );
        $total_rows              = (int) $this->admin_dao->foundRows();

        foreach ($trackers_row as $tracker_row) {
            $tracker = $this->tracker_factory->getTrackerById($tracker_row["tracker_id"]);
            if ($tracker === null) {
                throw new \RuntimeException('Tracker does not exist');
            }
            if ($this->checkTrackerAndPermissions($tracker, $user)) {
                $tracker_representations["trackers"][] = $this->tracker_rest_builder->getTrackerRepresentationInTrackerContext(
                    $user,
                    $tracker
                );
            }
        }
        $tracker_representations['total_trackers'] = $total_rows;

        return $tracker_representations;
    }

    private function checkTrackerAndPermissions(\Tracker $tracker, PFUser $user)
    {
        return $tracker !== null
            && $tracker->userCanView($user)
            && $this->permissions_retriever->userCanSeeAggregatedTimesInTracker($user, $tracker);
    }
}
