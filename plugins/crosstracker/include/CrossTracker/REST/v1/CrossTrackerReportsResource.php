<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\REST\v1;

use Exception;
use Luracast\Restler\RestException;
use PFUser;
use ProjectManager;
use Tracker_Semantic_DescriptionDao;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_TitleDao;
use TrackerFactory;
use Tuleap\CrossTracker\CrossTrackerArtifactReportDao;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\CrossTrackerReportDao;
use Tuleap\CrossTracker\CrossTrackerReportFactory;
use Tuleap\CrossTracker\CrossTrackerReportNotFoundException;
use Tuleap\CrossTracker\Permission\CrossTrackerPermissionGate;
use Tuleap\CrossTracker\Permission\CrossTrackerUnauthorizedException;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidComparisonCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchablesCollectionBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic\EqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic\MetadataChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic\NotEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic\SemanticUsageChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\CrossTrackerExpertQueryReportDao;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\SearchableVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Description;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\EqualComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\NotEqualComparisonFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Status;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Title;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\SubmittedOn;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilderVisitor;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parser;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;
use Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SizeValidatorVisitor;
use Tuleap\Tracker\Report\TrackerReportConfig;
use Tuleap\Tracker\Report\TrackerReportConfigDao;
use URLVerification;
use UserManager;

class CrossTrackerReportsResource extends AuthenticatedResource
{
    const MAX_LIMIT = 50;
    /**
     * @var QueryParameterParser
     */
    private $query_parser;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var CrossTrackerArtifactReportFactory
     */
    private $cross_tracker_artifact_factory;
    /**
     * @var CrossTrackerReportExtractor
     */
    private $cross_tracker_extractor;
    /**
     * @var CrossTrackerReportDao
     */
    private $cross_tracker_dao;
    /**
     * @var CrossTrackerReportFactory
     */
    private $report_factory;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var CrossTrackerPermissionGate
     */
    private $cross_tracker_permission_gate;
    /** @var ExpertQueryValidator */
    private $validator;
    /** @var InvalidComparisonCollectorVisitor */
    private $invalid_comparisons_collector;

