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

use BaseLanguageFactory;
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
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSelectablesCollectionBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSelectablesCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidTermCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\ArtifactLink\ForwardLinkFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\ArtifactLink\ReverseLinkFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\CrossTrackerExpertQueryReportDao;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\FromWhereSearchableVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\DuckTypedField\DuckTypedFieldChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\ArtifactIdMetadataChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\AssignedToChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\InvalidMetadataChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\MetadataUsageChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\StatusChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\SubmissionDateChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\TextSemanticChecker;
use Tuleap\DB\DBFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parser;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ArtifactLink\ArtifactLinkTypeChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date\DateFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\File\FileFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields\FloatFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Integer\IntegerFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ArtifactSubmitterChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractorForOpenList;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Text\TextFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SizeValidatorVisitor;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
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
    private InvalidSelectablesCollectorVisitor $invalid_selectables_collector;
    private ArtifactRepresentationFactory $representation_factory;

    public function __construct()
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

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

        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = new UgroupLabelConverter(
            $list_field_bind_value_normalizer,
            new \BaseLanguageFactory()
        );
        $bind_labels_extractor            = new CollectionOfNormalizedBindLabelsExtractor(
            $list_field_bind_value_normalizer,
            $ugroup_label_converter
        );

        $form_element_factory                = Tracker_FormElementFactory::instance();
        $duck_typed_field_checker            = new DuckTypedFieldChecker(
            $form_element_factory,
            $form_element_factory,
            new InvalidFieldChecker(
                new FloatFieldChecker(),
                new IntegerFieldChecker(),
                new TextFieldChecker(),
                new DateFieldChecker(),
                new FileFieldChecker(),
                new ListFieldChecker(
                    $list_field_bind_value_normalizer,
                    $bind_labels_extractor,
                    $ugroup_label_converter
                ),
                new ListFieldChecker(
                    $list_field_bind_value_normalizer,
                    new CollectionOfNormalizedBindLabelsExtractorForOpenList(
                        $bind_labels_extractor,
                        new OpenListValueDao(),
                        $list_field_bind_value_normalizer,
                    ),
                    $ugroup_label_converter
                ),
                new ArtifactSubmitterChecker($this->user_manager),
                true,
            ),
            TrackersPermissionsRetriever::build(),
        );
        $this->invalid_comparisons_collector = new InvalidTermCollectorVisitor(
            new InvalidSearchableCollectorVisitor(
                new MetadataChecker(
                    new MetadataUsageChecker(
                        $form_element_factory,
                        new Tracker_Semantic_TitleDao(),
                        new Tracker_Semantic_DescriptionDao(),
                        new Tracker_Semantic_StatusDao(),
                        new Tracker_Semantic_ContributorDao()
                    ),
                    new InvalidMetadataChecker(
                        new TextSemanticChecker(),
                        new StatusChecker(),
                        new AssignedToChecker($this->user_manager),
                        new \Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\ArtifactSubmitterChecker(
                            $this->user_manager
                        ),
                        new SubmissionDateChecker(),
                        new ArtifactIdMetadataChecker(),
                    )
                ),
                $duck_typed_field_checker,
            ),
            new ArtifactLinkTypeChecker(
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao(),
                ),
            )
        );
        $this->invalid_selectables_collector = new InvalidSelectablesCollectorVisitor($duck_typed_field_checker);

        $date_time_value_rounder = new DateTimeValueRounder();
        $artifact_factory        = Tracker_ArtifactFactory::instance();
        $list_from_where_builder = new Field\ListFromWhereBuilder();
        $query_builder_visitor   = new QueryBuilderVisitor(
            new FromWhereSearchableVisitor(),
            new ReverseLinkFromWhereBuilder($artifact_factory),
            new ForwardLinkFromWhereBuilder($artifact_factory),
            new Field\FieldFromWhereBuilder(
                $form_element_factory,
                $form_element_factory,
                new Field\Numeric\NumericFromWhereBuilder(),
                new Field\Text\TextFromWhereBuilder($db),
                new Field\Date\DateFromWhereBuilder($date_time_value_rounder),
                new Field\Datetime\DatetimeFromWhereBuilder($date_time_value_rounder),
                new Field\StaticList\StaticListFromWhereBuilder($list_from_where_builder),
                new Field\UGroupList\UGroupListFromWhereBuilder(
                    new UgroupLabelConverter(new ListFieldBindValueNormalizer(), new BaseLanguageFactory()),
                    $list_from_where_builder,
                ),
                new Field\UserList\UserListFromWhereBuilder($list_from_where_builder),
            ),
            new Metadata\MetadataFromWhereBuilder(
                new Metadata\Semantic\Title\TitleFromWhereBuilder($db),
                new Metadata\Semantic\Description\DescriptionFromWhereBuilder($db),
                new Metadata\Semantic\Status\StatusFromWhereBuilder(),
                new Metadata\Semantic\AssignedTo\AssignedToFromWhereBuilder($this->user_manager),
                new Metadata\AlwaysThereField\Date\DateFromWhereBuilder($date_time_value_rounder),
                new Metadata\AlwaysThereField\Users\UsersFromWhereBuilder($this->user_manager),
                new Metadata\AlwaysThereField\ArtifactId\ArtifactIdFromWhereBuilder(),
                $form_element_factory,
            ),
        );

        $this->cross_tracker_artifact_factory = new CrossTrackerArtifactReportFactory(
            new CrossTrackerArtifactReportDao(),
            $artifact_factory,
            $this->validator,
            $query_builder_visitor,
            $parser,
            new CrossTrackerExpertQueryReportDao(),
            $this->invalid_comparisons_collector,
            $this->invalid_selectables_collector,
        );
        $trackers_permissions_retriever       = TrackersPermissionsRetriever::build();
        $this->cross_tracker_permission_gate  = new CrossTrackerPermissionGate(
            new URLVerification(),
            $trackers_permissions_retriever,
            $trackers_permissions_retriever,
        );

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
            $representation = $this->getReportRepresentation($report, $this->user_manager->getCurrentUser());
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
     * @param int $id Id of the report
     * @param string $query
     *                      With a property "trackers_id" to search artifacts presents in given trackers.
     *                      With a property "expert_query" to customize the search. {@required false}
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
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
                return ['artifacts' => []];
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
            throw new RestException(404, null, ['i18n_error_message' => 'Report not found']);
        } catch (TrackerNotFoundException $exception) {
            throw new RestException(400, null, ['i18n_error_message' => $exception->getMessage()]);
        } catch (TrackerDuplicateException $exception) {
            throw new RestException(400, null, ['i18n_error_message' => $exception->getMessage()]);
        } catch (SyntaxError $exception) {
            throw new RestException(
                400,
                null,
                ['i18n_error_message' => dgettext('tuleap-crosstracker', 'Error while parsing the query')]
            );
        } catch (LimitSizeIsExceededException $exception) {
            throw new RestException(
                400,
                null,
                ['i18n_error_message' => dgettext(
                    'tuleap-tracker',
                    'The query is considered too complex to be executed by the server. Please simplify it (e.g remove comparisons) to continue.'
                ),
                ]
            );
        } catch (SearchablesDoNotExistException | SelectablesDoNotExistException $exception) {
            throw new RestException(400, null, ['i18n_error_message' => $exception->getI18NExceptionMessage()]);
        } catch (SearchablesAreInvalidException | SelectablesAreInvalidException $exception) {
            throw new RestException(400, null, ['i18n_error_message' => $exception->getMessage()]);
        }

        $this->sendPaginationHeaders($limit, $offset, $representations->getTotalSize());

        return ['artifacts' => $representations->getArtifacts()];
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
     * @param string $expert_query The TQL query saved with the report
     *
     * @status 201
     * @access hybrid
     *
     * @return CrossTrackerReportRepresentation
     *
     * @throws RestException 400
     * @throws RestException 404
     */
    protected function put($id, array $trackers_id, $expert_query = '')
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

        return $this->getReportRepresentation($expected_report, $current_user);
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
                new InvalidSearchablesCollectionBuilder($this->invalid_comparisons_collector, $trackers, $user),
                new InvalidSelectablesCollectionBuilder($this->invalid_selectables_collector, $trackers, $user),
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

    private function getReportRepresentation(CrossTrackerReport $report, PFUser $user): CrossTrackerReportRepresentation
    {
        return CrossTrackerReportRepresentation::fromReport($report, $user);
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    /**
     * @param                    $query
     *
     * @return array
     * @throws RestException 400
     *
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
