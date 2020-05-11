<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\ArtifactsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\FieldChangeXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Permissions\PermissionsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Semantic\SemanticsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraToTuleapFieldTypeMapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\Creation\JiraImporter\JiraCredentials;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use UserManager;
use XML_SimpleXMLCDATAFactory;

class JiraXmlExporter
{
    public const JIRA_LINK_FIELD_NAME        = "jira_issue_url";
    public const JIRA_SUMMARY_FIELD_NAME     = "summary";
    public const JIRA_DESCRIPTION_FIELD_NAME = "description";
    public const JIRA_UPDATED_ON_NAME        = "updated";
    public const JIRA_RESOLUTION_DATE_NAME   = "resolutiondate";

    public const JIRA_LINK_FIELD_ID        = "jira_issue_url";
    public const JIRA_ARTIFACT_ID_FIELD_ID = "artifact_id";

    /**
     * @var FieldXmlExporter
     */
    private $field_xml_exporter;
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
     * @var FieldMappingCollection
     */
    private $jira_field_mapping_collection;

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

    public function __construct(
        FieldXmlExporter $field_xml_exporter,
        ErrorCollector $error_collector,
        JiraFieldRetriever $jira_field_retriever,
        JiraToTuleapFieldTypeMapper $field_type_mapper,
        XmlReportExporter $report_exporter,
        FieldMappingCollection $field_mapping_collection,
        PermissionsXMLExporter $permissions_xml_exporter,
        ArtifactsXMLExporter $artifacts_xml_exporter,
        SemanticsXMLExporter $semantics_xml_exporter
    ) {
        $this->field_xml_exporter            = $field_xml_exporter;
        $this->error_collector               = $error_collector;
        $this->jira_field_retriever          = $jira_field_retriever;
        $this->field_type_mapper             = $field_type_mapper;
        $this->report_exporter               = $report_exporter;
        $this->jira_field_mapping_collection = $field_mapping_collection;
        $this->permissions_xml_exporter      = $permissions_xml_exporter;
        $this->artifacts_xml_exporter        = $artifacts_xml_exporter;
        $this->semantics_xml_exporter        = $semantics_xml_exporter;
    }

    public static function build(
        JiraCredentials $jira_credentials
    ): self {
        $error_collector = new ErrorCollector();

        $cdata_factory = new XML_SimpleXMLCDATAFactory();

        $wrapper = ClientWrapper::build($jira_credentials);

        $field_xml_exporter = new FieldXmlExporter(new XML_SimpleXMLCDATAFactory());
        $jira_field_mapper  = new JiraToTuleapFieldTypeMapper($field_xml_exporter, $error_collector);

        return new self(
            new FieldXmlExporter(
                $cdata_factory
            ),
            $error_collector,
            new JiraFieldRetriever($wrapper),
            $jira_field_mapper,
            new XmlReportExporter($cdata_factory),
            new FieldMappingCollection(),
            new PermissionsXMLExporter(),
            new ArtifactsXMLExporter(
                $wrapper,
                new XML_SimpleXMLCDATAFactory(),
                UserManager::instance(),
                new FieldChangeXMLExporter(
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
                    )
                ),
                new FieldChangeStringBuilder(
                    new XML_SimpleXMLCDATAFactory()
                )
            ),
            new SemanticsXMLExporter()
        );
    }

    /**
     * @throws JiraConnectionException
     */
    public function exportJiraToXml(
        SimpleXMLElement $node_tracker,
        string $jira_base_url,
        string $jira_project_id,
        string $jira_issue_type_name
    ): void {
        $form_elements = $node_tracker->addChild('formElements');

        $node_jira_atf_form_elements = $this->field_xml_exporter->exportFieldsetWithName(
            $form_elements,
            'jira_atf',
            'Jira ATF',
            0,
            1
        );

        $node_jira_custom_form_elements = $this->field_xml_exporter->exportFieldsetWithName(
            $form_elements,
            'jira_custom',
            'Jira Custom Fields',
            0,
            2
        );

        $this->field_xml_exporter->exportField(
            $node_jira_atf_form_elements,
            Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE,
            self::JIRA_ARTIFACT_ID_FIELD_ID,
            "Artifact id",
            self::JIRA_ARTIFACT_ID_FIELD_ID,
            1,
            false,
            [],
            $this->jira_field_mapping_collection
        );

        $this->field_xml_exporter->exportField(
            $node_jira_atf_form_elements,
            Tracker_FormElementFactory::FIELD_STRING_TYPE,
            self::JIRA_LINK_FIELD_NAME,
            "Link to original issue",
            self::JIRA_LINK_FIELD_ID,
            2,
            false,
            [],
            $this->jira_field_mapping_collection
        );

        $this->field_xml_exporter->exportField(
            $node_jira_atf_form_elements,
            Tracker_FormElementFactory::FIELD_LAST_UPDATE_DATE_TYPE,
            self::JIRA_UPDATED_ON_NAME,
            "Last update date",
            self::JIRA_UPDATED_ON_NAME,
            5,
            false,
            [],
            $this->jira_field_mapping_collection
        );

        $this->field_xml_exporter->exportField(
            $node_jira_atf_form_elements,
            Tracker_FormElementFactory::FIELD_DATE_TYPE,
            self::JIRA_RESOLUTION_DATE_NAME,
            "Resolved",
            self::JIRA_RESOLUTION_DATE_NAME,
            6,
            false,
            [
                'display_time' => '1'
            ],
            $this->jira_field_mapping_collection
        );

        $this->exportJiraField(
            $node_jira_atf_form_elements,
            $node_jira_custom_form_elements,
            $jira_project_id,
            $jira_issue_type_name
        );

        $this->semantics_xml_exporter->exportSemantics($node_tracker, $this->jira_field_mapping_collection);

        $node_tracker->addChild('rules');
        $this->report_exporter->exportReports($node_tracker, $this->jira_field_mapping_collection);
        $node_tracker->addChild('workflow');

        $this->permissions_xml_exporter->exportFieldsPermissions(
            $node_tracker,
            $this->jira_field_mapping_collection
        );

        $this->artifacts_xml_exporter->exportArtifacts(
            $node_tracker,
            $this->jira_field_mapping_collection,
            $jira_base_url,
            $jira_project_id,
            $jira_issue_type_name
        );

        if ($this->error_collector->hasError()) {
            foreach ($this->error_collector->getErrors() as $error) {
                echo $error . '<br>';
            }
        }
    }

    private function exportJiraField(
        SimpleXMLElement $node_jira_atf_form_elements,
        SimpleXMLElement $node_jira_custom_form_elements,
        string $jira_project_id,
        string $jira_issue_type_name
    ): void {
        $fields = $this->jira_field_retriever->getAllJiraFields($jira_project_id, $jira_issue_type_name);
        foreach ($fields as $key => $field) {
            $this->field_type_mapper->exportFieldToXml(
                $field,
                $node_jira_atf_form_elements,
                $node_jira_custom_form_elements,
                $this->jira_field_mapping_collection
            );
        }
    }
}
