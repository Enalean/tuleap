<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

use Luracast\Restler\RestException;
use PermissionsManager;
use PFUser;
use Tracker;
use Tracker_Artifact_PossibleParentsRetriever;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_Report;
use Tracker_Report_InvalidRESTCriterionException as InvalidCriteriaException;
use Tracker_Report_REST;
use Tracker_ReportDao;
use Tracker_ReportFactory;
use Tracker_REST_Artifact_ArtifactRepresentationBuilder;
use Tracker_REST_TrackerRestBuilder;
use Tracker_URLVerification;
use TrackerFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Exceptions\LimitOutOfBoundsException;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;
use Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\REST\Artifact\ParentArtifactReference;
use Tuleap\Tracker\REST\ReportRepresentation;
use UserManager;

/**
 * Wrapper for Tracker related REST methods
 */
class TrackersResource extends AuthenticatedResource {

    const MAX_LIMIT            = 1000;
    const DEFAULT_LIMIT        = 100;
    const DEFAULT_OFFSET       = 0;
    const DEFAULT_VALUES       = null;
    const ALL_VALUES           = 'all';
    const DEFAULT_CRITERIA     = '';
    const ORDER_ASC            = 'asc';
    const ORDER_DESC           = 'desc';
    const DEFAULT_EXPERT_QUERY = '';

    /** @var UserManager */
    private $user_manager;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Tracker_ReportFactory */
    private $report_factory;

    /** @var PermissionsManager */
    private $permission_manager;

    /** @var Tracker_Factory */
    private $tracker_factory;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var  ReportArtifactFactory */
    private $report_artifact_factory;

