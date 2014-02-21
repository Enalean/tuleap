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
use \Tracker_REST_TrackerRestBuilder;
use \Tuleap\Tracker\REST\ReportRepresentation;
use \Tracker_FormElementFactory;
use \TrackerFactory;
use \Tracker_ReportFactory;
use \UserManager;
use \Tuleap\REST\Header;
use \Tracker_Report;

/**
 * Wrapper for Tracker related REST methods
 */
class TrackersResource {

    const MAX_LIMIT      = 50;
    const DEFAULT_LIMIT  = 10;
    const DEFAULT_OFFSET = 0;

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptions();
    }

    /**
     * @url OPTIONS {id}
     *
     * @param string $id Id of the tracker
     */
    protected function optionsId($id) {
        $user    = UserManager::instance()->getCurrentUser();
        $this->getTrackerById($user, $id);
        $this->sendAllowHeaderForTracker();
    }

    /**
     * Get tracker
     *
     * Get the definition of the given tracker
     *
     * @url GET {id}
     *
     * @param int $id Id of the tracker
     *
     * @return Tuleap\Tracker\REST\TrackerRepresentation
     */
    protected function getId($id) {
        $builder = new Tracker_REST_TrackerRestBuilder(Tracker_FormElementFactory::instance());
        $user    = UserManager::instance()->getCurrentUser();
        $tracker = $this->getTrackerById($user, $id);
        $this->sendAllowHeaderForTracker();

        return $builder->getTrackerRepresentation($user, $tracker);
    }

    /**
     * @url OPTIONS {id}/tracker_reports
     *
     * @param string $id Id of the tracker
     */
    protected function optionsReports($id) {
        $user    = UserManager::instance()->getCurrentUser();
        $this->getTrackerById($user, $id);

        Header::allowOptionsGet();
        Header::sendOptionsPaginationHeaders(self::DEFAULT_LIMIT, self::DEFAULT_OFFSET, self::MAX_LIMIT);
    }

    /**
     * Get all reports of a given tracker
     *
     * All reports the user can see
     *
     * @url GET {id}/tracker_reports
     *
     * @param int $id Id of the tracker
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\Tracker\REST\ReportRepresentation}
     */
    protected function getReports($id, $limit = self::DEFAULT_LIMIT, $offset = self::DEFAULT_OFFSET) {
        if (! $this->limitValueIsAcceptable($limit)) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }

        $user    = UserManager::instance()->getCurrentUser();
        $tracker = $this->getTrackerById($user, $id);
        $all_reports = Tracker_ReportFactory::instance()->getReportsByTrackerId($tracker->getId(), $user->getId());

        $nb_of_reports = count($all_reports);
        $reports = array_slice($all_reports, $offset, $limit);

        Header::allowOptionsGet();
        Header::sendPaginationHeaders($limit, $offset, $nb_of_reports, self::MAX_LIMIT);

        return array_map(
            function (Tracker_Report $report) {
                $rest_report = new ReportRepresentation();
                $rest_report->build($report);

                return $rest_report;
            },
            $reports
        );
    }

    private function getTrackerById(\PFUser $user, $id) {
        $tracker = TrackerFactory::instance()->getTrackerById($id);
        if ($tracker) {
            if ($tracker->userCanView($user)) {
                ProjectAuthorization::userCanAccessProject($user, $tracker->getProject());
                return $tracker;
            }
            throw new RestException(403);
        }
        throw new RestException(404);
    }

    private function sendAllowHeaderForTracker() {
        Header::allowOptionsGet();
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }
}

