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
use Codendi_HTMLPurifier;
use EventManager;
use Exception;
use ForgeConfig;
use Luracast\Restler\RestException;
use PFUser;
use ProjectManager;
use Tracker;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_Semantic_ContributorDao;
use Tracker_Semantic_ContributorFactory;
use Tracker_Semantic_DescriptionDao;
use Tracker_Semantic_DescriptionFactory;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_StatusFactory;
use Tracker_Semantic_TitleDao;
use Tracker_Semantic_TitleFactory;
use TrackerFactory;
use Tuleap\CrossTracker\CrossTrackerInstrumentation;
use Tuleap\CrossTracker\CrossTrackerQuery;
use Tuleap\CrossTracker\CrossTrackerReportFactory;
use Tuleap\CrossTracker\CrossTrackerReportNotFoundException;
use Tuleap\CrossTracker\CrossTrackerWidgetDao;
use Tuleap\CrossTracker\Field\ReadableFieldRetriever;
use Tuleap\CrossTracker\Report\CrossTrackerArtifactReportFactory;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Report\Query\Advanced\ExpertQueryIsEmptyException;
use Tuleap\CrossTracker\Report\Query\Advanced\FromBuilder\FromProjectBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\FromBuilder\FromTrackerBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\FromBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidOrderByBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidOrderByListChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchablesCollectionBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSelectablesCollectionBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSelectablesCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidTermCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\Date\DateFromOrderBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\FieldFromOrderBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\Numeric\NumericFromOrderBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\StaticList\StaticListFromOrderBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\Text\TextFromOrderBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\UGroupList\UGroupListFromOrderBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\UserList\UserListFromOrderBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\UserList\UserOrderByBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Metadata\MetadataFromOrderBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilderVisitor;
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
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\UGroupList\UGroupListResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\UserList\UserListResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\AlwaysThereField\ArtifactId\ArtifactIdResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\ArtifactResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Date\MetadataDateResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\MetadataResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Semantic\AssignedTo\AssignedToResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Semantic\Status\StatusResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Special\PrettyTitle\PrettyTitleResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Special\ProjectName\ProjectNameResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Special\TrackerName\TrackerNameResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Text\MetadataTextResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\User\MetadataUserResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Date\DateSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\FieldSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Numeric\NumericSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\StaticList\StaticListSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Text\TextSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\UGroupList\UGroupListSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\UserList\UserListSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\MetadataSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\AssignedTo\AssignedToSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Description\DescriptionSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Status\StatusSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Title\TitleSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Special\PrettyTitle\PrettyTitleSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Special\ProjectName\ProjectNameSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\WidgetInProjectChecker;
use Tuleap\CrossTracker\Report\ReportTrackersRetriever;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerQueryRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerReportContentRepresentation;
use Tuleap\DB\DatabaseUUIDFactory;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\DBFactory;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Exceptions\InvalidJsonException;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\Report\Query\Advanced\Errors\QueryErrorsTranslator;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\FromIsInvalidException;
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
use Tuleap\Tracker\Report\Query\Advanced\InvalidSelectException;
use Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\MissingFromException;
use Tuleap\Tracker\Report\Query\Advanced\OrderByIsInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesMustBeUniqueException;
use Tuleap\Tracker\Report\Query\Advanced\SelectLimitExceededException;
use Tuleap\Tracker\Report\Query\Advanced\SizeValidatorVisitor;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Report\TrackerReportConfig;
use Tuleap\Tracker\Report\TrackerReportConfigDao;
use Tuleap\Tracker\Semantic\Contributor\ContributorFieldRetriever;
use Tuleap\Tracker\Semantic\Status\StatusFieldRetriever;
use UGroupManager;
use URLVerification;
use UserHelper;
use UserManager;

final class CrossTrackerReportsResource extends AuthenticatedResource
{
    public const ROUTE = 'cross_tracker_reports';

    public const  MAX_LIMIT = 50;

