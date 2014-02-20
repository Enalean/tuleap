<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use \Tuleap\REST\ProjectAuthorization;
use \Luracast\Restler\RestException;
use \Tuleap\Tracker\REST\ReportRepresentation;
use \Tracker_ReportFactory;
use \UserManager;
use \Tuleap\REST\Header;

/**
 * Wrapper for Tracker Report related REST methods
 */
class ReportsResource {

    /**
     * @url OPTIONS {id}
     *
     * @param string $id Id of the report
     */
    protected function optionsId($id) {
        $user = UserManager::instance()->getCurrentUser();
        $this->getReportById($user, $id);
        Header::allowOptionsGet();
    }

    /**
     * Get report
     *
     * Get the definition of the given report
     *
     * @url GET {id}
     *
     * @param int $id Id of the report
     *
     * @return Tuleap\Tracker\REST\ReportRepresentation
     */
    protected function getId($id) {
        $user   = UserManager::instance()->getCurrentUser();
        $report = $this->getReportById($user, $id);

        $rest_report = new ReportRepresentation();
        $rest_report->build($report);

        Header::allowOptionsGet();

        return $rest_report;
    }

    private function getReportById(\PFUser $user, $id) {
        $store_in_session = false;
        $report = Tracker_ReportFactory::instance()->getReportById(
            $id,
            $user->getId(),
            $store_in_session
        );

        if (! $report) {
            throw new RestException(404);
        }

        $tracker = $report->getTracker();
        if (! $tracker->userCanView($user)) {
            throw new RestException(403);
        }

        ProjectAuthorization::userCanAccessProject($user, $tracker->getProject());

        return $report;
    }
}