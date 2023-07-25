<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
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
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\AppendFieldsFromCreateMetaAPI;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\AppendFieldsFromCreateMetaServer9API;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\XML\JiraXMLNodeBuilder;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraToTuleapFieldTypeMapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use Tuleap\Tracker\XML\XMLTracker;
use UserManager;
use XML_SimpleXMLCDATAFactory;

class JiraIssuesFromProjectInMonoTrackerInXmlExporter
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly JiraClient $wrapper,
        private readonly ErrorCollector $error_collector,
        private readonly JiraFieldRetriever $jira_field_retriever,
        private readonly JiraToTuleapFieldTypeMapper $field_type_mapper,
        private readonly XmlReportExporter $report_exporter,
        private readonly SemanticsXMLExporter $semantics_xml_exporter,
        private readonly AlwaysThereFieldsExporter $always_there_fields_exporter,
        private readonly XmlReportAllIssuesExporter $xml_report_all_issues_exporter,
        private readonly XmlReportOpenIssuesExporter $xml_report_open_issues_exporter,
        private readonly XmlReportDoneIssuesExporter $xml_report_done_issues_exporter,
        private readonly XmlReportCreatedRecentlyExporter $xml_report_created_recently_exporter,
        private readonly XmlReportUpdatedRecentlyExporter $xml_report_updated_recently_exporter,
    ) {
    }

    /**
     * @throws \RuntimeException
     */
    public static function build(
        JiraClient $wrapper,
        LoggerInterface $logger,
    ): self {
        $error_collector = new ErrorCollector();

        $cdata_factory = new XML_SimpleXMLCDATAFactory();

        $jira_field_mapper         = new JiraToTuleapFieldTypeMapper($error_collector, $logger);
        $report_table_exporter     = new XmlReportTableExporter();
        $default_criteria_exporter = new XmlReportDefaultCriteriaExporter();

        $tql_report_exporter = new XmlTQLReportExporter(
            $default_criteria_exporter,
            $cdata_factory,
            $report_table_exporter
        );

        $user_manager = UserManager::instance();
        $forge_user   = $user_manager->getUserById(TrackerImporterUser::ID);

        if ($forge_user === null) {
            throw new \RuntimeException("Unable to find TrackerImporterUser");
        }

        if ($wrapper->isJiraCloud()) {
            $append_fields_from_create = new AppendFieldsFromCreateMetaAPI($wrapper, $logger);
        } elseif ($wrapper->isJiraServer9()) {
            $append_fields_from_create = new AppendFieldsFromCreateMetaServer9API($wrapper, $logger);
        } else {
            $append_fields_from_create = new AppendFieldsFromCreateMetaAPI($wrapper, $logger);
        }

        return new self(
            $logger,
            $wrapper,
            $error_collector,
            new JiraFieldRetriever($wrapper, $logger, $append_fields_from_create),
            $jira_field_mapper,
            new XmlReportExporter(),
            new SemanticsXMLExporter(),
            new AlwaysThereFieldsExporter(),
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
        );
    }

    /**
     *
     * @param IssueType[] $jira_issue_types
     *
     * @throws JiraConnectionException
     */
    public function exportIssuesToXml(
        PlatformConfiguration $jira_platform_configuration,
        XMLTracker $xml_tracker,
        string $jira_base_url,
        string $jira_project_key,
        array $jira_issue_types,
        IDGenerator $field_id_generator,
        LinkedIssuesCollection $linked_issues_collection,
    ): \SimpleXMLElement {
        $this->logger->debug("Start export Jira issues from project $jira_project_key in mono tracker to XML.");

        $jira_field_mapping_collection = new FieldMappingCollection();
        $status_values_collection      = new StatusValuesCollection(
            $this->wrapper,
            $this->logger
        );

        $this->logger->debug("Handle status");
        $status_values_collection->initCollectionForProject(
            $jira_project_key,
            $field_id_generator,
        );

        $this->logger->debug("Export Always there fields");
        $xml_tracker = $this->always_there_fields_exporter->exportFields(
            $xml_tracker,
            $status_values_collection,
            $jira_field_mapping_collection,
        );

        $this->logger->debug("Export custom jira fields");
        $xml_tracker = $this->exportJiraField(
            $xml_tracker,
            $field_id_generator,
            $jira_project_key,
            $jira_issue_types,
            $jira_platform_configuration,
            $jira_field_mapping_collection,
        );

        $this->logger->debug("Export semantics");
        $tracker_for_semantic_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->semantics_xml_exporter->exportSemantics(
            $tracker_for_semantic_xml,
            $jira_field_mapping_collection,
            $status_values_collection,
        );


        $this->logger->debug("Export reports");
        $tracker_for_reports_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->report_exporter->exportReports(
            $tracker_for_reports_xml,
            $jira_field_mapping_collection,
            $status_values_collection,
            $this->xml_report_all_issues_exporter,
            $this->xml_report_open_issues_exporter,
            $this->xml_report_done_issues_exporter,
            $this->xml_report_created_recently_exporter,
            $this->xml_report_updated_recently_exporter
        );

        $node_tracker = JiraXMLNodeBuilder::buildTrackerXMLNode($xml_tracker, $tracker_for_semantic_xml, $tracker_for_reports_xml);

        if ($this->error_collector->hasError()) {
            foreach ($this->error_collector->getErrors() as $error) {
                $this->logger->error($error);
            }
        }

        return $node_tracker;
    }

    /**
     * @param IssueType[] $jira_issue_types
     */
    private function exportJiraField(
        XMLTracker $xml_tracker,
        IDGenerator $id_generator,
        string $jira_project_id,
        array $jira_issue_types,
        PlatformConfiguration $platform_configuration,
        FieldMappingCollection $field_mapping_collection,
    ): XMLTracker {
        $this->logger->debug("Start exporting jira field structure (custom fields) ...");
        $fields = $this->mergeAllProjectFields(
            $id_generator,
            $jira_project_id,
            $jira_issue_types,
        );

        foreach ($fields as $key => $field) {
            $xml_tracker = $this->field_type_mapper->exportFieldToXml(
                $field,
                $xml_tracker,
                $id_generator,
                $platform_configuration,
                $field_mapping_collection,
            );
        }
        $this->logger->debug("Field structure exported successfully");
        return $xml_tracker;
    }

    /**
     * @param IssueType[] $jira_issue_types
     */
    private function mergeAllProjectFields(
        IDGenerator $id_generator,
        string $jira_project_id,
        array $jira_issue_types,
    ): array {
        $fields = [];
        foreach ($jira_issue_types as $issue_type) {
            foreach ($this->jira_field_retriever->getAllJiraFields($jira_project_id, $issue_type->getId(), $id_generator) as $key => $jira_field) {
                if (! isset($fields[$key])) {
                    $fields[$key] = $jira_field;
                }
            }
        }

        return $fields;
    }
}
