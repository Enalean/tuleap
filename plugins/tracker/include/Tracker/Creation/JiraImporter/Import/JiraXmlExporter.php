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
use Tracker_FormElement_Field_ArtifactId;
use Tracker_FormElement_Field_String;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraToTuleapFieldTypeMapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\Creation\JiraImporter\JiraCredentials;
use XML_SimpleXMLCDATAFactory;

class JiraXmlExporter
{
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

    public function __construct(
        FieldXmlExporter $field_xml_exporter,
        ErrorCollector $error_collector,
        JiraFieldRetriever $jira_field_retriever,
        JiraToTuleapFieldTypeMapper $field_type_mapper,
        XmlReportExporter $report_exporter
    ) {
        $this->field_xml_exporter   = $field_xml_exporter;
        $this->error_collector      = $error_collector;
        $this->jira_field_retriever = $jira_field_retriever;
        $this->field_type_mapper    = $field_type_mapper;
        $this->report_exporter      = $report_exporter;
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
            new XmlReportExporter($cdata_factory)
        );
    }

    /**
     * @throws JiraConnectionException
     */
    public function exportJiraToXml(SimpleXMLElement $node_tracker): void
    {
        $form_elements = $node_tracker->addChild('formElements');

        $node_jira_atf_form_elements = $this->field_xml_exporter->exportFieldsetWithName(
            $form_elements,
            'jira_atf',
            'Jira ATF',
            0,
            1
        );

        $this->field_xml_exporter->exportField(
            $node_jira_atf_form_elements,
            Tracker_FormElement_Field_ArtifactId::TYPE,
            "artifact id",
            "Artifact id",
            "artifact_id",
            1,
            "0"
        );

        $this->field_xml_exporter->exportField(
            $node_jira_atf_form_elements,
            Tracker_FormElement_Field_String::TYPE,
            "jira_artifact_url",
            "Link to original artifact",
            "jira_artifact_url",
            2,
            "0"
        );
        $this->exportJiraField($node_jira_atf_form_elements);

        $node_tracker->addChild('semantics');
        $node_tracker->addChild('rules');
        $this->report_exporter->exportReports($node_tracker);
        $node_tracker->addChild('workflow');
        $node_tracker->addChild('permissions');

        if ($this->error_collector->hasError()) {
            foreach ($this->error_collector->getErrors() as $error) {
                echo $error . '<br>';
            }
        }
    }

    private function exportJiraField(SimpleXMLElement $node_jira_atf_form_elements): void
    {
        $fields            = $this->jira_field_retriever->getAllJiraFields();

        foreach ($fields as $key => $field) {
            $this->field_type_mapper->exportFieldToXml(
                $field,
                '1',
                $node_jira_atf_form_elements
            );
        }
    }
}
