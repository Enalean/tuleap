<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
use Tracker;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_Semantic_ContributorDao;
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
use Tuleap\CrossTracker\Report\CrossTrackerArtifactReportFactory;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchablesCollectionBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidTermCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\ArtifactLink\ForwardLinkFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\ArtifactLink\ReverseLinkFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\CrossTrackerExpertQueryReportDao;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\FromWhereSearchableVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Date;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Users;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\ListValueExtractor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\AssignedTo;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Description;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Status;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Title;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Between\BetweenComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Equal\EqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\GreaterThan\GreaterThanComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\GreaterThan\GreaterThanOrEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\In\InComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\LesserThan\LesserThanComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\LesserThan\LesserThanOrEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ListValueValidator;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotEqual\NotEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotIn\NotInComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Field\FieldUsageChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\MetadataUsageChecker;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parser;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ArtifactLink\ArtifactLinkTypeChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFormatValidator;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringAllowed;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringForbidden;
use Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SizeValidatorVisitor;
use Tuleap\Tracker\Report\TrackerDuplicateException;
use Tuleap\Tracker\Report\TrackerNotFoundException;
use Tuleap\Tracker\Report\TrackerReportConfig;
use Tuleap\Tracker\Report\TrackerReportConfigDao;
use Tuleap\Tracker\Report\TrackerReportExtractor;
use URLVerification;
use UserManager;

class CrossTrackerReportsResource extends AuthenticatedResource
{
    public const MAX_LIMIT = 50;
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
     * @var TrackerReportExtractor
     */
    private $tracker_extractor;
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
    /** @var InvalidTermCollectorVisitor */
    private $invalid_comparisons_collector;
    private ArtifactRepresentationFactory $representation_factory;

    public function __construct()
    {
        $this->project_manager = ProjectManager::instance();
        $this->user_manager    = UserManager::instance();
        $this->report_factory  = new CrossTrackerReportFactory(
            new CrossTrackerReportDao(),
            TrackerFactory::instance()
        );

        $this->cross_tracker_dao = new CrossTrackerReportDao();
        $this->tracker_extractor = new TrackerReportExtractor(TrackerFactory::instance());

        $report_config = new TrackerReportConfig(
            new TrackerReportConfigDao()
        );

        $parser = new ParserCacheProxy(new Parser());

        $this->validator = new ExpertQueryValidator(
            $parser,
            new SizeValidatorVisitor($report_config->getExpertQueryLimit())
        );

        $date_validator                 = new DateFormatValidator(new EmptyStringForbidden(), DateFormat::DATETIME);
        $list_value_validator           = new ListValueValidator(new EmptyStringAllowed(), $this->user_manager);
        $list_value_validator_not_empty = new ListValueValidator(new EmptyStringForbidden(), $this->user_manager);

        $form_element_factory                = Tracker_FormElementFactory::instance();
        $this->invalid_comparisons_collector = new InvalidTermCollectorVisitor(
            new InvalidSearchableCollectorVisitor(
                new MetadataChecker(
                    new MetadataUsageChecker(
                        $form_element_factory,
                        new Tracker_Semantic_TitleDao(),
                        new Tracker_Semantic_DescriptionDao(),
                        new Tracker_Semantic_StatusDao(),
                        new Tracker_Semantic_ContributorDao()
                    )
                ),
                new FieldUsageChecker($form_element_factory, $form_element_factory),
            ),
            new EqualComparisonChecker($date_validator, $list_value_validator),
            new NotEqualComparisonChecker($date_validator, $list_value_validator),
            new GreaterThanComparisonChecker($date_validator, $list_value_validator),
            new GreaterThanOrEqualComparisonChecker($date_validator, $list_value_validator),
            new LesserThanComparisonChecker($date_validator, $list_value_validator),
            new LesserThanOrEqualComparisonChecker($date_validator, $list_value_validator),
            new BetweenComparisonChecker($date_validator, $list_value_validator),
            new InComparisonChecker($date_validator, $list_value_validator_not_empty),
            new NotInComparisonChecker($date_validator, $list_value_validator_not_empty),
            new ArtifactLinkTypeChecker(
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao(),
                ),
            ),
        );

        $submitted_on_alias_field     = 'tracker_artifact.submitted_on';
        $last_update_date_alias_field = 'last_changeset.submitted_on';
        $submitted_by_alias_field     = 'tracker_artifact.submitted_by';
        $last_update_by_alias_field   = 'last_changeset.submitted_by';

