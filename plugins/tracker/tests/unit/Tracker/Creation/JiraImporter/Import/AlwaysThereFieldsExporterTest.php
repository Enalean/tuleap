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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ContainersXMLCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ContainersXMLCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldAPIAllowedValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub;

class AlwaysThereFieldsExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    /**
     * @var AlwaysThereFieldsExporter
     */
    private $exporter;

    /**
     * @var Structure\ContainersXMLCollection
     */
    private $containers_collection;

    /**
     * @var FieldMappingCollection
     */
    private $field_mapping_collection;

    /**
     * @var StatusValuesCollection
     */
    private $status_values_collection;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FieldXmlExporter
     */
    private $field_xml_exporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->field_xml_exporter = Mockery::mock(FieldXmlExporter::class);
        $this->exporter           = new AlwaysThereFieldsExporter(
            $this->field_xml_exporter
        );

        $field_id_generator = new FieldAndValueIDGenerator();

        $root_form_elements          = new SimpleXMLElement("<formElements/>");
        $this->containers_collection = new ContainersXMLCollection($field_id_generator);

        (new ContainersXMLCollectionBuilder())
            ->buildCollectionOfJiraContainersXML($root_form_elements, $this->containers_collection);

        $this->field_mapping_collection = new FieldMappingCollection($field_id_generator);

        $wrapper = new class extends JiraCloudClientStub {
            public function __construct()
            {
                $this->urls[ClientWrapper::JIRA_CORE_BASE_URL . "/project/TEST/statuses"] = [
                    [
                    'self' => 'URL/rest/api/3/issuetype/10002',
                    'id' => '10002' ,
                    'name' => 'Story' ,
                    'subtask' => false,
                    'statuses' => [
                        [
                            'self' => 'URL/rest/api/3/status/3',
                            'description' => 'This issue is being actively worked on at the moment by the assignee.',
                            'iconUrl' => 'URL/images/icons/statuses/inprogress.png',
                            'name' => 'In Progress',
                            'untranslatedName' => 'In Progress',
                            'id' => '3',
                            'statusCategory' => [
                                'self' => 'URL/rest/api/3/statuscategory/4',
                                'id' => 4,
                                'key' => 'indeterminate',
                                'colorName' => 'yellow',
                                'name' => 'In Progress',
                            ],
                        ],
                    ],
                    ],
                    ];
            }
        };

        $this->logger                   = Mockery::mock(LoggerInterface::class);
        $this->status_values_collection = new StatusValuesCollection(
            $wrapper,
            $this->logger
        );
        $this->logger->shouldReceive('debug');

        $this->status_values_collection->initCollectionForProjectAndIssueType(
            'TEST',
            '10002',
            $field_id_generator
        );
    }

    public function testItProcessATFExport(): void
    {
        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return isset($fieldset_xml->formElements);
                }),
                Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE,
                "artifact_id",
                "Artifact id",
                "artifact_id",
                AlwaysThereFieldsExporter::JIRA_ARTIFACT_ID_RANK,
                false,
                [],
                [],
                $this->field_mapping_collection,
                null,
            ]
        )->once();

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return isset($fieldset_xml->formElements);
                }),
                Tracker_FormElementFactory::FIELD_STRING_TYPE,
                "jira_issue_url",
                "Link to original issue",
                "jira_issue_url",
                AlwaysThereFieldsExporter::JIRA_LINK_RANK,
                false,
                [],
                [],
                $this->field_mapping_collection,
                null,
            ]
        )->once();

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return isset($fieldset_xml->formElements);
                }),
                Tracker_FormElementFactory::FIELD_SUBMITTED_BY_TYPE,
                "creator",
                "Created by",
                "creator",
                AlwaysThereFieldsExporter::JIRA_DESCRIPTION_RANK,
                false,
                [],
                [],
                $this->field_mapping_collection,
                null,
            ]
        )->once();

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return isset($fieldset_xml->formElements);
                }),
                Tracker_FormElementFactory::FIELD_SUBMITTED_ON_TYPE,
                "created",
                "Creation date",
                "created",
                AlwaysThereFieldsExporter::JIRA_CREATED_RANK,
                false,
                [],
                [],
                $this->field_mapping_collection,
                null,
            ]
        )->once();

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return isset($fieldset_xml->formElements);
                }),
                Tracker_FormElementFactory::FIELD_LAST_UPDATE_DATE_TYPE,
                "updated",
                "Last update date",
                "updated",
                AlwaysThereFieldsExporter::JIRA_UPDATED_ON_RANK,
                false,
                [],
                [],
                $this->field_mapping_collection,
                null,
            ]
        )->once();

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return isset($fieldset_xml->formElements);
                }),
                Tracker_FormElementFactory::FIELD_DATE_TYPE,
                "resolutiondate",
                "Resolved",
                "resolutiondate",
                AlwaysThereFieldsExporter::JIRA_RESOLUTION_DATE_RANK,
                false,
                [
                    'display_time' => '1',
                ],
                [],
                $this->field_mapping_collection,
                null,
            ]
        )->once();

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return isset($fieldset_xml->formElements);
                }),
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
                "status",
                "Status",
                "status",
                AlwaysThereFieldsExporter::JIRA_ATTACHMENT_RANK,
                false,
                [],
                Mockery::on(function (array $statuses) {
                    $status = $statuses[0];
                    assert($status instanceof JiraFieldAPIAllowedValueRepresentation);
                    return $status->getId() === 3 && $status->getName() === "In Progress";
                }),
                $this->field_mapping_collection,
                \Tracker_FormElement_Field_List_Bind_Static::TYPE,
            ]
        )->once();

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                Mockery::on(function (SimpleXMLElement $fieldset_xml) {
                    return isset($fieldset_xml->formElements);
                }),
                Tracker_FormElementFactory::FIELD_FILE_TYPE,
                "attachment",
                "Attachments",
                "attachment",
                AlwaysThereFieldsExporter::JIRA_ATTACHMENT_RANK,
                false,
                [],
                [],
                $this->field_mapping_collection,
                null,
            ]
        )->once();

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->containers_collection->getContainerByName(ContainersXMLCollectionBuilder::LINKS_FIELDSET_NAME),
                Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS,
                'issuelinks',
                'Links',
                'issuelinks',
                1,
                false,
                [],
                [],
                $this->field_mapping_collection,
                null,
            ]
        )->once();

        $this->field_xml_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->containers_collection->getContainerByName(ContainersXMLCollectionBuilder::LINKS_FIELDSET_NAME),
                Tracker_FormElementFactory::FIELD_CROSS_REFERENCES,
                'org.tuleap.crossreferences',
                'References',
                'org.tuleap.crossreferences',
                2,
                false,
                [],
                [],
                $this->field_mapping_collection,
                null,
            ]
        )->once();

        $this->exporter->exportFields(
            $this->containers_collection,
            $this->field_mapping_collection,
            $this->status_values_collection
        );
    }
}
