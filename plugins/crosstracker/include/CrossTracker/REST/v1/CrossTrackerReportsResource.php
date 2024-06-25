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

declare(strict_types=1);

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
use Tuleap\CrossTracker\Report\CrossTrackerArtifactReportFactory;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
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
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Date\DateResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\FieldResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Numeric\NumericResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\StaticList\StaticListResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Text\TextResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Date\DateSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\FieldSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Numeric\NumericSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\StaticList\StaticListSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Text\TextSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\UGroupList\UGroupListSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\UserList\UserListSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilderVisitor;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerReportContentRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\LegacyCrossTrackerReportContentRepresentation;
use Tuleap\DB\DBFactory;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
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
use Tuleap\Tracker\Report\Query\Advanced\SelectablesMustBeUniqueException;
use Tuleap\Tracker\Report\Query\Advanced\SizeValidatorVisitor;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Report\TrackerDuplicateException;
use Tuleap\Tracker\Report\TrackerNotFoundException;
use Tuleap\Tracker\Report\TrackerReportConfig;
use Tuleap\Tracker\Report\TrackerReportConfigDao;
use Tuleap\Tracker\Report\TrackerReportExtractor;
use Tuleap\Tracker\REST\v1\ArtifactMatchingReportCollection;
use URLVerification;
use UserManager;

final class CrossTrackerReportsResource extends AuthenticatedResource
{
    public const MAX_LIMIT          = 50;
    private const FORMAT_SELECTABLE = 'selectable';

    private UserManager $user_manager;

    public function __construct()
    {
        $this->user_manager = UserManager::instance();
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the report
     */
    public function optionsId(int $id): void
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
     *
     * @throws RestException 404
     */
    public function getId(int $id): CrossTrackerReportRepresentation
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
     * @param int $id id of the report
     * @param string|null $query
     *                      With a property "trackers_id" to search artifacts presents in given trackers.
     *                      With a property "expert_query" to customize the search. {@required false}
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     * @param string $return_format Format of the returned payload. Default is 'selectable' {@from path}{@choice selectable,static}
     *
     * @throws RestException 404
     */
    public function getIdContent(int $id, ?string $query, int $limit = self::MAX_LIMIT, int $offset = 0, string $return_format = self::FORMAT_SELECTABLE): LegacyCrossTrackerReportContentRepresentation|CrossTrackerReportContentRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        try {
            $current_user = $this->user_manager->getCurrentUser();
            $report       = $this->getReport($id);

            $query_parser = new QueryParameterParser(new JsonDecoder());

            $trackers = $this->getTrackersFromRoute($query, $report, $query_parser);
            if (count($trackers) === 0) {
                if ($return_format === self::FORMAT_SELECTABLE) {
                    return new CrossTrackerReportContentRepresentation([], [], 0);
                }
                return new LegacyCrossTrackerReportContentRepresentation([]);
            }
            $expert_query    = $this->getExpertQueryFromRoute($query, $report, $query_parser);
            $expected_report = new CrossTrackerReport($report->getId(), $expert_query, $trackers);

            $this->getUserIsAllowedToSeeReportChecker()->checkUserIsAllowedToSeeReport($current_user, $expected_report);

            $form_element_factory     = Tracker_FormElementFactory::instance();
            $tracker_artifact_factory = Tracker_ArtifactFactory::instance();

            $retrieve_field_type    = new FieldTypeRetrieverWrapper($form_element_factory);
            $trackers_permissions   = TrackersPermissionsRetriever::build();
            $select_builder_visitor = new SelectBuilderVisitor(
                new FieldSelectFromBuilder(
                    $form_element_factory,
                    $retrieve_field_type,
                    $trackers_permissions,
                    new DateSelectFromBuilder(),
                    new TextSelectFromBuilder(),
                    new NumericSelectFromBuilder(),
                    new StaticListSelectFromBuilder(),
                    new UGroupListSelectFromBuilder(),
                    new UserListSelectFromBuilder()
                ),
            );
            $purifier               = \Codendi_HTMLPurifier::instance();
            $result_builder_visitor = new ResultBuilderVisitor(
                new FieldResultBuilder(
                    $form_element_factory,
                    $retrieve_field_type,
                    $trackers_permissions,
                    new DateResultBuilder(
                        $tracker_artifact_factory,
                        $form_element_factory,
                    ),
                    new TextResultBuilder(
                        $tracker_artifact_factory,
                        new TextValueInterpreter(
                            $purifier,
                            CommonMarkInterpreter::build(
                                $purifier,
                            ),
                        ),
                    ),
                    new NumericResultBuilder(),
                    new StaticListResultBuilder(),
                ),
            );

            $artifact_factory               = Tracker_ArtifactFactory::instance();
            $query_builder_visitor          = $this->getQueryBuilderVisitor();
            $cross_tracker_artifact_factory = new CrossTrackerArtifactReportFactory(
                new CrossTrackerArtifactReportDao(),
                $artifact_factory,
                $this->getExpertQueryValidator(),
                $query_builder_visitor,
                $select_builder_visitor,
                $result_builder_visitor,
                new ParserCacheProxy(new Parser()),
                new CrossTrackerExpertQueryReportDao(),
                $this->getInvalidComparisonsCollector(),
                new InvalidSelectablesCollectorVisitor($this->getDuckTypedFieldChecker()),
            );

            $artifacts = $cross_tracker_artifact_factory->getArtifactsMatchingReport(
                $expected_report,
                $current_user,
                $limit,
                $offset,
                $return_format !== self::FORMAT_SELECTABLE,
            );

            if ($return_format === self::FORMAT_SELECTABLE) {
                assert($artifacts instanceof CrossTrackerReportContentRepresentation);
                $this->sendPaginationHeaders($limit, $offset, $artifacts->getTotalSize());
                return $artifacts;
            } else {
                assert($artifacts instanceof ArtifactMatchingReportCollection);
                $representations = (new ArtifactRepresentationFactory())->buildRepresentationsForReport(
                    $artifacts,
                    $current_user
                );

                $this->sendPaginationHeaders($limit, $offset, $representations->getTotalSize());

                return new LegacyCrossTrackerReportContentRepresentation($representations->getArtifacts());
            }
        } catch (CrossTrackerReportNotFoundException $exception) {
            throw new RestException(404, null, ['i18n_error_message' => 'Report not found']);
        } catch (TrackerNotFoundException | TrackerDuplicateException $exception) {
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
        } catch (SearchablesDoNotExistException | SelectablesDoNotExistException | SelectablesMustBeUniqueException $exception) {
            throw new RestException(400, null, ['i18n_error_message' => $exception->getI18NExceptionMessage()]);
        } catch (SearchablesAreInvalidException | SelectablesAreInvalidException $exception) {
            throw new RestException(400, null, ['i18n_error_message' => $exception->getMessage()]);
        }
    }

