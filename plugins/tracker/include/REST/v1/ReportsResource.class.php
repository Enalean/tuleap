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
use \Tuleap\Tracker\REST\ReportRepresentation;
use \Tracker_ReportFactory;
use \Tracker_ArtifactFactory;
use \Tracker_FormElementFactory;
use \UserManager;
use \PFUser;
use \Tuleap\REST\Header;
use \Tracker_REST_Artifact_ArtifactRepresentationBuilder;
use \Tracker_URLVerification;

/**
 * Wrapper for Tracker Report related REST methods
 */
class ReportsResource {

    const MAX_LIMIT      = 50;
    const DEFAULT_LIMIT  = 10;
    const DEFAULT_OFFSET = 0;
    const DEFAULT_VALUES = '';
    const ALL_VALUES     = 'all';

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_REST_Artifact_ArtifactRepresentationBuilder */
    private $builder;

    public function __construct() {
        $this->artifact_factory = Tracker_ArtifactFactory::instance();
        $this->builder          = new Tracker_REST_Artifact_ArtifactRepresentationBuilder(
            Tracker_FormElementFactory::instance()
        );
    }

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

    /**
     * @url OPTIONS {id}/artifacts
     *
     * @param string $id Id of the report
     */
    protected function optionsArtifacts($id) {
        $user = UserManager::instance()->getCurrentUser();
        $this->getReportById($user, $id);
        Header::allowOptionsGet();
        Header::sendOptionsPaginationHeaders(self::DEFAULT_LIMIT, self::DEFAULT_OFFSET, self::MAX_LIMIT);
    }

    /**
     * Get artifacts
     *
     * Get artifacts matching criteria of a report.
     *
     * <p>
     * By default it does not return the values of the fields for performance reasons.
     * You can ask to include some specific values with the <strong>values</strong> parameter.<br>
     * Eg:
     * <ul>
     *  <li>…?id=123&values=summary,status //add summary and status values
     *  <li>…?id=123&values=all            //add all fields values
     *  <li>…?id=123&values=               //(empty string) do not add any field values
     * </ul>
     * </p>
     *
     * <p>
     *   <strong>/!\</strong> Please note that <strong>only "all" and "" (empty string) are available</strong> for now.
     * </p>
     *
     * @url GET {id}/artifacts
     *
     * @param int    $id      Id of the report
     * @param string $values  Which fields to include in the response. Default is no field values {@from path}{@choice ,all}
     * @param int    $limit   Number of elements displayed per page {@from path}{@min 1}
     * @param int    $offset  Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type Tuleap\Tracker\REST\Artifact\ArtifactRepresentation}
     */
    protected function getArtifacts(
        $id,
        $values = self::DEFAULT_VALUES,
        $limit  = self::DEFAULT_LIMIT,
        $offset = self::DEFAULT_OFFSET
    ) {
        $this->checkLimitValue($limit);

        $user   = UserManager::instance()->getCurrentUser();
        $report = $this->getReportById($user, $id);

        $matching_ids = $report->getMatchingIds();
        if (! $matching_ids['id']) {
            return array();
        }

        $matching_artifact_ids = explode(',', $matching_ids['id']);
        $nb_matching           = count($matching_artifact_ids);
        $slice_matching_ids    = array_slice($matching_artifact_ids, $offset, $limit);
        $with_all_field_values = $values == self::ALL_VALUES;

        Header::allowOptionsGet();
        Header::sendPaginationHeaders(self::DEFAULT_LIMIT, self::DEFAULT_OFFSET, $nb_matching, self::MAX_LIMIT);

        return $this->getListOfArtifactRepresentation(
            $user,
            $slice_matching_ids,
            $with_all_field_values
        );
    }

    /**
     * @return Tuleap\Tracker\REST\Artifact\ArtifactRepresentation[]
     */
    private function getListOfArtifactRepresentation(
        PFUser $user,
        $matching_ids,
        $with_all_field_values

    ) {
        $artifact_factory = Tracker_ArtifactFactory::instance();
        $builder          = new Tracker_REST_Artifact_ArtifactRepresentationBuilder(
            Tracker_FormElementFactory::instance()
        );

        $build_artifact_representation = function ($artifact_id) use (
            $builder,
            $artifact_factory,
            $user,
            $with_all_field_values
        ) {
            $artifact = $artifact_factory->getArtifactById($artifact_id);
            if (! $artifact) {
                return;
            }

            if ($with_all_field_values) {
                return $builder->getArtifactRepresentationWithFieldValues($user, $artifact);
            } else {
                return $builder->getArtifactRepresentation($artifact);
            }
        };

        $list_of_artifact_representation = array_map($build_artifact_representation, $matching_ids);

        return array_filter($list_of_artifact_representation);
    }

    /** @return Tracker_Report */
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

        ProjectAuthorization::userCanAccessProject($user, $tracker->getProject(), new Tracker_URLVerification());

        return $report;
    }

    private function checkLimitValue($limit) {
        if ($limit > self::MAX_LIMIT) {
            throw new LimitOutOfBoundsException(self::MAX_LIMIT);
        }
    }
}
