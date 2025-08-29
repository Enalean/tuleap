<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query;

use BaseLanguageFactory;
use Codendi_HTMLPurifier;
use EventManager;
use ProjectManager;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\CrossTracker\CrossTrackerInstrumentation;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Query\Advanced\FromBuilder\FromProjectBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\FromBuilder\FromTrackerBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\FromBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\InvalidOrderByListChecker;
use Tuleap\CrossTracker\Query\Advanced\InvalidSearchableCollectorVisitor;
use Tuleap\CrossTracker\Query\Advanced\InvalidSelectablesCollectorVisitor;
use Tuleap\CrossTracker\Query\Advanced\InvalidTermCollectorVisitor;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\Date\DateFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\FieldFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\Numeric\NumericFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\StaticList\StaticListFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\Text\TextFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\UGroupList\UGroupListFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\UserList\UserListFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\UserList\UserOrderByBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Metadata\MetadataFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\ArtifactLink\ForwardLinkFromWhereBuilder;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\ArtifactLink\ReverseLinkFromWhereBuilder;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\CrossTrackerTQLQueryDao;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\FromWhereSearchableVisitor;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Special\ForwardLinkTypeFromWhereBuilder;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\DuckTypedField\DuckTypedFieldChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\ArtifactIdMetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\AssignedToChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\InvalidMetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\StatusChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\SubmissionDateChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\TextSemanticChecker;
use Tuleap\CrossTracker\Query\Advanced\ReadableFieldRetriever;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\Date\DateResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\FieldResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\Numeric\NumericResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\StaticList\StaticListResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\Text\TextResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\UGroupList\UGroupListResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\UserList\UserListResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\AlwaysThereField\ArtifactId\ArtifactIdResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\ArtifactResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Date\MetadataDateResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\MetadataResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Semantic\AssignedTo\AssignedToResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Semantic\Description\DescriptionResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Semantic\Status\StatusResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Semantic\Title\TitleResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\LinkType\LinkTypeResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\PrettyTitle\PrettyTitleResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\ProjectName\ProjectNameResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\TrackerName\TrackerNameResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\User\MetadataUserResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\Date\DateSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\FieldSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\Numeric\NumericSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\StaticList\StaticListSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\Text\TextSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\UGroupList\UGroupListSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\UserList\UserListSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\MetadataSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\AssignedTo\AssignedToSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\Description\DescriptionSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\Status\StatusSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\Title\TitleSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\LinkType\BuildLinkTypeSelectFrom;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\PrettyTitle\PrettyTitleSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\ProjectName\ProjectNameSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\WidgetInProjectChecker;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetDao;
use Tuleap\DB\DBFactory;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\SystemTypePresenterBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
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
use Tuleap\Tracker\Semantic\Contributor\TrackerSemanticContributorFactory;
use Tuleap\Tracker\Semantic\Description\CachedSemanticDescriptionFieldRetriever;
use Tuleap\Tracker\Semantic\Status\CachedSemanticStatusFieldRetriever;
use Tuleap\Tracker\Semantic\Status\CachedSemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Title\CachedSemanticTitleFieldRetriever;
use UGroupManager;
use UserHelper;
use UserManager;

final class CrossTrackerArtifactQueryFactoryBuilder
{
    public function getInstrumentation(): CrossTrackerInstrumentation
    {
        return new CrossTrackerInstrumentation(Prometheus::instance());
    }