    private function getQueryBuilderVisitor(): QueryBuilderVisitor
    {
        $artifact_factory        = Tracker_ArtifactFactory::instance();
        $form_element_factory    = Tracker_FormElementFactory::instance();
        $db                      = DBFactory::getMainTuleapDBConnection()->getDB();
        $date_time_value_rounder = new DateTimeValueRounder();
        $list_from_where_builder = new Field\ListFromWhereBuilder();

        return new QueryBuilderVisitor(
            new FromWhereSearchableVisitor(),
            new ReverseLinkFromWhereBuilder($artifact_factory),
            new ForwardLinkFromWhereBuilder($artifact_factory),
            new Field\FieldFromWhereBuilder(
                $form_element_factory,
                new FieldTypeRetrieverWrapper($form_element_factory),
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
     *
     * @throws RestException 400
     * @throws RestException 404
     */
    protected function put(int $id, array $trackers_id, string $expert_query = ''): CrossTrackerReportRepresentation
    {
        $this->sendAllowHeaders();

        $current_user = $this->user_manager->getCurrentUser();
        try {
            $tracker_extractor = new TrackerReportExtractor(TrackerFactory::instance());
            $trackers          = $tracker_extractor->extractTrackers($trackers_id);

            $this->checkQueryIsValid($trackers, $expert_query, $current_user);

            $report          = $this->getReport($id);
            $expected_report = new CrossTrackerReport($report->getId(), $expert_query, $trackers);

            $this->getUserIsAllowedToSeeReportChecker()->checkUserIsAllowedToSeeReport($current_user, $expected_report);

            $this->getCrossTrackerDao()->updateReport($id, $trackers, $expert_query);
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
     * @throws RestException
     */
    private function checkQueryIsValid(array $trackers, string $expert_query, PFUser $user)
    {
        if ($expert_query === '') {
            return;
        }

        try {
            $this->getExpertQueryValidator()->validateExpertQuery(
                $expert_query,
                new InvalidSearchablesCollectionBuilder($this->getInvalidComparisonsCollector(), $trackers, $user),
                new InvalidSelectablesCollectionBuilder(
                    new InvalidSelectablesCollectorVisitor($this->getDuckTypedFieldChecker()),
                    $trackers,
                    $user
                ),
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

    private function sendAllowHeaders(): void
    {
        Header::allowOptionsGetPut();
    }

    private function getReportRepresentation(CrossTrackerReport $report, PFUser $user): CrossTrackerReportRepresentation
    {
        return CrossTrackerReportRepresentation::fromReport($report, $user);
    }

    private function sendPaginationHeaders($limit, $offset, $size): void
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    /**
     *
     * @return Tracker[]
     * @throws RestException 400
     *
     */
    private function getTrackersFromRoute(
        ?string $query,
        CrossTrackerReport $report,
        QueryParameterParser $query_parser,
    ): array {
        if ($query === null || $query === '') {
            return $report->getTrackers();
        }

        $query = trim($query);

        try {
            $trackers_id = $query_parser->getArrayOfInt($query, 'trackers_id');
        } catch (QueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
        $tracker_extractor = new TrackerReportExtractor(TrackerFactory::instance());
        return $tracker_extractor->extractTrackers($trackers_id);
    }

    private function getExpertQueryFromRoute(
        ?string $query_parameter,
        CrossTrackerReport $report,
        QueryParameterParser $query_parser,
    ): string {
        if ($query_parameter === null || $query_parameter === '') {
            return $report->getExpertQuery();
        }
        $query_parameter = trim($query_parameter);

        try {
            return $query_parser->getString($query_parameter, 'expert_query');
        } catch (QueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function getCrossTrackerDao(): CrossTrackerReportDao
    {
        return new CrossTrackerReportDao();
    }

    private function getUserIsAllowedToSeeReportChecker(): UserIsAllowedToSeeReportChecker
    {
        $trackers_permissions_retriever = TrackersPermissionsRetriever::build();
        $cross_tracker_permission_gate  = new CrossTrackerPermissionGate(
            new URLVerification(),
            $trackers_permissions_retriever,
            $trackers_permissions_retriever,
        );

        return new UserIsAllowedToSeeReportChecker(
            $this->getCrossTrackerDao(),
            ProjectManager::instance(),
            new URLVerification(),
            $cross_tracker_permission_gate
        );
    }

    /**
     * @throws CrossTrackerReportNotFoundException
     * @throws RestException 403
     */
    private function getReport(int $id): CrossTrackerReport
    {
        $report_factory = new CrossTrackerReportFactory(
            new CrossTrackerReportDao(),
            TrackerFactory::instance()
        );

        $report       = $report_factory->getById($id);
        $current_user = $this->user_manager->getCurrentUser();
        $this->getUserIsAllowedToSeeReportChecker()->checkUserIsAllowedToSeeReport($current_user, $report);

        return $report;
    }

    private function getDuckTypedFieldChecker(): DuckTypedFieldChecker
    {
        $form_element_factory             = Tracker_FormElementFactory::instance();
        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = new UgroupLabelConverter(
            $list_field_bind_value_normalizer,
            new \BaseLanguageFactory()
        );
        $bind_labels_extractor            = new CollectionOfNormalizedBindLabelsExtractor(
            $list_field_bind_value_normalizer,
            $ugroup_label_converter
        );

        return new DuckTypedFieldChecker(
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
    }

    private function getExpertQueryValidator(): ExpertQueryValidator
    {
        $report_config = new TrackerReportConfig(
            new TrackerReportConfigDao()
        );

        $parser = new ParserCacheProxy(new Parser());

        return new ExpertQueryValidator(
            $parser,
            new SizeValidatorVisitor($report_config->getExpertQueryLimit())
        );
    }

    private function getInvalidComparisonsCollector(): InvalidTermCollectorVisitor
    {
        return new InvalidTermCollectorVisitor(
            new InvalidSearchableCollectorVisitor(
                new MetadataChecker(
                    new MetadataUsageChecker(
                        Tracker_FormElementFactory::instance(),
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
                $this->getDuckTypedFieldChecker(),
            ),
            new ArtifactLinkTypeChecker(
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao(),
                ),
            )
        );
    }
}