    public function __construct()
    {
        $this->project_manager = ProjectManager::instance();
        $this->user_manager    = UserManager::instance();
        $this->report_factory  = new CrossTrackerReportFactory(
            new CrossTrackerReportDao(),
            TrackerFactory::instance()
        );

        $this->cross_tracker_dao              = new CrossTrackerReportDao();
        $this->cross_tracker_extractor        = new CrossTrackerReportExtractor(TrackerFactory::instance());

        $report_config = new TrackerReportConfig(
            new TrackerReportConfigDao()
        );

        $parser = new ParserCacheProxy(new Parser());

        $this->validator = new ExpertQueryValidator(
            $parser,
            new SizeValidatorVisitor($report_config->getExpertQueryLimit())
        );

        $this->invalid_comparisons_collector = new InvalidComparisonCollectorVisitor(
            new InvalidSearchableCollectorVisitor(),
            new MetadataChecker(
                new SemanticUsageChecker(
                    new Tracker_Semantic_TitleDao(),
                    new Tracker_Semantic_DescriptionDao(),
                    new Tracker_Semantic_StatusDao()
                )
            ),
            new EqualComparisonChecker(),
            new NotEqualComparisonChecker()
        );

        $query_builder_visitor = new QueryBuilderVisitor(
            new SearchableVisitor(),
            new EqualComparisonFromWhereBuilder(
                new Title\EqualComparisonFromWhereBuilder(),
                new Description\EqualComparisonFromWhereBuilder(),
                new Status\EqualComparisonFromWhereBuilder(),
                new SubmittedOn\EqualComparisonFromWhereBuilder(
                    new DateTimeValueRounder()
                )
            ),
            new NotEqualComparisonFromWhereBuilder(
                new Title\NotEqualComparisonFromWhereBuilder(),
                new Description\NotEqualComparisonFromWhereBuilder(),
                new Status\NotEqualComparisonFromWhereBuilder(),
                new SubmittedOn\NotEqualComparisonFromWhereBuilder(
                    new DateTimeValueRounder()
                )
            )
        );

        $this->cross_tracker_artifact_factory = new CrossTrackerArtifactReportFactory(
            new CrossTrackerArtifactReportDao(),
            \Tracker_ArtifactFactory::instance(),
            $this->validator,
            $query_builder_visitor,
            $parser,
            new CrossTrackerExpertQueryReportDao(),
            $this->invalid_comparisons_collector
        );
        $this->cross_tracker_permission_gate  = new CrossTrackerPermissionGate(new URLVerification());

        $this->query_parser = new QueryParameterParser(new JsonDecoder());
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the report
     */
    public function optionsId($id)
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get cross tracker report
     *
     * Get the definition of the given report id
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id Id of the report
     *
     * @return CrossTrackerReportRepresentation
     *
     * @throws 404
     */
    public function getId($id)
    {
        $this->checkAccess();
        try {
            $report         = $this->getReport($id);
            $representation = $this->getReportRepresentation($report);
        } catch (CrossTrackerReportNotFoundException $exception) {
            throw new RestException(404, "Report $id not found");
        }

        $this->sendAllowHeaders();

        return $representation;
    }

    /**
     * Get cross artifacts linked to tracker report
     *
     * <pre>/!\ route under construction</pre>
     * Get open artifacts linked to given trackers.
     * <br>
     * <br>
     * ?query is optional. When filled, it is a json object:
     * <ul>
     *   <li>With a property "trackers_id" to search artifacts presents in given trackers.</li>
     *   <li>With a property "expert_query" to customize the search.
     *     Example: <pre>{"trackers_id": [1,2,3],"expert_query": "@title = 'Critical'"}</pre>
     *   </li>
     * </ul>
     * @url GET {id}/content
     * @access hybrid
     *
     * @param int    $id Id of the report
     * @param string $query
     *                      With a property "trackers_id" to search artifacts presents in given trackers.
     *                      With a property "expert_query" to customize the search. {@required false}
     * @param int    $limit Number of elements displayed per page {@from path}{@min 1}{@max 50}
     * @param int    $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return CrossTrackerArtifactReportRepresentation[]
     *
     * @throws 404
     */
    public function getIdContent($id, $query, $limit = self::MAX_LIMIT, $offset = 0)
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        try {
            $current_user    = $this->user_manager->getCurrentUser();
            $report          = $this->getReport($id);
            $trackers        = $this->getTrackersFromRoute($query, $report);
            $expert_query    = $this->getExpertQueryFromRoute($query, $report);
            $expected_report = new CrossTrackerReport($report->getId(), $expert_query, $trackers);

            $this->checkUserIsAllowedToSeeReport($current_user, $expected_report);

            $artifacts = $this->cross_tracker_artifact_factory->getArtifactsMatchingReport(
                $expected_report,
                $current_user,
                $limit,
                $offset
            );
        } catch (CrossTrackerReportNotFoundException $exception) {
            throw new RestException(404, "Report not found");
        } catch (TrackerNotFoundException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (TrackerDuplicateException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (SyntaxError $exception) {
            throw new RestException(
                400,
                null,
                array('i18n_error_message' => dgettext("tuleap-crosstracker", "Error while parsing the query"))
            );
        } catch (LimitSizeIsExceededException $exception) {
            throw new RestException(
                400,
                null,
                array('i18n_error_message' => dgettext(
                    "tuleap-tracker",
                    "The query is considered too complex to be executed by the server. Please simplify it (e.g remove comparisons) to continue."
                ))
            );
        } catch (SearchablesDoNotExistException $exception) {
            throw new RestException(400, null, array('i18n_error_message' => $exception->getMessage()));
        } catch (SearchablesAreInvalidException $exception) {
            throw new RestException(400, null, array('i18n_error_message' => $exception->getMessage()));
        }

        $this->sendPaginationHeaders($limit, $offset, $artifacts->getTotalSize());

        return array("artifacts" => $artifacts->getArtifacts());
    }

    /**
     * Update a cross tracker report
     *
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"trackers_id": [1, 2, 3],<br>
     *   &nbsp;"expert_query": ""<br>
     * }<br>
     * </pre>
     *
     * @url PUT {id}
     *
     * @param int $id Id of the report
     * @param array {@max 10}  $trackers_id  Tracker id to link to report
     * @param string           $expert_query The TQL query saved with the report
     *
     * @status 201
     * @access hybrid
     *
     * @return CrossTrackerReportRepresentation
     *
     * @throws 400
     * @throws 404
     */
    protected function put($id, array $trackers_id, $expert_query = "")
    {
        $this->sendAllowHeaders();

        $current_user = $this->user_manager->getCurrentUser();
        try {
            $this->checkQueryIsValid($trackers_id, $expert_query);

            $report          = $this->getReport($id);
            $trackers        = $this->cross_tracker_extractor->extractTrackers($trackers_id);
            $expected_report = new CrossTrackerReport($report->getId(), $expert_query, $trackers);

            $this->checkUserIsAllowedToSeeReport($current_user, $expected_report);

            $this->cross_tracker_dao->updateReport($id, $trackers, $expert_query);
        } catch (CrossTrackerReportNotFoundException $exception) {
            throw new RestException(404, "Report $id not found");
        } catch (TrackerNotFoundException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (TrackerDuplicateException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        return $this->getReportRepresentation($expected_report);
    }

    private function checkQueryIsValid(array $trackers_id, $expert_query)
    {
        if ($expert_query === '') {
            return;
        }

        try {
            $this->validator->validateExpertQuery(
                $expert_query,
                new InvalidSearchablesCollectionBuilder($this->invalid_comparisons_collector, $trackers_id)
            );
        } catch (SearchablesDoNotExistException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (SearchablesAreInvalidException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (SyntaxError $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (LimitSizeIsExceededException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGetPut();
    }

    /**
     * @param $report
     *
     * @return CrossTrackerReportRepresentation
     */
    private function getReportRepresentation($report)
    {
        $representation = new CrossTrackerReportRepresentation();
        $representation->build($report);

        return $representation;
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    /**
     * @param                    $query
     * @param CrossTrackerReport $report
     *
     * @return array
     */
    private function getTrackersFromRoute($query, CrossTrackerReport $report)
    {
        $query = trim($query);
        if (empty($query)) {
            return $report->getTrackers();
        }

        try {
            $trackers_id = $this->query_parser->getArrayOfInt($query, 'trackers_id');
        } catch (QueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        return $this->cross_tracker_extractor->extractTrackers($trackers_id);
    }

    private function getExpertQueryFromRoute($query_parameter, CrossTrackerReport $report)
    {
        $query_parameter = trim($query_parameter);
        if (empty($query_parameter)) {
            return $report->getExpertQuery();
        }

        try {
            return $this->query_parser->getString($query_parameter, 'expert_query');
        } catch (QueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function checkUserIsAllowedToSeeReport(PFUser $user, CrossTrackerReport $report)
    {
        $widget = $this->cross_tracker_dao->searchCrossTrackerWidgetByCrossTrackerReportId($report->getId());
        if ($widget['dashboard_type'] === 'user' && $widget['user_id'] !== (int) $user->getId()) {
            throw new RestException(403);
        }

        if ($widget['dashboard_type'] === 'project') {
            $project = $this->project_manager->getProject($widget['project_id']);
            ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
        }

        try {
            $this->cross_tracker_permission_gate->check($user, $report);
        } catch (CrossTrackerUnauthorizedException $ex) {
            throw new RestException(403, null, array('i18n_error_message' => $ex->getMessage()));
        }
    }

    /**
     * @param $id
     *
     * @return CrossTrackerReport
     */
    private function getReport($id)
    {
        $report       = $this->report_factory->getById($id);
        $current_user = $this->user_manager->getCurrentUser();
        $this->checkUserIsAllowedToSeeReport($current_user, $report);

        return $report;
    }
}