    private readonly UserManager $user_manager;

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
     * @return CrossTrackerQueryRepresentation[]
     *
     * @throws RestException 404
     */
    public function getId(int $id): array
    {
        $this->checkAccess();
        try {
            if (! $this->getCrossTrackerDao()->searchWidgetExistence($id)) {
                throw new CrossTrackerReportNotFoundException();
            }
            $queries         = $this->getWidgetQueries($id);
            $representations = [];
            foreach ($queries as $query) {
                $representations[] = $this->getReportRepresentation($query);
            }
        } catch (CrossTrackerReportNotFoundException) {
            throw new I18NRestException(404, sprintf(dgettext('tuleap-crosstracker', 'Report with id %d not found'), $id));
        }

        $this->sendAllowHeaders();

        return $representations;
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
     *   <li>With a property "expert_query" to customize the search.
     *     Example: <pre>{"trackers_id": [1,2,3],"expert_query": "@title = 'Critical'"}</pre>
     *   </li>
     * </ul>
     * @url GET {uuid}/content
     * @access hybrid
     *
     * @param string $uuid UUID of the report
     * @param string|null $query With a property "expert_query" to customize the search. {@required false}
     * @param int $limit Number of elements displayed per page {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @throws RestException 404
     */
    public function getIdContent(string $uuid, ?string $query, int $limit = self::MAX_LIMIT, int $offset = 0): CrossTrackerReportContentRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        try {
            $current_user = $this->user_manager->getCurrentUser();
            $report       = $this->getQuery($uuid);

            $query_parser = new QueryParameterParser(new JsonDecoder());

            $expert_query = $this->getExpertQueryFromRoute($query, $report, $query_parser);

            $expected_report = new CrossTrackerQuery($report->getUUID(), $expert_query, $report->getTitle(), $report->getDescription(), $report->getWidgetId());

            $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToSeeWidget($current_user, $report->getWidgetId());

            $artifacts = $this->getInstrumentation()->updateQueryDuration(
                fn() => $this->getArtifactFactory()->getArtifactsMatchingReport(
                    $expected_report,
                    $current_user,
                    $limit,
                    $offset,
                )
            );

            assert($artifacts instanceof CrossTrackerReportContentRepresentation);
            $this->sendPaginationHeaders($limit, $offset, $artifacts->getTotalSize());
            return $artifacts;
        } catch (CrossTrackerReportNotFoundException) {
            throw new I18NRestException(404, dgettext('tuleap-crosstracker', 'Report not found'));
        } catch (SyntaxError $error) {
            throw new RestException(400, '', SyntaxErrorTranslator::fromSyntaxError($error));
        } catch (LimitSizeIsExceededException | InvalidSelectException | SelectablesMustBeUniqueException | SelectLimitExceededException | MissingFromException $exception) {
            throw new I18NRestException(400, QueryErrorsTranslator::translateException($exception));
        } catch (SearchablesDoNotExistException | SelectablesDoNotExistException $exception) {
            throw new I18NRestException(400, $exception->getI18NExceptionMessage());
        } catch (SearchablesAreInvalidException | SelectablesAreInvalidException $exception) {
            throw new I18NRestException(400, $exception->getMessage());
        } catch (FromIsInvalidException $exception) {
            throw new I18NRestException(400, $exception->getI18NExceptionMessage());
        } catch (OrderByIsInvalidException $exception) {
            throw new I18NRestException(400, $exception->getI18NExceptionMessage());
        } catch (ExpertQueryIsEmptyException) {
            throw new I18NRestException(400, dgettext('tuleap-crosstracker', 'Expert query is required and cannot be empty'));
        }
    }

    /**
     * Update a cross tracker report
     *
     * <br>
     * <pre>
     * {<br>
     *   &nbsp;"trackers_id": [1, 2, 3],<br>
     *   &nbsp;"expert_query": "",<br>
     * }<br>
     * </pre>
     *
     * ⚠️ WARNING: Expert mode of Cross Tracker Report is not yet stable,
     * future release may break your report.
     *
     * @url PUT {id}
     *
     * @param int $id Id of the report
     * @param string $expert_query The TQL query saved with the report
     *
     * @status 201
     * @access hybrid
     *
     *
     * @throws RestException 400
     * @throws RestException 404
     */
    protected function put(int $id, string $expert_query = ''): CrossTrackerQueryRepresentation
    {
        $this->sendAllowHeaders();

        $current_user = $this->user_manager->getCurrentUser();
        try {
            $reports = $this->getWidgetQueries($id);
            if ($reports === []) {
                $uuid_factory    = $this->getUUIDFactory();
                $uuid            = $uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes());
                $expected_report = new CrossTrackerQuery($uuid, $expert_query, '', '', $id);
                $trackers        = $this->getReportTrackersRetriever()->getReportTrackers($expected_report, $current_user, ForgeConfig::getInt(CrossTrackerArtifactReportFactory::MAX_TRACKER_FROM));
                $this->checkQueryIsValid($trackers, $expert_query, $current_user, $id);
                $uuid            = $this->getCrossTrackerDao()->insertQuery($id, $expert_query);
                $expected_report = $this->getQuery($uuid->toString());
            } else {
                $report          = $reports[0];
                $expected_report = new CrossTrackerQuery($report->getUUID(), $expert_query, $report->getTitle(), $report->getDescription(), $id);
                $trackers        = $this->getReportTrackersRetriever()->getReportTrackers($expected_report, $current_user, ForgeConfig::getInt(CrossTrackerArtifactReportFactory::MAX_TRACKER_FROM));
                $this->checkQueryIsValid($trackers, $expert_query, $current_user, $id);

                $this->getCrossTrackerDao()->updateQuery($id, $expert_query);
            }
        } catch (CrossTrackerReportNotFoundException) {
            throw new I18NRestException(404, sprintf(dgettext('tuleap-crosstracker', 'Report with id %d not found'), $id));
        } catch (FromIsInvalidException $exception) {
            throw new I18NRestException(400, $exception->getI18NExceptionMessage());
        } catch (SyntaxError $error) {
            throw new RestException(400, '', SyntaxErrorTranslator::fromSyntaxError($error));
        }

        return $this->getReportRepresentation($expected_report);
    }

