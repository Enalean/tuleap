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

use Tuleap\CrossTracker\CrossTrackerArtifactReportDao;
use Tuleap\CrossTracker\CrossTrackerReportDao;
use Tuleap\CrossTracker\CrossTrackerReportFactory;
use Tuleap\CrossTracker\Permission\CrossTrackerPermissionGate;
use Tuleap\CrossTracker\Report\CrossTrackerArtifactReportFactory;
use Tuleap\CrossTracker\Report\CSV\CSVExportController;
use Tuleap\CrossTracker\Report\CSV\CSVRepresentationBuilder;
use Tuleap\CrossTracker\Report\CSV\CSVRepresentationFactory;
use Tuleap\CrossTracker\Report\CSV\Format\BindToValueVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\CSVFormatterVisitor;
use Tuleap\CrossTracker\Report\CSV\SimilarFieldsFormatter;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorVisitor;
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
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\FieldResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilderVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Date\DateSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\FieldSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Numeric\NumericSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\StaticList\StaticListSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Text\TextSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\UGroupList\UGroupListSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\UserList\UserListSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilderVisitor;
use Tuleap\CrossTracker\Report\SimilarField\BindNameVisitor;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldsFilter;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldsMatcher;
use Tuleap\CrossTracker\Report\SimilarField\SupportedFieldsDao;
use Tuleap\CrossTracker\REST\ResourcesInjector;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerSearch;
use Tuleap\DB\DBFactory;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
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

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $event): void
    {
        $event->addWidget(ProjectCrossTrackerSearch::NAME);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event): void
    {
        $event->addWidget(ProjectCrossTrackerSearch::NAME);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event): void
    {
        if ($get_widget_event->getName() === ProjectCrossTrackerSearch::NAME) {
            $get_widget_event->setWidget(new ProjectCrossTrackerSearch());
        }
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets([ProjectCrossTrackerSearch::NAME]);
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    #[\Tuleap\Plugin\ListeningToEventName(TrackerFactory::TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED)]
    public function trackerEventProjectCreationTrackersRequired(array $params): void
    {
        $dao = new CrossTrackerReportDao();
        foreach ($dao->searchTrackersIdUsedByCrossTrackerByProjectId($params['project_id']) as $row) {
            $params['tracker_ids_list'][] = $row['id'];
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
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
            new \BaseLanguageFactory()
        );
        $bind_labels_extractor            = new CollectionOfNormalizedBindLabelsExtractor(
            $list_field_bind_value_normalizer,
            $ugroup_label_converter
        );

        $trackers_permissions          = TrackersPermissionsRetriever::build();
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
            $trackers_permissions,
        );
        $invalid_comparisons_collector = new InvalidTermCollectorVisitor(
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
                        new AssignedToChecker($user_manager),
                        new \Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\ArtifactSubmitterChecker(
                            $user_manager
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
        $invalid_selectables_collector = new InvalidSelectablesCollectorVisitor($duck_typed_field_checker);

        $date_time_value_rounder = new DateTimeValueRounder();
        $list_from_where_builder = new Field\ListFromWhereBuilder();
        $retrieve_field_type     = new FieldTypeRetrieverWrapper($form_element_factory);
        $query_builder_visitor   = new QueryBuilderVisitor(
            new FromWhereSearchableVisitor(),
            new ReverseLinkFromWhereBuilder(Tracker_ArtifactFactory::instance()),
            new ForwardLinkFromWhereBuilder(Tracker_ArtifactFactory::instance()),
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
                new UserListSelectFromBuilder()
            ),
        );
        $result_builder_visitor  = new ResultBuilderVisitor(
            new FieldResultBuilder(
                $form_element_factory,
                $retrieve_field_type,
                $trackers_permissions,
            ),
        );

        $cross_tracker_artifact_factory = new CrossTrackerArtifactReportFactory(
            new CrossTrackerArtifactReportDao(),
            \Tracker_ArtifactFactory::instance(),
            $validator,
            $query_builder_visitor,
            $select_builder_visitor,
            $result_builder_visitor,
            $parser,
            new CrossTrackerExpertQueryReportDao(),
            $invalid_comparisons_collector,
            $invalid_selectables_collector,
        );

        $report_dao = new CrossTrackerReportDao();

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
                TrackerFactory::instance()
            ),
            $cross_tracker_artifact_factory,
            $representation_factory,
            $report_dao,
            ProjectManager::instance(),
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
