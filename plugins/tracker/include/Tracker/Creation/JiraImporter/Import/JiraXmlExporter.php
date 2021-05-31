<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import;

use EventManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\ArtifactsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentDownloader;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ListFieldChangeInitialValueRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentValuesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentXMLValueEnhancer;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentationCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\ChangelogSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\CurrentSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\InitialSnapshotBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\IssueSnapshotCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\DataChangesetXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\FieldChangeXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Permissions\PermissionsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportAllIssuesExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportCreatedRecentlyExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportDefaultCriteriaExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportDoneIssuesExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportOpenIssuesExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportTableExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportUpdatedRecentlyExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlTQLReportExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Semantic\SemanticsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ContainersXMLCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ContainersXMLCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\StoryPointFieldExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserInfoQuerier;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraToTuleapFieldTypeMapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\Creation\JiraImporter\JiraCredentials;
use Tuleap\Tracker\FormElement\FieldNameFormatter;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeArtifactLinksBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFileBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use UserManager;
use UserXMLExporter;
use XML_SimpleXMLCDATAFactory;

class JiraXmlExporter
{
    /**
     * @var ErrorCollector
     */
    private $error_collector;
    /**
     * @var JiraFieldRetriever
     */
    private $jira_field_retriever;
    /**
     * @var JiraToTuleapFieldTypeMapper
     */
    private $field_type_mapper;
    /**
     * @var XmlReportExporter
     */
    private $report_exporter;
    /**
     * @var PermissionsXMLExporter
     */
    private $permissions_xml_exporter;

    /**
     * @var ArtifactsXMLExporter
     */
    private $artifacts_xml_exporter;

    /**
     * @var SemanticsXMLExporter
     */
    private $semantics_xml_exporter;
    /**
     * @var ContainersXMLCollectionBuilder
     */
    private $containers_xml_collection_builder;
    /**
     * @var AlwaysThereFieldsExporter
     */
    private $always_there_fields_exporter;

    /**
     * @var XmlReportAllIssuesExporter
     */
    private $xml_report_all_issues_exporter;

    /**
     * @var XmlReportOpenIssuesExporter
     */
    private $xml_report_open_issues_exporter;

    /**
     * @var XmlReportCreatedRecentlyExporter
     */
    private $xml_report_created_recently_exporter;

    /**
     * @var XmlReportUpdatedRecentlyExporter
     */
    private $xml_report_updated_recently_exporter;

    /**
     * @var XmlReportDoneIssuesExporter
     */
    private $xml_report_done_issues_exporter;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ClientWrapper
     */
    private $wrapper;

    /**
     * @var EventDispatcherInterface
     */
    private $event_manager;

    /**
     * @var JiraUserRetriever
     */
    private $jira_user_retriever;
    /**
     * @var StoryPointFieldExporter
     */
    private $story_point_field_exporter;

    public function __construct(
        LoggerInterface $logger,
        ClientWrapper $wrapper,
        ErrorCollector $error_collector,
        JiraFieldRetriever $jira_field_retriever,
        JiraToTuleapFieldTypeMapper $field_type_mapper,
        JiraUserRetriever $jira_user_retriever,
        XmlReportExporter $report_exporter,
        PermissionsXMLExporter $permissions_xml_exporter,
        ArtifactsXMLExporter $artifacts_xml_exporter,
        SemanticsXMLExporter $semantics_xml_exporter,
        ContainersXMLCollectionBuilder $containers_xml_collection_builder,
        AlwaysThereFieldsExporter $always_there_fields_exporter,
        StoryPointFieldExporter $story_point_field_exporter,
        XmlReportAllIssuesExporter $xml_report_all_issues_exporter,
        XmlReportOpenIssuesExporter $xml_report_open_issues_exporter,
        XmlReportDoneIssuesExporter $xml_report_done_issues_exporter,
        XmlReportCreatedRecentlyExporter $xml_report_created_recently_exporter,
        XmlReportUpdatedRecentlyExporter $xml_report_updated_recently_exporter,
        EventDispatcherInterface $event_manager
    ) {
        $this->logger                               = $logger;
        $this->wrapper                              = $wrapper;
        $this->error_collector                      = $error_collector;
        $this->jira_field_retriever                 = $jira_field_retriever;
        $this->field_type_mapper                    = $field_type_mapper;
        $this->jira_user_retriever                  = $jira_user_retriever;
        $this->report_exporter                      = $report_exporter;
        $this->permissions_xml_exporter             = $permissions_xml_exporter;
        $this->artifacts_xml_exporter               = $artifacts_xml_exporter;
        $this->semantics_xml_exporter               = $semantics_xml_exporter;
        $this->containers_xml_collection_builder    = $containers_xml_collection_builder;
        $this->always_there_fields_exporter         = $always_there_fields_exporter;
        $this->story_point_field_exporter           = $story_point_field_exporter;
        $this->xml_report_all_issues_exporter       = $xml_report_all_issues_exporter;
        $this->xml_report_open_issues_exporter      = $xml_report_open_issues_exporter;
        $this->xml_report_done_issues_exporter      = $xml_report_done_issues_exporter;
        $this->xml_report_created_recently_exporter = $xml_report_created_recently_exporter;
        $this->xml_report_updated_recently_exporter = $xml_report_updated_recently_exporter;
        $this->event_manager                        = $event_manager;
    }

