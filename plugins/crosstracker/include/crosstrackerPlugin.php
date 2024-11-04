<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Tuleap\Config\GetConfigKeys;
use Tuleap\CrossTracker\CrossTrackerArtifactReportDao;
use Tuleap\CrossTracker\CrossTrackerInstrumentation;
use Tuleap\CrossTracker\CrossTrackerReportCreator;
use Tuleap\CrossTracker\CrossTrackerReportDao;
use Tuleap\CrossTracker\CrossTrackerReportFactory;
use Tuleap\CrossTracker\Field\ReadableFieldRetriever;
use Tuleap\CrossTracker\Permission\CrossTrackerPermissionGate;
use Tuleap\CrossTracker\Report\CrossTrackerArtifactReportFactory;
use Tuleap\CrossTracker\Report\CSV\CSVExportController;
use Tuleap\CrossTracker\Report\CSV\CSVRepresentationBuilder;
use Tuleap\CrossTracker\Report\CSV\CSVRepresentationFactory;
use Tuleap\CrossTracker\Report\CSV\Format\BindToValueVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\CSVFormatterVisitor;
use Tuleap\CrossTracker\Report\CSV\SimilarFieldsFormatter;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Report\Query\Advanced\FromBuilder\FromProjectBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\FromBuilder\FromTrackerBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\FromBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidOrderByListChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorVisitor;
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
use Tuleap\CrossTracker\Report\ReportInheritanceHandler;
use Tuleap\CrossTracker\Report\ReportTrackersRetriever;
use Tuleap\CrossTracker\Report\SimilarField\BindNameVisitor;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldsFilter;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldsMatcher;
use Tuleap\CrossTracker\Report\SimilarField\SupportedFieldsDao;
use Tuleap\CrossTracker\REST\ResourcesInjector;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerSearch;
use Tuleap\DB\DBFactory;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Plugin\ListeningToEventName;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\Date\CSVFormatter;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parser;
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
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\Advanced\SizeValidatorVisitor;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Report\TrackerReportConfig;
use Tuleap\Tracker\Report\TrackerReportConfigDao;
use Tuleap\Tracker\Semantic\Contributor\ContributorFieldRetriever;
use Tuleap\Tracker\Semantic\Status\StatusFieldRetriever;
use Tuleap\Widget\Event\GetProjectWidgetList;
use Tuleap\Widget\Event\GetUserWidgetList;
use Tuleap\Widget\Event\GetWidget;

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class crosstrackerPlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        bindtextdomain('tuleap-crosstracker', __DIR__ . '/../site-content');
    }

    public function getDependencies()
    {
        return ['tracker'];
    }

    /**
     * @return Tuleap\CrossTracker\Plugin\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\CrossTracker\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    #[ListeningToEventClass]
    public function getUserWidgetList(GetUserWidgetList $event): void
    {
        $event->addWidget(ProjectCrossTrackerSearch::NAME);
    }

    #[ListeningToEventClass]
    public function getProjectWidgetList(GetProjectWidgetList $event): void
    {
        $event->addWidget(ProjectCrossTrackerSearch::NAME);
    }

    #[ListeningToEventClass]
    public function widgetInstance(GetWidget $get_widget_event): void
    {
        if ($get_widget_event->getName() === ProjectCrossTrackerSearch::NAME) {
            $report_dao = new CrossTrackerReportDao();
            $get_widget_event->setWidget(
                new ProjectCrossTrackerSearch(
                    new CrossTrackerReportCreator($report_dao),
                    new ReportInheritanceHandler(
                        new CrossTrackerReportFactory($report_dao, $report_dao, \TrackerFactory::instance()),
                        $report_dao,
                        $report_dao,
                        $this->getBackendLogger()
                    )
                )
            );
        }
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets([ProjectCrossTrackerSearch::NAME]);
    }

    #[ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    #[ListeningToEventName(TrackerFactory::TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED)]
    public function trackerEventProjectCreationTrackersRequired(array $params): void
    {
        $dao = new CrossTrackerReportDao();
        foreach ($dao->searchTrackersIdUsedByCrossTrackerByProjectId($params['project_id']) as $row) {
            $params['tracker_ids_list'][] = $row['id'];
        }
    }

    #[ListeningToEventClass]
    public function getConfigKeys(GetConfigKeys $config_keys): void
    {
        $config_keys->addConfigClass(CrossTrackerArtifactReportFactory::class);
    }

    #[ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->get(CROSSTRACKER_BASE_URL . '/csv_export/{report_id:\d+}', $this->getRouteHandler('routeGetCSVExportReport'));
    }

    public function routeGetCSVExportReport(): CSVExportController
    {
        $db           = DBFactory::getMainTuleapDBConnection()->getDB();
        $user_manager = UserManager::instance();

        $report_config = new TrackerReportConfig(
            new TrackerReportConfigDao()
        );

        $parser = new ParserCacheProxy(new Parser());

        $validator = new ExpertQueryValidator(
            $parser,
            new SizeValidatorVisitor($report_config->getExpertQueryLimit())
        );

        $form_element_factory = Tracker_FormElementFactory::instance();

        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = new UgroupLabelConverter(
            $list_field_bind_value_normalizer,
            new BaseLanguageFactory()
        );
        $bind_labels_extractor            = new CollectionOfNormalizedBindLabelsExtractor(
            $list_field_bind_value_normalizer,
            $ugroup_label_converter
        );

        $trackers_permissions = TrackersPermissionsRetriever::build();

        $field_retriever = new ReadableFieldRetriever($form_element_factory, $trackers_permissions);

        $duck_typed_field_checker      = new DuckTypedFieldChecker(
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
                new ArtifactSubmitterChecker($user_manager),
                true,
            ),
            $field_retriever,
        );
        $metadata_checker              = new MetadataChecker(
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
                new AssignedToChecker($user_manager),
                new \Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\ArtifactSubmitterChecker(
                    $user_manager
                ),
                new SubmissionDateChecker(),
                new ArtifactIdMetadataChecker(),
            ),
            new InvalidOrderByListChecker(
                new StatusFieldRetriever(Tracker_Semantic_StatusFactory::instance()),
                new ContributorFieldRetriever(Tracker_Semantic_ContributorFactory::instance()),
            ),
        );
        $invalid_comparisons_collector = new InvalidTermCollectorVisitor(
            new InvalidSearchableCollectorVisitor($metadata_checker, $duck_typed_field_checker),
            new ArtifactLinkTypeChecker(
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao(),
                ),
            )
        );
        $invalid_selectables_collector = new InvalidSelectablesCollectorVisitor($duck_typed_field_checker, $metadata_checker);

        $date_time_value_rounder = new DateTimeValueRounder();
        $list_from_where_builder = new Field\ListFromWhereBuilder();
        $retrieve_field_type     = new FieldTypeRetrieverWrapper($form_element_factory);
        $artifact_factory        = Tracker_ArtifactFactory::instance();
        $query_builder_visitor   = new QueryBuilderVisitor(
            new FromWhereSearchableVisitor(),
            new ReverseLinkFromWhereBuilder($artifact_factory),
            new ForwardLinkFromWhereBuilder($artifact_factory),
            new Field\FieldFromWhereBuilder(
                $form_element_factory,
                $retrieve_field_type,
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
                new Metadata\Semantic\AssignedTo\AssignedToFromWhereBuilder($user_manager),
                new Metadata\AlwaysThereField\Date\DateFromWhereBuilder($date_time_value_rounder),
                new Metadata\AlwaysThereField\Users\UsersFromWhereBuilder($user_manager),
                new Metadata\AlwaysThereField\ArtifactId\ArtifactIdFromWhereBuilder(),
                $form_element_factory,
            ),
        );
        $select_builder_visitor  = new SelectBuilderVisitor(
            new FieldSelectFromBuilder(
                $form_element_factory,
                $retrieve_field_type,
                $trackers_permissions,
                new DateSelectFromBuilder(),
                new TextSelectFromBuilder(),
                new NumericSelectFromBuilder(),
                new StaticListSelectFromBuilder(),
                new UGroupListSelectFromBuilder(),
                new UserListSelectFromBuilder(),
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
        $purifier                = Codendi_HTMLPurifier::instance();
        $text_value_interpreter  = new TextValueInterpreter(
            $purifier,
            CommonMarkInterpreter::build($purifier),
        );
        $user_group_manager      = new UGroupManager();
        $result_builder_visitor  = new ResultBuilderVisitor(
            new FieldResultBuilder(
                $retrieve_field_type,
                new DateResultBuilder(
                    $artifact_factory,
                    $form_element_factory,
                ),
                new TextResultBuilder(
                    $artifact_factory,
                    $text_value_interpreter,
                ),
                new NumericResultBuilder(),
                new StaticListResultBuilder(),
                new UGroupListResultBuilder($artifact_factory, $user_group_manager),
                new UserListResultBuilder($user_manager, $user_manager, $user_manager, UserHelper::instance()),
                $field_retriever
            ),
            new MetadataResultBuilder(
                new MetadataTextResultBuilder(
                    $artifact_factory,
                    $text_value_interpreter,
                ),
                new StatusResultBuilder(),
                new AssignedToResultBuilder($user_manager, UserHelper::instance()),
                new MetadataDateResultBuilder(),
                new MetadataUserResultBuilder($user_manager, UserHelper::instance()),
                new ArtifactIdResultBuilder(),
                new ProjectNameResultBuilder(),
                new TrackerNameResultBuilder(),
                new PrettyTitleResultBuilder(),
                new ArtifactResultBuilder($artifact_factory),
            ),
        );
        $event_manager           = EventManager::instance();
        $project_manager         = ProjectManager::instance();
        $report_dao              = new CrossTrackerReportDao();
        $from_builder_visitor    = new FromBuilderVisitor(
            new FromTrackerBuilderVisitor($report_dao),
            new FromProjectBuilderVisitor(
                $report_dao,
                $project_manager,
                $event_manager,
            ),
        );

        $text_order_builder        = new TextFromOrderBuilder();
        $static_list_order_builder = new StaticListFromOrderBuilder();
        $user_order_by_builder     = new UserOrderByBuilder($user_manager);
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

        $expert_query_dao               = new CrossTrackerExpertQueryReportDao();
        $tracker_factory                = TrackerFactory::instance();
        $cross_tracker_artifact_factory = new CrossTrackerArtifactReportFactory(
            new CrossTrackerArtifactReportDao(),
            Tracker_ArtifactFactory::instance(),
            $validator,
            $query_builder_visitor,
            $select_builder_visitor,
            $order_builder_visitor,
            $result_builder_visitor,
            $parser,
            $expert_query_dao,
            $invalid_comparisons_collector,
            $invalid_selectables_collector,
            $duck_typed_field_checker,
            $metadata_checker,
            new ReportTrackersRetriever(
                $validator,
                $parser,
                $from_builder_visitor,
                $trackers_permissions,
                $expert_query_dao,
                $tracker_factory,
                new WidgetInProjectChecker($report_dao),
                $report_dao,
                $project_manager,
                $event_manager,
            ),
            new CrossTrackerInstrumentation(Prometheus::instance())
        );

        $formatter_visitor = new CSVFormatterVisitor(new CSVFormatter());

        $csv_representation_builder     = new CSVRepresentationBuilder(
            $formatter_visitor,
            $user_manager,
            new SimilarFieldsFormatter($formatter_visitor, new BindToValueVisitor())
        );
        $representation_factory         = new CSVRepresentationFactory($csv_representation_builder);
        $trackers_permissions_retriever = $trackers_permissions;

        return new CSVExportController(
            new CrossTrackerReportFactory(
                $report_dao,
                $report_dao,
                $tracker_factory
            ),
            $cross_tracker_artifact_factory,
            $representation_factory,
            $report_dao,
            $project_manager,
            new CrossTrackerPermissionGate(
                new URLVerification(),
                $trackers_permissions_retriever,
                $trackers_permissions_retriever,
            ),
            new SimilarFieldsMatcher(
                new SupportedFieldsDao(),
                $form_element_factory,
                new SimilarFieldsFilter(),
                new BindNameVisitor()
            )
        );
    }
}