    public function getDuckTypedFieldChecker(): DuckTypedFieldChecker
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
                new ArtifactSubmitterChecker(UserManager::instance()),
                true,
            ),
            $field_retriever
        );
    }

    public function getMetadataChecker(): MetadataChecker
    {
        return new MetadataChecker(
            new InvalidMetadataChecker(
                new TextSemanticChecker(),
                new StatusChecker(),
                new AssignedToChecker(UserManager::instance()),
                new \Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\ArtifactSubmitterChecker(
                    UserManager::instance()
                ),
                new SubmissionDateChecker(),
                new ArtifactIdMetadataChecker(),
            ),
            new InvalidOrderByListChecker(
                CachedSemanticStatusFieldRetriever::instance(),
                new ContributorFieldRetriever(TrackerSemanticContributorFactory::instance()),
            ),
        );
    }

    public function getQueryValidator(): ExpertQueryValidator
    {
        $report_config = new TrackerReportConfig(
            new TrackerReportConfigDao()
        );

        return new ExpertQueryValidator(
            $this->getParser(),
            new SizeValidatorVisitor($report_config->getExpertQueryLimit())
        );
    }

    public function getInvalidComparisonsCollector(): InvalidTermCollectorVisitor
    {
        return new InvalidTermCollectorVisitor(
            new InvalidSearchableCollectorVisitor($this->getMetadataChecker(), $this->getDuckTypedFieldChecker()),
            new ArtifactLinkTypeChecker(
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao(),
                    new SystemTypePresenterBuilder(EventManager::instance())
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
        $widget_dao    = new CrossTrackerWidgetDao();
        return new FromBuilderVisitor(
            new FromTrackerBuilderVisitor($widget_dao),
            new FromProjectBuilderVisitor(
                $widget_dao,
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
        $list_from_where_builder = new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\ListFromWhereBuilder();

        return new QueryBuilderVisitor(
            new FromWhereSearchableVisitor(),
            new ReverseLinkFromWhereBuilder($artifact_factory),
            new ForwardLinkFromWhereBuilder($artifact_factory),
            new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\FieldFromWhereBuilder(
                $form_element_factory,
                new FieldTypeRetrieverWrapper($form_element_factory),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\Numeric\NumericFromWhereBuilder(),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\Text\TextFromWhereBuilder($db),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\Date\DateFromWhereBuilder($date_time_value_rounder),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\Datetime\DatetimeFromWhereBuilder($date_time_value_rounder),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\StaticList\StaticListFromWhereBuilder($list_from_where_builder),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\UGroupList\UGroupListFromWhereBuilder(
                    new UgroupLabelConverter(new ListFieldBindValueNormalizer(), new BaseLanguageFactory()),
                    $list_from_where_builder,
                ),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\UserList\UserListFromWhereBuilder($list_from_where_builder),
            ),
            new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\MetadataFromWhereBuilder(
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Semantic\Title\TitleFromWhereBuilder($db),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Semantic\Description\DescriptionFromWhereBuilder(
                    $db,
                    CachedSemanticDescriptionFieldRetriever::instance(),
                ),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Semantic\Status\StatusFromWhereBuilder(),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Semantic\AssignedTo\AssignedToFromWhereBuilder(UserManager::instance()),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Date\DateFromWhereBuilder($date_time_value_rounder),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Users\UsersFromWhereBuilder(UserManager::instance()),
                new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\ArtifactId\ArtifactIdFromWhereBuilder(),
                new ForwardLinkTypeFromWhereBuilder(),
                $form_element_factory,
            ),
        );
    }

    public function getArtifactFactory(BuildLinkTypeSelectFrom $link_type_builder,): CrossTrackerArtifactQueryFactory
    {
        $tuleap_db                 = DBFactory::getMainTuleapDBConnection()->getDB();
        $form_element_factory      = Tracker_FormElementFactory::instance();
        $tracker_artifact_factory  = Tracker_ArtifactFactory::instance();
        $retrieve_field_type       = new FieldTypeRetrieverWrapper($form_element_factory);
        $trackers_permissions      = TrackersPermissionsRetriever::build();
        $select_builder_visitor    = new SelectBuilderVisitor(
            new FieldSelectFromBuilder(
                $form_element_factory,
                $retrieve_field_type,
                $trackers_permissions,
                new DateSelectFromBuilder($tuleap_db),
                new TextSelectFromBuilder($tuleap_db),
                new NumericSelectFromBuilder($tuleap_db),
                new StaticListSelectFromBuilder($tuleap_db),
                new UGroupListSelectFromBuilder($tuleap_db),
                new UserListSelectFromBuilder($tuleap_db)
            ),
            new MetadataSelectFromBuilder(
                new TitleSelectFromBuilder(),
                new DescriptionSelectFromBuilder(),
                new StatusSelectFromBuilder(),
                new AssignedToSelectFromBuilder(),
                new ProjectNameSelectFromBuilder(),
                new PrettyTitleSelectFromBuilder(),
                $link_type_builder
            ),
        );
        $purifier                  = Codendi_HTMLPurifier::instance();
        $text_value_interpreter    = new TextValueInterpreter($purifier, CommonMarkInterpreter::build($purifier));
        $field_retriever           = new ReadableFieldRetriever($form_element_factory, $trackers_permissions);
        $user_group_manager        = new UGroupManager();
        $user_manager              = UserManager::instance();
        $user_helper               = UserHelper::instance();
        $tracker_factory           = TrackerFactory::instance();
        $semantic_title_retriever  = CachedSemanticTitleFieldRetriever::instance();
        $result_builder_visitor    = new ResultBuilderVisitor(
            new FieldResultBuilder(
                $retrieve_field_type,
                new DateResultBuilder($tracker_artifact_factory, $form_element_factory),
                new TextResultBuilder($tracker_artifact_factory, $text_value_interpreter),
                new NumericResultBuilder(),
                new StaticListResultBuilder(),
                new UGroupListResultBuilder($tracker_artifact_factory, $user_group_manager),
                new UserListResultBuilder($user_manager, $user_manager, $user_manager, $user_helper),
                $field_retriever
            ),
            new MetadataResultBuilder(
                new TitleResultBuilder($tracker_artifact_factory, $text_value_interpreter, $semantic_title_retriever),
                new DescriptionResultBuilder($tracker_artifact_factory, $text_value_interpreter, CachedSemanticDescriptionFieldRetriever::instance()),
                new StatusResultBuilder($tracker_artifact_factory, CachedSemanticStatusRetriever::instance()),
                new AssignedToResultBuilder($user_manager, $user_helper, $tracker_artifact_factory),
                new MetadataDateResultBuilder(),
                new MetadataUserResultBuilder($user_manager, $user_helper),
                new ArtifactIdResultBuilder(),
                new ProjectNameResultBuilder(),
                new TrackerNameResultBuilder(),
                new PrettyTitleResultBuilder($tracker_artifact_factory, $semantic_title_retriever),
                new LinkTypeResultBuilder(),
                new ArtifactResultBuilder(
                    $tracker_artifact_factory,
                    new TrackersListAllowedByPlugins(
                        EventManager::instance(),
                        $tracker_factory
                    )
                ),
            ),
        );
        $text_order_builder        = new TextFromOrderBuilder($tuleap_db);
        $static_list_order_builder = new StaticListFromOrderBuilder($tuleap_db);
        $user_order_by_builder     = new UserOrderByBuilder($user_manager);
        $user_list_builder         = new UserListFromOrderBuilder($user_order_by_builder, $tuleap_db);
        $order_builder_visitor     = new OrderByBuilderVisitor(
            new FieldFromOrderBuilder(
                $field_retriever,
                $retrieve_field_type,
                new DateFromOrderBuilder($tuleap_db),
                new NumericFromOrderBuilder($tuleap_db),
                $text_order_builder,
                $static_list_order_builder,
                new UGroupListFromOrderBuilder($user_group_manager, $tuleap_db),
                $user_list_builder,
            ),
            new MetadataFromOrderBuilder(
                $semantic_title_retriever,
                CachedSemanticDescriptionFieldRetriever::instance(),
                CachedSemanticStatusFieldRetriever::instance(),
                new ContributorFieldRetriever(TrackerSemanticContributorFactory::instance()),
                $text_order_builder,
                $static_list_order_builder,
                $user_list_builder,
                $user_order_by_builder,
                $tuleap_db
            ),
        );
        $field_checker             = $this->getDuckTypedFieldChecker();
        $metadata_checker          = $this->getMetadataChecker();
        $query_trackers_retriever  = $this->getQueryTrackersRetriever();

        return new CrossTrackerArtifactQueryFactory(
            $this->getQueryValidator(),
            $this->getQueryBuilderVisitor(),
            $select_builder_visitor,
            $order_builder_visitor,
            $result_builder_visitor,
            $this->getParser(),
            new CrossTrackerTQLQueryDao(),
            $this->getInvalidComparisonsCollector(),
            new InvalidSelectablesCollectorVisitor($field_checker, $metadata_checker),
            $field_checker,
            $metadata_checker,
            $query_trackers_retriever,
            $this->getInstrumentation(),
            $trackers_permissions,
            $tracker_artifact_factory,
            new ForwardLinkFromWhereBuilder($tracker_artifact_factory),
            new ReverseLinkFromWhereBuilder($tracker_artifact_factory),
            new TrackersListAllowedByPlugins(
                EventManager::instance(),
                TrackerFactory::instance()
            ),
            TrackersPermissionsRetriever::build(),
        );
    }

    public function getQueryTrackersRetriever(): QueryTrackersRetriever
    {
        $widget_dao = new CrossTrackerWidgetDao();
        return new QueryTrackersRetriever(
            $this->getQueryValidator(),
            $this->getFromBuilderVisitor(),
            TrackersPermissionsRetriever::build(),
            new CrossTrackerTQLQueryDao(),
            new WidgetInProjectChecker($widget_dao),
            $widget_dao,
            ProjectManager::instance(),
            EventManager::instance(),
            new TrackersListAllowedByPlugins(
                EventManager::instance(),
                TrackerFactory::instance()
            ),
        );
    }
}