    /**
     * @throws \RuntimeException
     */
    public static function build(
        JiraCredentials $jira_credentials,
        LoggerInterface $logger,
        JiraUserOnTuleapCache $jira_user_on_tuleap_cache
    ): self {
        $error_collector = new ErrorCollector();

        $cdata_factory = new XML_SimpleXMLCDATAFactory();

        $wrapper = ClientWrapper::build($jira_credentials);

        $field_xml_exporter = new FieldXmlExporter(
            new XML_SimpleXMLCDATAFactory(),
            new FieldNameFormatter()
        );

        $jira_field_mapper         = new JiraToTuleapFieldTypeMapper($field_xml_exporter, $error_collector, $logger);
        $report_table_exporter     = new XmlReportTableExporter();
        $default_criteria_exporter = new XmlReportDefaultCriteriaExporter();

        $tql_report_exporter = new XmlTQLReportExporter(
            $default_criteria_exporter,
            $cdata_factory,
            $report_table_exporter
        );

        $creation_state_list_value_formatter = new CreationStateListValueFormatter();
        $user_manager                        = UserManager::instance();
        $forge_user                          = $user_manager->getUserById(TrackerImporterUser::ID);

        if ($forge_user === null) {
            throw new \RuntimeException("Unable to find TrackerImporterUser");
        }

        $jira_user_retriever = new JiraUserRetriever(
            $logger,
            $user_manager,
            $jira_user_on_tuleap_cache,
            new JiraUserInfoQuerier(
                $wrapper,
                $logger
            ),
            $forge_user
        );

        return new self(
            $logger,
            $wrapper,
            $error_collector,
            new JiraFieldRetriever($wrapper, $logger),
            $jira_field_mapper,
            $jira_user_retriever,
            new XmlReportExporter(),
            new PermissionsXMLExporter(),
            new ArtifactsXMLExporter(
                $wrapper,
                $user_manager,
                new DataChangesetXMLExporter(
                    new XML_SimpleXMLCDATAFactory(),
                    new FieldChangeXMLExporter(
                        $logger,
                        new FieldChangeDateBuilder(
                            new XML_SimpleXMLCDATAFactory()
                        ),
                        new FieldChangeStringBuilder(
                            new XML_SimpleXMLCDATAFactory()
                        ),
                        new FieldChangeTextBuilder(
                            new XML_SimpleXMLCDATAFactory()
                        ),
                        new FieldChangeFloatBuilder(
                            new XML_SimpleXMLCDATAFactory()
                        ),
                        new FieldChangeListBuilder(
                            new XML_SimpleXMLCDATAFactory(),
                            UserXMLExporter::build()
                        ),
                        new FieldChangeFileBuilder(),
                        new FieldChangeArtifactLinksBuilder(
                            new XML_SimpleXMLCDATAFactory(),
                        ),
                    ),
                    new IssueSnapshotCollectionBuilder(
                        new ChangelogEntriesBuilder(
                            $wrapper,
                            $logger
                        ),
                        new CurrentSnapshotBuilder(
                            $logger,
                            $creation_state_list_value_formatter,
                            $jira_user_retriever
                        ),
                        new InitialSnapshotBuilder(
                            $logger,
                            new ListFieldChangeInitialValueRetriever(
                                $creation_state_list_value_formatter,
                                $jira_user_retriever
                            )
                        ),
                        new ChangelogSnapshotBuilder(
                            $creation_state_list_value_formatter,
                            $logger,
                            $jira_user_retriever
                        ),
                        new CommentValuesBuilder(
                            $wrapper,
                            $logger
                        ),
                        $logger,
                        $jira_user_retriever
                    ),
                    new CommentXMLExporter(
                        new XML_SimpleXMLCDATAFactory(),
                        new CommentXMLValueEnhancer()
                    ),
                    $logger
                ),
                new AttachmentCollectionBuilder(),
                new AttachmentXMLExporter(
                    AttachmentDownloader::build($jira_credentials, $logger),
                    new XML_SimpleXMLCDATAFactory()
                ),
                $logger
            ),
            new SemanticsXMLExporter(),
            new ContainersXMLCollectionBuilder(),
            new AlwaysThereFieldsExporter(
                $field_xml_exporter
            ),
            new StoryPointFieldExporter(
                $field_xml_exporter,
                $logger,
            ),
            new XmlReportAllIssuesExporter(
                $default_criteria_exporter,
                $report_table_exporter
            ),
            new XmlReportOpenIssuesExporter(
                $default_criteria_exporter,
                $report_table_exporter,
            ),
            new XmlReportDoneIssuesExporter(
                $default_criteria_exporter,
                $report_table_exporter,
            ),
            new XmlReportCreatedRecentlyExporter($tql_report_exporter),
            new XmlReportUpdatedRecentlyExporter($tql_report_exporter),
            EventManager::instance()
        );
    }