    public function __construct() {
        $this->user_manager             = UserManager::instance();
        $this->formelement_factory      = Tracker_FormElementFactory::instance();
        $this->report_factory           = Tracker_ReportFactory::instance();
        $this->permission_manager       = PermissionsManager::instance();
        $this->tracker_factory          = TrackerFactory::instance();
        $this->tracker_artifact_factory = Tracker_ArtifactFactory::instance();
        $this->report_artifact_factory  = new ReportArtifactFactory(
            $this->tracker_artifact_factory
        );
    }


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
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getId($id) {
        $this->checkAccess();

        $builder = new Tracker_REST_TrackerRestBuilder($this->formelement_factory);
        $user    = $this->user_manager->getCurrentUser();
        $tracker = $this->getTrackerById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $tracker->getProject()
        );

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
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type Tuleap\Tracker\REST\ReportRepresentation}
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getReports($id, $limit = 10, $offset = self::DEFAULT_OFFSET) {
        $this->checkAccess();
        $this->checkLimitValue($limit);

        $user        = $this->user_manager->getCurrentUser();
        $tracker     = $this->getTrackerById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $tracker->getProject()
        );

        $all_reports = $this->report_factory->getReportsByTrackerId($tracker->getId(), $user->getId());

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
     *  <li>For text or number-like fields, the allowed operator is "contains". The value must be a string or number</li>
     *  <li>For list fields (like selectboxes or openlists), the allowed operator is "contains". The value(s) are bind_value_id</li>
     *  <li>For date-like fields, the allowed operators are ["="|"<"|">"|"between"]. Dates must be in ISO date format</li>
     *  <li>Full example: {"title" : "bug", "2458" : {"operator" : "between", "value", ["2014-02-25", "2014-03-25T00:00:00-05:00"]}}</li>
     * </ol>
     * <br><br>
     * Notes on the expert query parameter
     * <ol>
     *  <li>You can use: AND, OR, BETWEEN(), NOW(), IN(), NOT IN(), MYSELF() parenthesis.
     *  <li>The basic form of a property is [field_shortname] = [string]
     *      <br>Example: sprint_name='s1' AND description='desc1'
     *  </li>
     * </ol>
     *
     * @url GET {id}/artifacts
     * @access hybrid
     *
     * @param int    $id             ID of the tracker
     * @param string $values         Which fields to include in the response. Default is no field values {@from path}{@choice ,all}
     * @param int    $limit          Number of elements displayed per page {@from path}{@min 1}
     * @param int    $offset         Position of the first element to display {@from path}{@min 0}
     * @param string $query          JSON object of search criteria properties {@from path}
     * @param string $expert_query   Query with AND, OR, BETWEEN(), NOW(), IN(), NOT IN(), MYSELF(), parenthesis
     *                               and Text, Integer, Float, Date, List fields
     *                               <b>Does not work with query parameter</b> {@from path}
     * @param string $order          By default the artifacts are returned by Artifact ID ASC. Set this parameter to either ASC or DESC
     *                               <b>Does not work with query and expert_query parameters</b> {@from path}{@choice asc,desc}
     *
     * @return array {@type Tuleap\Tracker\REST\Artifact\ArtifactRepresentation}
     * @throws RestException 400
     * @throws RestException 404
     * @throws RestException 403
     */
    public function getArtifacts(
        $id,
        $values       = self::DEFAULT_VALUES,
        $limit        = self::DEFAULT_LIMIT,
        $offset       = self::DEFAULT_OFFSET,
        $query        = self::DEFAULT_CRITERIA,
        $expert_query = self::DEFAULT_EXPERT_QUERY,
        $order        = self::ORDER_ASC
    ) {
        $this->checkAccess();
        $this->checkLimitValue($limit);

        $user          = $this->user_manager->getCurrentUser();
        $valid_tracker = $this->getTrackerById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $valid_tracker->getProject()
        );

        if ($query) {
            $artifacts = $this->getArtifactsMatchingFromCriteria($user, $valid_tracker, $query, $offset, $limit);
        } else if ($expert_query) {
            $artifacts = $this->getArtifactsMatchingFromExpertQuery($user, $valid_tracker, $expert_query, $offset, $limit);
        } else {
            $reverse_order = (bool) (strtolower($order) === self::ORDER_DESC);

            $pagination = $this->tracker_artifact_factory->getPaginatedArtifactsByTrackerId(
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
    private function getArtifactsMatchingFromCriteria(PFUser $user, Tracker $tracker, $query, $offset, $limit)
    {
        $report = new Tracker_Report_REST(
            $user,
            $tracker,
            $this->permission_manager,
            new Tracker_ReportDao(),
            $this->formelement_factory
        );

        try {
            $report->setRESTCriteria($query);
            return $this->getArtifactsMatching($report, $offset, $limit);
        } catch (InvalidCriteriaException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }

    private function getArtifactsMatchingFromExpertQuery(PFUser $user, Tracker $tracker, $query, $offset, $limit)
    {
        $report = new Tracker_Report_REST(
            $user,
            $tracker,
            $this->permission_manager,
            new Tracker_ReportDao(),
            $this->formelement_factory
        );

        $report->setIsInExpertMode(true);
        $report->setExpertQuery(stripslashes($query));

        $this->validateExpertQuery($report);

        return $this->getArtifactsMatching($report, $offset, $limit);
    }

    private function getArtifactsMatching(Tracker_Report_REST $report, $offset, $limit)
    {
        $artifact_collection = $this->report_artifact_factory->getArtifactsMatchingReport($report, $limit, $offset);
        Header::sendPaginationHeaders($limit, $offset, $artifact_collection->getTotalSize(), self::MAX_LIMIT);
        return $artifact_collection->getArtifacts();
    }

    private function validateExpertQuery(Tracker_Report_REST $report)
    {
        try {
            $report->validateExpertQuery();
        } catch (SearchablesDoNotExistException $exception) {
            throw new RestException(
                400,
                $exception->getMessage()
            );
        } catch (SearchablesAreInvalidException $exception) {
            throw new RestException(
                400,
                $exception->getMessage()
            );
        } catch (SyntaxError $exception) {
            throw new RestException(
                400,
                "Error during parsing expert query"
            );
        } catch (LimitSizeIsExceededException $exception) {
            throw new RestException(
                400,
                "The query is considered too complex to be executed by the server. Please simplify it (e.g remove comparisons) to continue."
            );
        }
    }

    /**
     * @return Tuleap\Tracker\REST\Artifact\ArtifactRepresentation[]
     */
    private function getListOfArtifactRepresentation(PFUser $user, $artifacts, $with_all_field_values) {
        $builder = new Tracker_REST_Artifact_ArtifactRepresentationBuilder(
            $this->formelement_factory,
            $this->tracker_artifact_factory,
            new NatureDao()
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
                return $builder->getArtifactRepresentation($user, $artifact);
            }
        };

        $list_of_artifact_representation = array_map($build_artifact_representation, $artifacts);

        return array_values(array_filter($list_of_artifact_representation));
    }

    /**
     * @url OPTIONS {id}/parent_artifacts
     */
    public function optionsParentArtifacts($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get all possible parent artifacts for a given tracker
     *
     * Given a tracker, get all open artifacts of its parent tracker ordered by their artifact id
     * in decreasing order.
     * If the given tracker doesn't have a parent, it throws an error.
     *
     * @url GET {id}/parent_artifacts
     * @access hybrid
     *
     * @param int $id
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return array {@type Tuleap\Tracker\REST\Artifact\ParentArtifactReference}
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getParentArtifacts($id, $limit  = self::DEFAULT_LIMIT, $offset = self::DEFAULT_OFFSET) {
        $this->checkAccess();
        $this->checkLimitValue($limit);

        $user    = $this->user_manager->getCurrentUser();
        $tracker = $this->getTrackerById($user, $id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $tracker->getProject()
        );

        $parent = $this->getParentTracker($user, $tracker);

        $possible_parents_getr                       = new Tracker_Artifact_PossibleParentsRetriever($this->tracker_artifact_factory);
        list($label, $pagination, $display_selector) = $possible_parents_getr->getPossibleArtifactParents($parent, $user, $limit, $offset);

        if ($display_selector) {
            $nb_matching = $pagination->getTotalSize();
            Header::sendPaginationHeaders($limit, $offset, $nb_matching, self::MAX_LIMIT);
            $collection = array();
            foreach ($pagination->getArtifacts() as $artifact) {
                $reference    = new ParentArtifactReference();
                $reference->build($artifact);
                $collection[] = $reference;
            }
            return $collection;
        }
    }

    /**
     * @return Tracker
     * @throws RestException
     */
    private function getTrackerById(\PFUser $user, $id) {
        $tracker = $this->tracker_factory->getTrackerById($id);
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

    private function getParentTracker(\PFUser $user, Tracker $tracker) {
        $parent = $tracker->getParent();

        if (! $parent) {
            throw new RestException(404, 'This tracker has no parent tracker');
        }

        if ($parent->isDeleted()) {
            throw new RestException(404, "This tracker's parent is deleted");
        }

        if (! $parent->userCanView($user)) {
            throw new RestException(403);
        }
        return $parent;
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

