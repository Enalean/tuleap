<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Permission;

use PFUser;
use Project;
use Project_AccessException;
use Tracker;
use Tracker_FormElement_Field;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\include\CheckUserCanAccessProject;

final readonly class CrossTrackerPermissionGate
{
    public function __construct(private CheckUserCanAccessProject $project_access)
    {
    }

    /**
     * @throws CrossTrackerUnauthorizedException
     */
    public function check(PFUser $user, CrossTrackerReport $report): void
    {
        $this->checkProjectsAuthorization($user, $report->getProjects());
        $this->checkTrackersAuthorization($user, $report->getTrackers());
        $this->checkColumnFieldsAuthorization($user, $report->getColumnFields());
        $this->checkSearchFieldsAuthorization($user, $report->getSearchFields());
    }

    /**
     * @param Project[] $projects
     * @throws CrossTrackerUnauthorizedProjectException
     */
    private function checkProjectsAuthorization(PFUser $user, array $projects): void
    {
        $count_of_authorized_projects = 0;
        foreach ($projects as $project) {
            try {
                $this->project_access->userCanAccessProject($user, $project);
                $count_of_authorized_projects++;
            } catch (Project_AccessException) {
            }
        }

        if (! empty($projects) && $count_of_authorized_projects === 0) {
            throw new CrossTrackerUnauthorizedProjectException();
        }
    }

    /**
     * @param Tracker[] $trackers
     * @throws CrossTrackerUnauthorizedTrackerException
     */
    private function checkTrackersAuthorization(PFUser $user, array $trackers): void
    {
        $count_of_authorized_trackers = 0;
        foreach ($trackers as $tracker) {
            if ($tracker->userCanView($user)) {
                $count_of_authorized_trackers++;
            }
        }

        if (! empty($trackers) && $count_of_authorized_trackers === 0) {
            throw new CrossTrackerUnauthorizedTrackerException();
        }
    }

    /**
     * @param Tracker_FormElement_Field[] $column_fields
     * @throws CrossTrackerUnauthorizedException
     */
    private function checkColumnFieldsAuthorization($user, array $column_fields): void
    {
        $this->checkFieldsAuthorization($user, $column_fields, new CrossTrackerUnauthorizedColumnFieldException());
    }

    /**
     * @param Tracker_FormElement_Field[] $search_fields
     * @throws CrossTrackerUnauthorizedException
     */
    private function checkSearchFieldsAuthorization(PFUser $user, array $search_fields): void
    {
        $this->checkFieldsAuthorization($user, $search_fields, new CrossTrackerUnauthorizedSearchFieldException());
    }

    /**
     * @param Tracker_FormElement_Field[] $fields
     * @throws CrossTrackerUnauthorizedException
     */
    private function checkFieldsAuthorization(PFUser $user, array $fields, CrossTrackerUnauthorizedException $exception_to_throw): void
    {
        $count_of_authorized_fields = 0;
        foreach ($fields as $field) {
            if ($field->userCanRead($user)) {
                $count_of_authorized_fields++;
            }
        }
        if (! empty($fields) && $count_of_authorized_fields === 0) {
            throw $exception_to_throw;
        }
    }
}