    /**
     * @throws JiraConnectionException
     */
    public function exportJiraToXml(
        PlatformConfiguration $jira_platform_configuration,
        SimpleXMLElement $node_tracker,
        string $jira_base_url,
        string $jira_project_key,
        IssueType $issue_type,
        IDGenerator $field_id_generator,
        LinkedIssuesCollection $linked_issues_collection
    ): void {
        $this->logger->debug("Start export Jira to XML: " . $issue_type->getId());

        if (! isset($node_tracker->formElements)) {
            throw new \RuntimeException('XML node should have a `formElements` child');
        }

        $containers_collection         = new ContainersXMLCollection($field_id_generator);
        $jira_field_mapping_collection = new FieldMappingCollection($field_id_generator);
        $status_values_collection      = new StatusValuesCollection(
            $this->wrapper,
            $this->logger
        );

        $this->containers_xml_collection_builder->buildCollectionOfJiraContainersXML(
            $node_tracker->formElements,
            $containers_collection,
        );

        $this->logger->debug("Handle status");
        $status_values_collection->initCollectionForProjectAndIssueType(
            $jira_project_key,
            $issue_type->getId(),
            $field_id_generator,
        );

        $this->logger->debug("Export always there jira fields");
        $this->always_there_fields_exporter->exportFields(
            $containers_collection,
            $jira_field_mapping_collection,
            $status_values_collection,
        );

        $this->logger->debug("Export Story Points field");
        $this->story_point_field_exporter->exportFields(
            $jira_platform_configuration,
            $containers_collection,
            $jira_field_mapping_collection,
            $issue_type,
        );

        $this->logger->debug("Export custom jira fields");
        $this->exportJiraField(
            $containers_collection,
            $jira_field_mapping_collection,
            $field_id_generator,
            $jira_project_key,
            $issue_type,
            $jira_platform_configuration,
        );

        $this->logger->debug("Export semantics");
        $this->semantics_xml_exporter->exportSemantics(
            $node_tracker,
            $jira_field_mapping_collection,
            $status_values_collection
        );

        $node_tracker->addChild('rules');

        $this->logger->debug("Export reports");
        $this->report_exporter->exportReports(
            $node_tracker,
            $jira_field_mapping_collection,
            $status_values_collection,
            $this->xml_report_all_issues_exporter,
            $this->xml_report_open_issues_exporter,
            $this->xml_report_done_issues_exporter,
            $this->xml_report_created_recently_exporter,
            $this->xml_report_updated_recently_exporter
        );
        $node_tracker->addChild('workflow');

        $this->logger->debug("Export permissions");
        $this->permissions_xml_exporter->exportFieldsPermissions(
            $node_tracker,
            $jira_field_mapping_collection
        );

        $this->logger->debug("Export artifact");

        $issue_representation_collection = new IssueAPIRepresentationCollection();
        $this->artifacts_xml_exporter->exportArtifacts(
            $node_tracker,
            $jira_field_mapping_collection,
            $issue_representation_collection,
            $linked_issues_collection,
            $jira_base_url,
            $jira_project_key,
            $issue_type->getId(),
        );

        $this->event_manager->dispatch(
            new JiraImporterExternalPluginsEvent(
                $node_tracker,
                $jira_platform_configuration,
                $issue_representation_collection,
                $this->jira_user_retriever,
                $this->wrapper,
                $this->logger,
                $jira_field_mapping_collection
            )
        );

        if ($this->error_collector->hasError()) {
            foreach ($this->error_collector->getErrors() as $error) {
                $this->logger->error($error);
            }
        }
    }

    private function exportJiraField(
        ContainersXMLCollection $containers_collection,
        FieldMappingCollection $jira_field_mapping_collection,
        IDGenerator $id_generator,
        string $jira_project_id,
        IssueType $issue_type,
        PlatformConfiguration $platform_configuration
    ): void {
        $this->logger->debug("Start exporting jira field structure (custom fields) ...");
        $fields = $this->jira_field_retriever->getAllJiraFields($jira_project_id, $issue_type->getId(), $id_generator);
        foreach ($fields as $key => $field) {
            $this->field_type_mapper->exportFieldToXml(
                $field,
                $containers_collection,
                $jira_field_mapping_collection,
                $platform_configuration,
            );
        }
        $this->logger->debug("Field structure exported successfully");
    }
}
