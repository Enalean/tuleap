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

namespace Tuleap\Timetracking\Time;

use PFUser;
use Project;
use ProjectManager;
use Tracker_Artifact;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;

class TimeRetriever
{
    /**
     * @var TimeDao
     */
    private $dao;
    /**
     * @var PermissionsRetriever
     */
    private $permissions_retriever;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var AdminDao
     */
    private $admin_dao;

    public function __construct(TimeDao $dao, PermissionsRetriever $permissions_retriever, AdminDao $admin_dao, ProjectManager $project_manager)
    {
        $this->dao                   = $dao;
        $this->admin_dao             = $admin_dao;
        $this->permissions_retriever = $permissions_retriever;
        $this->project_manager       = $project_manager;
    }

    /**
     * @return Time[]
     */
    public function getTimesForUser(PFUser $user, Tracker_Artifact $artifact)
    {
        $times = array();

        if ($this->permissions_retriever->userCanSeeAggregatedTimesInTracker($user, $artifact->getTracker())) {
            foreach ($this->dao->getAllTimesAddedInArtifact($artifact->getId()) as $row_time) {
                $times[] = $this->buildTimeFromRow($row_time);
            }
        } elseif ($this->permissions_retriever->userCanAddTimeInTracker($user, $artifact->getTracker())) {
            foreach ($this->dao->getTimesAddedInArtifactByUser($user->getId(), $artifact->getId()) as $row_time) {
                $times[] = $this->buildTimeFromRow($row_time);
            }
        }

        return $times;
    }

    /**
     * @return Project[]
     */
    public function getProjectsWithTimetracking(PFUser $user, $limit, $offset)
    {
        $projects        = [];
        foreach ($this->admin_dao->getProjectstWithEnabledTimetracking($limit, $offset) as $project_id) {
            if ($user->isMember($project_id["group_id"])) {
                $projects[] = $this->project_manager->getProject($project_id["group_id"]);
            }
        }

        return $projects;
    }

    /**
     * @return PaginatedTimes
     */
    public function getPaginatedTimesForUserInTimePeriodByArtifact(PFUser $user, $start_date, $end_date, $limit, $offset)
    {
        $times = [];
        $matching_times_ids = $this->dao->searchTimesIdsForUserInTimePeriodByArtifact(
            $user->getId(),
            $start_date,
            $end_date,
            $limit,
            $offset
        );

        $total_rows = (int) $this->dao->foundRows();

        foreach ($matching_times_ids as $ids) {
            $times_ids = explode(',', $ids['artifact_times_ids']);

            foreach ($times_ids as $time_id) {
                $times[] = $this->getTimeByIdForUser($user, $time_id);
            }
        }

        return new PaginatedTimes($times, $total_rows);
    }

    /**
     * @return Time
     */
    public function getLastTime(PFUser $user, Tracker_Artifact $artifact)
    {
        return $this->buildTimeFromRow($this->dao->getLastTime($user->getId(), $artifact->getId()));
    }

    /**
     * @return null|Time
     */
    public function getTimeByIdForUser(PFUser $user, $time_id)
    {
        $row = $this->dao->getTimeByIdForUser($user->getId(), $time_id);
        if ($row) {
            return $this->buildTimeFromRow($row);
        }

        return null;
    }

    /**
     * @return Time
     */
    private function buildTimeFromRow($row_time)
    {
        return new Time(
            $row_time['id'],
            $row_time['user_id'],
            $row_time['artifact_id'],
            $row_time['day'],
            $row_time['minutes'],
            $row_time['step']
        );
    }
}