    private function getUUIDFactory(): DatabaseUUIDFactory
    {
        return new DatabaseUUIDV7Factory();
    }

    /**
     * @param Tracker[] $trackers
     * @throws RestException
     */
    private function checkQueryIsValid(array $trackers, string $expert_query, PFUser $user, int $report_id): void
    {
        if ($expert_query === '') {
            throw new I18NRestException(400, dgettext('tuleap-crosstracker', 'Expert query is required and cannot be empty'));
        }

        try {
            $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToSeeWidget($user, $report_id);
            $field_checker    = $this->getDuckTypedFieldChecker();
            $metadata_checker = $this->getMetadataChecker();
            $this->getExpertQueryValidator()->validateExpertQuery(
                $expert_query,
                new InvalidSearchablesCollectionBuilder($this->getInvalidComparisonsCollector(), $trackers, $user),
                new InvalidSelectablesCollectionBuilder(
                    new InvalidSelectablesCollectorVisitor($field_checker, $metadata_checker),
                    $trackers,
                    $user
                ),
                new InvalidOrderByBuilder($field_checker, $metadata_checker, $trackers, $user),
            );
        } catch (SearchablesDoNotExistException | SearchablesAreInvalidException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (SyntaxError $error) {
            throw new RestException(400, '', SyntaxErrorTranslator::fromSyntaxError($error));
        } catch (OrderByIsInvalidException $exception) {
            throw new I18NRestException(400, $exception->getI18NExceptionMessage());
        } catch (LimitSizeIsExceededException | SelectLimitExceededException | SelectablesMustBeUniqueException $exception) {
            throw new I18NRestException(400, QueryErrorsTranslator::translateException($exception));
        } catch (Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function sendAllowHeaders(): void
    {
        Header::allowOptionsGetPut();
    }

    private function getReportRepresentation(CrossTrackerQuery $report): CrossTrackerQueryRepresentation
    {
        return CrossTrackerQueryRepresentation::fromQuery($report);
    }

    private function sendPaginationHeaders($limit, $offset, $size): void
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    /**
     * @throws InvalidJsonException
     * @throws RestException
     */
    private function getExpertQueryFromRoute(
        ?string $query_parameter,
        CrossTrackerQuery $report,
        QueryParameterParser $query_parser,
    ): string {
        if ($query_parameter === null || $query_parameter === '') {
            return $report->getQuery();
        }
        $query_parameter = trim($query_parameter);

        try {
            return $query_parser->getString($query_parameter, 'expert_query');
        } catch (QueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function getCrossTrackerDao(): CrossTrackerWidgetDao
    {
        return new CrossTrackerWidgetDao();
    }

    private function getUserIsAllowedToSeeWidgetChecker(): UserIsAllowedToSeeWidgetChecker
    {
        return new UserIsAllowedToSeeWidgetChecker(
            $this->getCrossTrackerDao(),
            ProjectManager::instance(),
            new URLVerification()
        );
    }

    /**
     * @throws CrossTrackerReportNotFoundException
     * @throws RestException 403
     */
    private function getQuery(string $uuid): CrossTrackerQuery
    {
        $report_factory = new CrossTrackerReportFactory(new CrossTrackerWidgetDao());

        $query        = $report_factory->getById($uuid);
        $current_user = $this->user_manager->getCurrentUser();
        $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToSeeWidget($current_user, $query->getWidgetId());

        return $query;
    }

    /**
     * @return CrossTrackerQuery[]
     * @throws RestException 403
     */
    private function getWidgetQueries(int $id): array
    {
        $report_factory = new CrossTrackerReportFactory(new CrossTrackerWidgetDao());

        $queries      = $report_factory->getByWidgetId($id);
        $current_user = $this->user_manager->getCurrentUser();
        $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToSeeWidget($current_user, $id);

        return $queries;
    }

    private function getDuckTypedFieldChecker(): DuckTypedFieldChecker
    {
        $form_element_factory             = Tracker_FormElementFactory::instance();
        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = new UgroupLabelConverter(
            $list_field_bind_value_normalizer,
            new BaseLanguageFactory()
        );
        $bind_labels_extractor            = new CollectionOfNormalizedBindLabelsExtractor(
            $list_field_bind_value_normalizer,
            $ugroup_label_converter
        );

        $field_retriever = new ReadableFieldRetriever($form_element_factory, TrackersPermissionsRetriever::build());
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
            $field_retriever
        );
    }

    private function getMetadataChecker(): MetadataChecker
    {
        return new MetadataChecker(
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
            ),
            new InvalidOrderByListChecker(
                new StatusFieldRetriever(Tracker_Semantic_StatusFactory::instance()),
                new ContributorFieldRetriever(Tracker_Semantic_ContributorFactory::instance()),
            ),
        );
    }

    private function getExpertQueryValidator(): ExpertQueryValidator
    {
        $report_config = new TrackerReportConfig(
            new TrackerReportConfigDao()
        );

        return new ExpertQueryValidator(
            $this->getParser(),
            new SizeValidatorVisitor($report_config->getExpertQueryLimit())
        );
    }

    private function getInvalidComparisonsCollector(): InvalidTermCollectorVisitor
    {
        return new InvalidTermCollectorVisitor(
            new InvalidSearchableCollectorVisitor($this->getMetadataChecker(), $this->getDuckTypedFieldChecker()),
            new ArtifactLinkTypeChecker(
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao(),
                ),
            )
        );
    }

    private function getParser(): ParserCacheProxy
    {
        return new ParserCacheProxy(new Parser());
    }

    private function getFromBuilderVisitor(): FromBuilderVisitor
    {
        $event_manager = EventManager::instance();
        $report_dao    = new CrossTrackerWidgetDao();
        return new FromBuilderVisitor(
            new FromTrackerBuilderVisitor($report_dao),
            new FromProjectBuilderVisitor(
                $report_dao,
                ProjectManager::instance(),
                $event_manager,
            ),
        );
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

    private function getArtifactFactory(): CrossTrackerArtifactReportFactory
    {
        $form_element_factory      = Tracker_FormElementFactory::instance();
        $tracker_artifact_factory  = Tracker_ArtifactFactory::instance();
        $retrieve_field_type       = new FieldTypeRetrieverWrapper($form_element_factory);
        $trackers_permissions      = TrackersPermissionsRetriever::build();
        $select_builder_visitor    = new SelectBuilderVisitor(
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
            new MetadataSelectFromBuilder(
                new TitleSelectFromBuilder(),
                new DescriptionSelectFromBuilder(),
                new StatusSelectFromBuilder(),
                new AssignedToSelectFromBuilder(),
                new ProjectNameSelectFromBuilder(),
                new PrettyTitleSelectFromBuilder(),
            ),
        );
        $purifier                  = Codendi_HTMLPurifier::instance();
        $text_value_interpreter    = new TextValueInterpreter($purifier, CommonMarkInterpreter::build($purifier));
        $field_retriever           = new ReadableFieldRetriever($form_element_factory, $trackers_permissions);
        $user_group_manager        = new UGroupManager();
        $result_builder_visitor    = new ResultBuilderVisitor(
            new FieldResultBuilder(
                $retrieve_field_type,
                new DateResultBuilder($tracker_artifact_factory, $form_element_factory),
                new TextResultBuilder($tracker_artifact_factory, $text_value_interpreter),
                new NumericResultBuilder(),
                new StaticListResultBuilder(),
                new UGroupListResultBuilder($tracker_artifact_factory, $user_group_manager),
                new UserListResultBuilder($this->user_manager, $this->user_manager, $this->user_manager, UserHelper::instance()),
                $field_retriever
            ),
            new MetadataResultBuilder(
                new MetadataTextResultBuilder($tracker_artifact_factory, $text_value_interpreter),
                new StatusResultBuilder(),
                new AssignedToResultBuilder($this->user_manager, UserHelper::instance()),
                new MetadataDateResultBuilder(),
                new MetadataUserResultBuilder($this->user_manager, UserHelper::instance()),
                new ArtifactIdResultBuilder(),
                new ProjectNameResultBuilder(),
                new TrackerNameResultBuilder(),
                new PrettyTitleResultBuilder(),
                new ArtifactResultBuilder($tracker_artifact_factory),
            ),
        );
        $text_order_builder        = new TextFromOrderBuilder();
        $static_list_order_builder = new StaticListFromOrderBuilder();
        $user_order_by_builder     = new UserOrderByBuilder(UserManager::instance());
        $user_list_builder         = new UserListFromOrderBuilder($user_order_by_builder);
        $order_builder_visitor     = new OrderByBuilderVisitor(
            new FieldFromOrderBuilder(
                $field_retriever,
                $retrieve_field_type,
                new DateFromOrderBuilder(),
                new NumericFromOrderBuilder(),
                $text_order_builder,
                $static_list_order_builder,
                new UGroupListFromOrderBuilder($user_group_manager),
                $user_list_builder,
            ),
            new MetadataFromOrderBuilder(
                Tracker_Semantic_TitleFactory::instance(),
                Tracker_Semantic_DescriptionFactory::instance(),
                new StatusFieldRetriever(Tracker_Semantic_StatusFactory::instance()),
                new ContributorFieldRetriever(Tracker_Semantic_ContributorFactory::instance()),
                $text_order_builder,
                $static_list_order_builder,
                $user_list_builder,
                $user_order_by_builder,
            ),
        );
        $field_checker             = $this->getDuckTypedFieldChecker();
        $metadata_checker          = $this->getMetadataChecker();
        return new CrossTrackerArtifactReportFactory(
            $this->getExpertQueryValidator(),
            $this->getQueryBuilderVisitor(),
            $select_builder_visitor,
            $order_builder_visitor,
            $result_builder_visitor,
            $this->getParser(),
            new CrossTrackerExpertQueryReportDao(),
            $this->getInvalidComparisonsCollector(),
            new InvalidSelectablesCollectorVisitor($field_checker, $metadata_checker),
            $field_checker,
            $metadata_checker,
            $this->getReportTrackersRetriever(),
            $this->getInstrumentation(),
            $trackers_permissions,
            $tracker_artifact_factory,
        );
    }

    private function getReportTrackersRetriever(): ReportTrackersRetriever
    {
        $report_dao = new CrossTrackerWidgetDao();
        return new ReportTrackersRetriever(
            $this->getExpertQueryValidator(),
            $this->getParser(),
            $this->getFromBuilderVisitor(),
            TrackersPermissionsRetriever::build(),
            new CrossTrackerExpertQueryReportDao(),
            TrackerFactory::instance(),
            new WidgetInProjectChecker($report_dao),
            $report_dao,
            ProjectManager::instance(),
            EventManager::instance(),
        );
    }

    private function getInstrumentation(): CrossTrackerInstrumentation
    {
        return new CrossTrackerInstrumentation(Prometheus::instance());
    }
}
