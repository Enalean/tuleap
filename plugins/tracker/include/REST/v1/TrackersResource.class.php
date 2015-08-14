<?php
/**
 * Copyright (c) Enalean, 2013-2014. All Rights Reserved.
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
use Tuleap\REST\AuthenticatedResource;
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
use \Tracker_Report_REST;
use \PermissionsManager;
use \Tracker_ReportDao;
use \Tracker_FormElementFactory                   as FormElementFactory;
use \Tracker_Report_InvalidRESTCriterionException as InvalidCriteriaException;

/**
 * Wrapper for Tracker related REST methods
 */
class TrackersResource extends AuthenticatedResource {

    const MAX_LIMIT        = 50;
    const DEFAULT_LIMIT    = 10;
    const DEFAULT_OFFSET   = 0;
    const DEFAULT_VALUES   = '';
    const ALL_VALUES       = 'all';
    const DEFAULT_CRITERIA = '';
    const ORDER_ASC        = 'asc';
    const ORDER_DESC       = 'desc';

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
    public function optionsId($id) {
        $this->sendAllowHeaderForTracker();
    }

    /**
     * Get tracker
     *
     * Get the definition of the given tracker
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id Id of the tracker
     *
     * @return Tuleap\Tracker\REST\TrackerRepresentation
     */
    public function getId($id) {
        $this->checkAccess();
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
    public function optionsReports($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get all reports of a given tracker
     *
     * All reports the user can see
     *
     * @url GET {id}/tracker_reports
     * @access hybrid
     *
     * @param int $id Id of the tracker
     * @param int $limit  Number of elements displayed per page {@from path}{@min 1}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type Tuleap\Tracker\REST\ReportRepresentation}
     */
    public function getReports($id, $limit = 10, $offset = self::DEFAULT_OFFSET) {
        $this->checkAccess();
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
     * @url OPTIONS {id}/artifacts
     */
    public function optionsArtifacts($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get all artifacts of a given tracker
     *
     * Get all artifacts of a given tracker the user can view
     *<br><br>
     * Notes on the query parameter
     * <ol>
     *  <li>It must be a URL-encoded JSON object</li>
     *  <li>The basic form of a property is [field_id|field_shortname] : [number|string|array(number)]
     *      <br>Example: {"1258" : "bug"} OR {"title" : "bug"}
     *  </li>
     *  <li>The complex form of a property is "field_id" : {"operator" : "operator_name", "value" : [number|string|array(number)]}
     *      <br>Example: {"title" : {"operator" : "contains", "value" : "bug"}}
     *  </li>
     *  <li>For text or number-like fields, the allowed operators are ["contains"]. The value must be a string or number</li>
     *  <li>For select-box-like fields, the allowed operators are ["contains"]. The value(s) are bind_value_id</li>
     *  <li>For date-like fields, the allowed operators are ["="|"<"|">"|"between"]. Dates must be in ISO date format</li>
     *  <li>Full example: {"title" : "bug", "2458" : {"operator" : "between", "value", ["2014-02-25", "2014-03-25T00:00:00-05:00"]}}</li>
     * </ol>
     * 
     * @url GET {id}/artifacts
     * @access hybrid
     *
     * @param int    $id     ID of the tracker
     * @param string $values Which fields to include in the response. Default is no field values {@from path}{@choice ,all}
     * @param int    $limit  Number of elements displayed per page {@from path}{@min 1}
     * @param int    $offset Position of the first element to display {@from path}{@min 0}
     * @param string $query  JSON object of search criteria properties {@from path}
     * @param string $order By default the artifacts are returned by Artifact ID ASC. Set this parameter to either ASC or DESC
     *                      <b>Does not work with the query parameter</b> {@from path}{@choice asc,desc}
     *
     * @return array {@type Tuleap\Tracker\REST\Artifact\ArtifactRepresentation}
     * @throws RestException 400
     */
    public function getArtifacts(
        $id,
        $values = self::DEFAULT_VALUES,
        $limit  = self::DEFAULT_LIMIT,
        $offset = self::DEFAULT_OFFSET,
        $query  = self::DEFAULT_CRITERIA,
        $order  = self::ORDER_ASC
    ) {
        $this->checkAccess();
        $this->checkLimitValue($limit);

        $user          = UserManager::instance()->getCurrentUser();
        $valid_tracker = $this->getTrackerById($user, $id);

        if ($query) {
            $artifacts = $this->getArtifactsMatchingQuery($user, $valid_tracker, $query, $offset, $limit);
        } else {
            $reverse_order = (bool) (strtolower($order) === self::ORDER_DESC);

            $pagination = $this->getTrackerArtifactFactory()->getPaginatedArtifactsByTrackerId(
                $id,
                $limit,
                $offset,
                $reverse_order
            );
            $nb_matching = $pagination->getTotalSize();
            $artifacts   = $pagination->getArtifacts();
            Header::sendPaginationHeaders($limit, $offset, $nb_matching, self::MAX_LIMIT);
        }

        Header::allowOptionsGet();

        $with_all_field_values = ($values == self::ALL_VALUES);
        return $this->getListOfArtifactRepresentation(
            $user,
            $artifacts,
            $with_all_field_values
        );
    }

    /**
     * @throws RestException 400
     */
    private function getArtifactsMatchingQuery(PFUser $user, Tracker $tracker, $query, $offset, $limit) {
        $report = new Tracker_Report_REST(
            $user,
            $tracker,
            PermissionsManager::instance(),
            new Tracker_ReportDao(),
            FormElementFactory::instance()
        );

        try {
            $report->setRESTCriteria($query);
            $matching_ids = $report->getMatchingIds();
        } catch (InvalidCriteriaException $e) {
            throw new RestException(400, $e->getMessage());
        }

        if (! $matching_ids['id']) {
            return array();
        }

        $matching_artifact_ids = explode(',', $matching_ids['id']);
        $slice_matching_ids    = array_slice($matching_artifact_ids, $offset, $limit);

        Header::sendPaginationHeaders($limit, $offset, count($matching_artifact_ids), self::MAX_LIMIT);

        $artifacts = $this->getTrackerArtifactFactory()->getArtifactsByArtifactIdList($slice_matching_ids);
        return array_filter($artifacts);
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

        return array_values(array_filter($list_of_artifact_representation));
    }

    /**
     * @return Tracker
     * @throws RestException
     */
    private function getTrackerById(\PFUser $user, $id) {
        $tracker = TrackerFactory::instance()->getTrackerById($id);
        if ($tracker) {

            if ($tracker->isDeleted()) {
                throw new RestException(404, 'this tracker is deleted');
            }

            if ($tracker->userCanView($user)) {
                ProjectAuthorization::userCanAccessProject($user, $tracker->getProject(), new Tracker_URLVerification());
                return $tracker;
            }
            throw new RestException(403);
        }
        throw new RestException(404);
    }

    /**
     * @return Tracker_ArtifactFactory
     */
    private function getTrackerArtifactFactory() {
        return Tracker_ArtifactFactory::instance();
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

