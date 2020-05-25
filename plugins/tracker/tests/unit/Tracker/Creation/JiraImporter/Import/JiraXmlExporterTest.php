<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\ArtifactsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Permissions\PermissionsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Semantic\SemanticsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ContainersXMLCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldAPIAllowedValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraToTuleapFieldTypeMapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;

final class JiraXmlExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|XmlReportExporter
     */
    private $report_exporter;

    /**
     * @var JiraXmlExporter
     */
    private $jira_exporter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JiraToTuleapFieldTypeMapper
     */
    private $field_type_mapper;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JiraFieldRetriever
     */
    private $jira_field_retriever;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FieldXmlExporter
     */
    private $field_xml_exporter;

    /**
     * @var FieldMappingCollection
     */
    private $jira_field_mapping_collection;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PermissionsXMLExporter
     */
    private $permissions_xml_exporter;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsXMLExporter
     */
    private $artifacts_xml_exporter;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticsXMLExporter
     */
    private $semantics_xml_exporter;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|StatusValuesCollection
     */
    private $status_values_collection;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $wrapper;

    protected function setUp(): void
    {
        $this->wrapper = Mockery::mock(ClientWrapper::class);

        $this->field_xml_exporter            = Mockery::mock(FieldXmlExporter::class);
        $this->jira_field_retriever          = Mockery::mock(JiraFieldRetriever::class);
        $error_collector                     = new ErrorCollector();
        $this->field_type_mapper             = Mockery::mock(JiraToTuleapFieldTypeMapper::class);
        $this->report_exporter               = Mockery::mock(XmlReportExporter::class);
        $this->jira_field_mapping_collection = new FieldMappingCollection();
        $this->permissions_xml_exporter      = Mockery::mock(PermissionsXMLExporter::class);
        $this->artifacts_xml_exporter        = Mockery::mock(ArtifactsXMLExporter::class);
        $this->semantics_xml_exporter        = Mockery::mock(SemanticsXMLExporter::class);
        $this->status_values_collection      = new StatusValuesCollection(
            $this->wrapper
        );
        $this->containers_xml_collection_builder = new ContainersXMLCollectionBuilder(
            new \XML_SimpleXMLCDATAFactory()
        );

        $this->jira_exporter = new JiraXmlExporter(
            $this->field_xml_exporter,
            $error_collector,
            $this->jira_field_retriever,
            $this->field_type_mapper,
            $this->report_exporter,
            $this->jira_field_mapping_collection,
            $this->permissions_xml_exporter,
            $this->artifacts_xml_exporter,
            $this->semantics_xml_exporter,
            $this->status_values_collection,
            $this->containers_xml_collection_builder
        );
    }

    public function testItProcessExport(): void
    {
        $xml          = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $trackers_xml = $xml->addChild('trackers');

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return (string) $fieldset_xml->getName() === 'formElements';
                }),
                Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE,
                "artifact_id",
                "Artifact id",
                "artifact_id",
                1,
                false,
                [],
                [],
                $this->jira_field_mapping_collection
            ]
        )->once();

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return (string) $fieldset_xml->getName() === 'formElements';
                }),
                Tracker_FormElementFactory::FIELD_STRING_TYPE,
                "jira_issue_url",
                "Link to original issue",
                "jira_issue_url",
                2,
                false,
                [],
                [],
                $this->jira_field_mapping_collection
            ]
        )->once();

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return (string) $fieldset_xml->getName() === 'formElements';
                }),
                Tracker_FormElementFactory::FIELD_LAST_UPDATE_DATE_TYPE,
                "updated",
                "Last update date",
                "updated",
                5,
                false,
                [],
                [],
                $this->jira_field_mapping_collection
            ]
        )->once();

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return (string) $fieldset_xml->getName() === 'formElements';
                }),
                Tracker_FormElementFactory::FIELD_DATE_TYPE,
                "resolutiondate",
                "Resolved",
                "resolutiondate",
                6,
                false,
                [
                    'display_time' => '1'
                ],
                [],
                $this->jira_field_mapping_collection
            ]
        )->once();

        $this->wrapper->shouldReceive('getUrl')->with("project/TEST/statuses")->once()->andReturn(
            $this->getStatusesAPIResponse()
        );

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return (string) $fieldset_xml->getName() === 'formElements';
                }),
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                "status",
                "Status",
                "status",
                7,
                false,
                [],
                Mockery::on(function (array $statuses) {
                    $status = $statuses[0];
                    assert($status instanceof JiraFieldAPIAllowedValueRepresentation);
                    return $status->getId() === 9000003 && $status->getName() === "In Progress";
                }),
                $this->jira_field_mapping_collection
            ]
        )->once();

        $this->report_exporter->shouldReceive('exportReports')->once();
        $this->permissions_xml_exporter->shouldReceive('exportFieldsPermissions')->once();
        $this->artifacts_xml_exporter->shouldReceive('exportArtifacts')->once();
        $this->semantics_xml_exporter->shouldReceive('exportSemantics')->once();

        $this->jira_field_retriever->shouldReceive('getAllJiraFields')->once();
        $this->jira_exporter->exportJiraToXml($trackers_xml, "URLinstance", "TEST", "Story");
    }

    private function getStatusesAPIResponse(): array
    {
        return [
            [
                'self' => 'URL/rest/api/latest/issuetype/10002',
                'id' => '10002' ,
                'name' => 'Story' ,
                'subtask' => false,
                'statuses' => [
                    [
                        'self' => 'URL/rest/api/latest/status/3',
                        'description' => 'This issue is being actively worked on at the moment by the assignee.',
                        'iconUrl' => 'URL/images/icons/statuses/inprogress.png',
                        'name' => 'In Progress',
                        'untranslatedName' => 'In Progress',
                        'id' => '3',
                        'statusCategory' => [
                            'self' => 'URL/rest/api/latest/statuscategory/4',
                            'id' => 4,
                            'key' => 'indeterminate',
                            'colorName' => 'yellow',
                            'name' => 'In Progress'
                        ]
                    ]
                ]
            ]
        ];
    }
}
