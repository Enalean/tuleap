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
use \Tuleap\REST\Exceptions\LimitOutOfBoundsException;
use \Luracast\Restler\RestException;
use \Tracker_REST_TrackerRestBuilder;
use \Tuleap\Tracker\REST\ReportRepresentation;
use \Tracker_FormElementFactory;
use \Tracker;
use \TrackerFactory;
use \Tracker_ArtifactFactory;
use \Tracker_ReportFactory;
use \UserManager;
use \Tuleap\REST\Header;
use \Tracker_Report;
use \Tracker_URLVerification;
use \Tracker_REST_Artifact_ArtifactRepresentationBuilder;
use \PFUser;

/**
 * Wrapper for Tracker related REST methods
 */
class TrackersResource {

    const MAX_LIMIT      = 50;
    const DEFAULT_LIMIT  = 10;
    const DEFAULT_OFFSET = 0;
    const DEFAULT_VALUES = '';
    const ALL_VALUES     = 'all';

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
     * @param int $limit  Number of elements displayed per page {@from path}{@min 1}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type Tuleap\Tracker\REST\ReportRepresentation}
     */
    protected function getReports($id, $limit = 10, $offset = self::DEFAULT_OFFSET) {
        $this->checkLimitValue($limit);

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

    /**
     * Get all artifacts of a given tracker
     *
     * Get all artifacts of a given tracker the user can view
     * 
     * @url GET {id}/artifacts
     *
     * @param int    $id Id of the tracker
     * @param string $values  Which fields to include in the response. Default is no field values {@from path}{@choice ,all}
     * @param int    $limit  Number of elements displayed per page {@from path}{@min 1}
     * @param int    $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type Tuleap\Tracker\REST\Artifact\ArtifactRepresentation}
     */
    protected function getArtifacts($id,
        $values = self::DEFAULT_VALUES,
        $limit  = self::DEFAULT_LIMIT,
        $offset = self::DEFAULT_OFFSET
    ) {
        $this->checkLimitValue($limit);

        $user              = UserManager::instance()->getCurrentUser();
        $check_user_access = $this->getTrackerById($user, $id);

        $artifact_factory = Tracker_ArtifactFactory::instance();
        $artifacts        = $artifact_factory->getArtifactsByTrackerId($id);

        $nb_matching      = count($artifacts);
        $artifacts        = array_slice($artifacts, $offset, $limit);
        $with_all_field_values = $values == self::ALL_VALUES;

        Header::allowOptionsGet();
        Header::sendPaginationHeaders($limit, $offset, $nb_matching, self::MAX_LIMIT);

        return $this->getListOfArtifactRepresentation(
            $user,
            $artifacts,
            $with_all_field_values
        );
    }

    /**
     * @return Tuleap\Tracker\REST\Artifact\ArtifactRepresentation[]
     */
    private function getListOfArtifactRepresentation(PFUser $user, $artifacts, $with_all_field_values) {
        $builder = new Tracker_REST_Artifact_ArtifactRepresentationBuilder(
            Tracker_FormElementFactory::instance()
        );

        $build_artifact_representation = function ($artifact) use (
            $builder,
            $user,
            $with_all_field_values
        ) {
            if (! $artifact || ! $artifact->userCanView($user)) {
                return;
            }

            if ($with_all_field_values) {
                return $builder->getArtifactRepresentationWithFieldValues($user, $artifact);
            } else {
                return $builder->getArtifactRepresentation($artifact);
            }
        };

        $list_of_artifact_representation = array_map($build_artifact_representation, $artifacts);

        return array_filter($list_of_artifact_representation);
    }

    /**
     * @return Tracker
     * @throws RestException
     */
    private function getTrackerById(\PFUser $user, $id) {
        $tracker = TrackerFactory::instance()->getTrackerById($id);
        if ($tracker) {
            if ($tracker->userCanView($user)) {
                ProjectAuthorization::userCanAccessProject($user, $tracker->getProject(), new Tracker_URLVerification());
                return $tracker;
            }
            throw new RestException(403);
        }
        throw new RestException(404);
    }

    private function sendAllowHeaderForTracker() {
        Header::allowOptionsGet();
    }

    private function checkLimitValue($limit) {
        if ($limit > self::MAX_LIMIT) {
            throw new LimitOutOfBoundsException(self::MAX_LIMIT);
        }
    }
}

