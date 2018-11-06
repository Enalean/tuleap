<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Permission;

use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\project\ProjectAccessSuspendedException;

class CrossTrackerPermissionGate
{
    /**
     * @var \URLVerification
     */
    private $url_verification;

    public function __construct(\URLVerification $url_verification)
    {
        $this->url_verification = $url_verification;
    }

    /**
     * @throws CrossTrackerUnauthorizedProjectException
     * @throws CrossTrackerUnauthorizedTrackerException
     * @throws ProjectAccessSuspendedException
     */
    public function check(\PFUser $user, CrossTrackerReport $report)
    {
        $this->checkProjectsAuthorization($user, $report->getProjects());
        $this->checkTrackersAuthorization($user, $report->getTrackers());
        $this->checkColumnFieldsAuthorization($user, $report->getColumnFields());
        $this->checkSearchFieldsAuthorization($user, $report->getSearchFields());
    }

    /**
     * @param \PFUser $user
     * @param array   $projects
     *
     * @throws CrossTrackerUnauthorizedProjectException
     * @throws ProjectAccessSuspendedException
     */
    private function checkProjectsAuthorization(\PFUser $user, array $projects)
    {
        /** @var \Project $project */
        foreach ($projects as $project) {
            try {
                $this->url_verification->userCanAccessProject($user, $project);
            } catch (ProjectAccessSuspendedException $exception) {
                throw $exception;
            } catch (\Project_AccessException $ex) {
                throw new CrossTrackerUnauthorizedProjectException();
            }
        }
    }

    /**
     * @throws CrossTrackerUnauthorizedTrackerException
     */
    private function checkTrackersAuthorization(\PFUser $user, array $trackers)
    {
        /** @var \Tracker $tracker */
        foreach ($trackers as $tracker) {
            if (! $tracker->userCanView($user)) {
                throw new CrossTrackerUnauthorizedTrackerException();
            }
        }
    }

    private function checkColumnFieldsAuthorization($user, array $column_fields)
    {
        $this->checkFieldsAuthorization($user, $column_fields, new CrossTrackerUnauthorizedColumnFieldException());
    }

    private function checkSearchFieldsAuthorization(\PFUser $user, array $search_fields)
    {
        $this->checkFieldsAuthorization($user, $search_fields, new CrossTrackerUnauthorizedSearchFieldException());
    }

    private function checkFieldsAuthorization(\PFUser $user, array $fields, CrossTrackerUnauthorizedException $exception_to_throw)
    {
        /** @var \Tracker_FormElement_Field $field */
        foreach ($fields as $field) {
            if (! $field->userCanRead($user)) {
                throw $exception_to_throw;
            }
        }
    }
}
