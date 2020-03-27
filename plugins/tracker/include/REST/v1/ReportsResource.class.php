<?php
/**
 * Copyright (c) Enalean, 2013-present. All Rights Reserved.
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

use Tracker_Artifact;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Exceptions\LimitOutOfBoundsException;
use Luracast\Restler\RestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\REST\ReportRepresentation;
use Tracker_ReportFactory;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use UserManager;
use PFUser;
use Tuleap\REST\Header;
use Tracker_URLVerification;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;

/**
 * Wrapper for Tracker Report related REST methods
 */
class ReportsResource extends AuthenticatedResource
{
    public const MAX_LIMIT      = 50;
    public const DEFAULT_LIMIT  = 10;
    public const DEFAULT_OFFSET = 0;
    public const DEFAULT_VALUES = null;
    public const ALL_VALUES     = 'all';

    /** @var ArtifactRepresentationBuilder */
    private $builder;

    /** @var ReportArtifactFactory */
    private $report_artifact_factory;

    public function __construct()
    {
        $artifact_factory = Tracker_ArtifactFactory::instance();
        $this->builder    = new ArtifactRepresentationBuilder(
            Tracker_FormElementFactory::instance(),
            $artifact_factory,
            new NatureDao()
        );
        $this->report_artifact_factory = new ReportArtifactFactory(
            $artifact_factory
        );
    }

    /**
     * @url OPTIONS {id}
     *
     * @param string $id Id of the report
     */
    public function optionsId($id)
    {
        Header::allowOptionsGet();
    }

    /**
     * Get report
     *
     * Get the definition of the given report
     *
     * @url GET {id}
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id Id of the report
     *
     * @return ReportRepresentation
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getId($id)
    {
        $this->checkAccess();
        $user   = UserManager::instance()->getCurrentUser();
        $report = $this->getReportById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $report->getTracker()->getProject()
        );

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
    public function optionsArtifacts($id)
    {
        Header::allowOptionsGet();
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
     * @access hybrid
     * @oauth2-scope read:tracker
     *
     * @param int $id Id of the report
     * @param string $values Which fields to include in the response. Default is no field values {@from path}{@choice ,all}
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type Tuleap\Tracker\REST\Artifact\ArtifactRepresentation}
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getArtifacts(
        $id,
        $values = self::DEFAULT_VALUES,
        $limit = self::DEFAULT_LIMIT,
        $offset = self::DEFAULT_OFFSET
    ) {
        $this->checkAccess();
        $this->checkLimitValue($limit);

        $user   = UserManager::instance()->getCurrentUser();
        $report = $this->getReportById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $report->getTracker()->getProject()
        );

        $artifact_collection = $this->report_artifact_factory->getArtifactsMatchingReport(
            $report,
            $limit,
            $offset
        );

        $with_all_field_values = $values == self::ALL_VALUES;

        Header::allowOptionsGet();
        Header::sendPaginationHeaders($limit, $offset, $artifact_collection->getTotalSize(), self::MAX_LIMIT);

        return $this->getListOfArtifactRepresentation(
            $user,
            $artifact_collection->getArtifacts(),
            $with_all_field_values
        );
    }

    /**
     * @return ArtifactRepresentation[]
     */
    private function getListOfArtifactRepresentation(PFUser $user, $artifacts, $with_all_field_values)
    {
        $builder = $this->builder;

        $build_artifact_representation = function (?Tracker_Artifact $artifact) use (
            $builder,
            $user,
            $with_all_field_values
        ) {
            if (! $artifact || ! $artifact->userCanView($user)) {
                return;
            }

            if ($with_all_field_values) {
                $tracker_representation = new MinimalTrackerRepresentation();
                $tracker_representation->build($artifact->getTracker());

                return $builder->getArtifactRepresentationWithFieldValues($user, $artifact, $tracker_representation);
            } else {
                return $builder->getArtifactRepresentation($user, $artifact);
            }
        };

        $list_of_artifact_representation = array_map($build_artifact_representation, $artifacts);

        return array_values(array_filter($list_of_artifact_representation));
    }

    /** @return \Tracker_Report */
    private function getReportById(\PFUser $user, $id)
    {
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

    private function checkLimitValue($limit)
    {
        if ($limit > self::MAX_LIMIT) {
            throw new LimitOutOfBoundsException(self::MAX_LIMIT);
        }
    }
}