        $date_value_extractor    = new Date\DateValueExtractor();
        $date_time_value_rounder = new DateTimeValueRounder();
        $list_value_extractor    = new ListValueExtractor();
        $artifact_factory        = Tracker_ArtifactFactory::instance();
        $query_builder_visitor   = new QueryBuilderVisitor(
            new FromWhereSearchableVisitor(),
            new Metadata\EqualComparisonFromWhereBuilder(
                new Title\EqualComparisonFromWhereBuilder(),
                new Description\EqualComparisonFromWhereBuilder(),
                new Status\EqualComparisonFromWhereBuilder(),
                new Date\EqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\EqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                ),
                new Users\EqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $this->user_manager,
                    $submitted_by_alias_field
                ),
                new Users\EqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $this->user_manager,
                    $last_update_by_alias_field
                ),
                new Metadata\Semantic\AssignedTo\EqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $this->user_manager
                )
            ),
            new Metadata\NotEqualComparisonFromWhereBuilder(
                new Title\NotEqualComparisonFromWhereBuilder(),
                new Description\NotEqualComparisonFromWhereBuilder(),
                new Status\NotEqualComparisonFromWhereBuilder(),
                new Date\NotEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\NotEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                ),
                new Users\NotEqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $this->user_manager,
                    $submitted_by_alias_field
                ),
                new Users\NotEqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $this->user_manager,
                    $last_update_by_alias_field
                ),
                new AssignedTo\NotEqualComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $this->user_manager
                )
            ),
            new Metadata\GreaterThanComparisonFromWhereBuilder(
                new Date\GreaterThanComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\GreaterThanComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new Metadata\GreaterThanOrEqualComparisonFromWhereBuilder(
                new Date\GreaterThanOrEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\GreaterThanOrEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new Metadata\LesserThanComparisonFromWhereBuilder(
                new Date\LesserThanComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\LesserThanComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new Metadata\LesserThanOrEqualComparisonFromWhereBuilder(
                new Date\LesserThanOrEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\LesserThanOrEqualComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new Metadata\BetweenComparisonFromWhereBuilder(
                new Date\BetweenComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $submitted_on_alias_field
                ),
                new Date\BetweenComparisonFromWhereBuilder(
                    $date_value_extractor,
                    $date_time_value_rounder,
                    $last_update_date_alias_field
                )
            ),
            new Metadata\InComparisonFromWhereBuilder(
                new Users\InComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $this->user_manager,
                    $submitted_by_alias_field
                ),
                new Users\InComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $this->user_manager,
                    $last_update_by_alias_field
                ),
                new AssignedTo\InComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $this->user_manager
                )
            ),
            new Metadata\NotInComparisonFromWhereBuilder(
                new Users\NotInComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $this->user_manager,
                    $submitted_by_alias_field
                ),
                new Users\NotInComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $this->user_manager,
                    $last_update_by_alias_field
                ),
                new AssignedTo\NotInComparisonFromWhereBuilder(
                    $list_value_extractor,
                    $this->user_manager
                )
            ),
            new ReverseLinkFromWhereBuilder($artifact_factory),
            new ForwardLinkFromWhereBuilder($artifact_factory),
            new Field\EqualComparisonFromWhereBuilder(
                $form_element_factory,
                $form_element_factory,
                new Field\Numeric\EqualComparisonFromWhereBuilder()
            ),
            new Field\NotEqualComparisonFromWhereBuilder(),
            new Field\GreaterThanComparisonFromWhereBuilder(),
            new Field\GreaterThanOrEqualComparisonFromWhereBuilder(),
            new Field\LesserThanComparisonFromWhereBuilder(),
            new Field\LesserThanOrEqualComparisonFromWhereBuilder(),
            new Field\BetweenComparisonFromWhereBuilder(),
            new Field\InComparisonFromWhereBuilder(),
            new FIeld\NotInComparisonFromWhereBuilder()
        );

        $this->cross_tracker_artifact_factory = new CrossTrackerArtifactReportFactory(
            new CrossTrackerArtifactReportDao(),
            $artifact_factory,
            $this->validator,
            $query_builder_visitor,
            $parser,
            new CrossTrackerExpertQueryReportDao(),
            $this->invalid_comparisons_collector
        );
        $this->cross_tracker_permission_gate  = new CrossTrackerPermissionGate(new URLVerification());

        $this->query_parser           = new QueryParameterParser(new JsonDecoder());
        $this->representation_factory = new ArtifactRepresentationFactory();
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
     * @throws RestException 404
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
     * @throws RestException 404
     */
    public function getIdContent($id, $query, $limit = self::MAX_LIMIT, $offset = 0): array
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        try {
            $current_user = $this->user_manager->getCurrentUser();
            $report       = $this->getReport($id);
            $trackers     = $this->getTrackersFromRoute($query, $report);
            if (count($trackers) === 0) {
                return ["artifacts" => []];
            }
            $expert_query    = $this->getExpertQueryFromRoute($query, $report);
            $expected_report = new CrossTrackerReport($report->getId(), $expert_query, $trackers);

            $this->checkUserIsAllowedToSeeReport($current_user, $expected_report);

            $artifacts       = $this->cross_tracker_artifact_factory->getArtifactsMatchingReport(
                $expected_report,
                $current_user,
                $limit,
                $offset
            );
            $representations = $this->representation_factory->buildRepresentationsForReport($artifacts, $current_user);
        } catch (CrossTrackerReportNotFoundException $exception) {
            throw new RestException(404, null, ['i18n_error_message' => "Report not found"]);
        } catch (TrackerNotFoundException $exception) {
            throw new RestException(400, null, ['i18n_error_message' => $exception->getMessage()]);
        } catch (TrackerDuplicateException $exception) {
            throw new RestException(400, null, ['i18n_error_message' => $exception->getMessage()]);
        } catch (SyntaxError $exception) {
            throw new RestException(
                400,
                null,
                ['i18n_error_message' => dgettext("tuleap-crosstracker", "Error while parsing the query")]
            );
        } catch (LimitSizeIsExceededException $exception) {
            throw new RestException(
                400,
                null,
                ['i18n_error_message' => dgettext(
                    "tuleap-tracker",
                    "The query is considered too complex to be executed by the server. Please simplify it (e.g remove comparisons) to continue."
                ),
                ]
            );
        } catch (SearchablesDoNotExistException $exception) {
            throw new RestException(400, null, ['i18n_error_message' => $exception->getMessage()]);
        } catch (SearchablesAreInvalidException $exception) {
            throw new RestException(400, null, ['i18n_error_message' => $exception->getMessage()]);
        }

        $this->sendPaginationHeaders($limit, $offset, $representations->getTotalSize());

        return ["artifacts" => $representations->getArtifacts()];
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
     * @param array {@max 25}  $trackers_id  Tracker id to link to report
     * @param string           $expert_query The TQL query saved with the report
     *
     * @status 201
     * @access hybrid
     *
     * @return CrossTrackerReportRepresentation
     *
     * @throws RestException 400
     * @throws RestException 404
     */
    protected function put($id, array $trackers_id, $expert_query = "")
    {
        $this->sendAllowHeaders();

        $current_user = $this->user_manager->getCurrentUser();
        try {
            $trackers = $this->tracker_extractor->extractTrackers($trackers_id);

            $this->checkQueryIsValid($trackers, $expert_query, $current_user);

            $report          = $this->getReport($id);
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

    /**
     * @param Tracker[] $trackers
     * @param $expert_query
     * @throws RestException
     */
    private function checkQueryIsValid(array $trackers, $expert_query, PFUser $user)
    {
        if ($expert_query === '') {
            return;
        }

        try {
            $this->validator->validateExpertQuery(
                $expert_query,
                new InvalidSearchablesCollectionBuilder($this->invalid_comparisons_collector, $trackers, $user)
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
        return CrossTrackerReportRepresentation::fromReport($report);
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    /**
     * @param                    $query
     *
     * @throws RestException 400
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

        return $this->tracker_extractor->extractTrackers($trackers_id);
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

    /**
     *
     * @throws RestException 403
     */
    private function checkUserIsAllowedToSeeReport(PFUser $user, CrossTrackerReport $report): void
    {
        $widget = $this->cross_tracker_dao->searchCrossTrackerWidgetByCrossTrackerReportId($report->getId());
        if ($widget !== null && $widget['dashboard_type'] === 'user' && $widget['user_id'] !== (int) $user->getId()) {
            throw new RestException(403);
        }

        if ($widget !== null && $widget['dashboard_type'] === 'project') {
            $project = $this->project_manager->getProject($widget['project_id']);
            ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
        }

        try {
            $this->cross_tracker_permission_gate->check($user, $report);
        } catch (CrossTrackerUnauthorizedException $ex) {
            throw new RestException(403, null, ['i18n_error_message' => $ex->getMessage()]);
        }
    }

    /**
     * @param $id
     *
     * @return CrossTrackerReport
     * @throws CrossTrackerReportNotFoundException
     * @throws RestException 403
     */
    private function getReport($id)
    {
        $report       = $this->report_factory->getById($id);
        $current_user = $this->user_manager->getCurrentUser();
        $this->checkUserIsAllowedToSeeReport($current_user, $report);

        return $report;
    }
}
