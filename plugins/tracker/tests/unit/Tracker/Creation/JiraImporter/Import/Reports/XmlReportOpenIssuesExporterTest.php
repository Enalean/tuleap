<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Reports;

use Mockery;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldAPIAllowedValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ScalarFieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;

final class XmlReportOpenIssuesExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var SimpleXMLElement
     */
    private $reports_node;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|StatusValuesCollection
     */
    private $status_values_collection;

    /**
     * @var XmlReportDefaultCriteriaExporter
     */
    private $default_criteria_exporter;

    /**
     * @var XmlReportTableExporter
     */
    private $report_table_exporter;

    /**
     * @var FieldMapping
     */
    private $summary_field_mapping;

    /**
     * @var FieldMapping
     */
    private $description_field_mapping;

    /**
     * @var FieldMapping
     */
    private $status_field_mapping;

    /**
     * @var FieldMapping
     */
    private $priority_field_mapping;

    /**
     * @var \XML_SimpleXMLCDATAFactory
     */
    private $cdata_factory;

    /**
     * @var FieldMapping
     */
    private $jira_issue_url_field_mapping;

    protected function setUp(): void
    {
        $this->summary_field_mapping = new ScalarFieldMapping(
            'summary',
            'Summary',
            null,
            'Fsummary',
            'summary',
            Tracker_FormElementFactory::FIELD_STRING_TYPE,
        );

        $this->description_field_mapping = new ScalarFieldMapping(
            'description',
            'Description',
            null,
            'Fdescription',
            'description',
            Tracker_FormElementFactory::FIELD_TEXT_TYPE,
        );

        $this->status_field_mapping = new ListFieldMapping(
            'status',
            'status',
            null,
            'Fstatus',
            'status',
            Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
            \Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [],
        );

        $this->priority_field_mapping = new ListFieldMapping(
            'priority',
            'priority',
            null,
            'Fpriority',
            'priority',
            Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
            \Tracker_FormElement_Field_List_Bind_Static::TYPE,
            [],
        );

        $this->jira_issue_url_field_mapping = new ScalarFieldMapping(
            'jira_issue_url',
            'Link to original issue',
            null,
            'Fjira_issue_url',
            'jira_issue_url',
            Tracker_FormElementFactory::FIELD_STRING_TYPE,
        );

        $tracker_node       = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><trackers />');
        $this->reports_node = $tracker_node->addChild('reports');

        $this->cdata_factory             = new \XML_SimpleXMLCDATAFactory();
        $this->status_values_collection  = Mockery::mock(StatusValuesCollection::class);
        $this->default_criteria_exporter = new XmlReportDefaultCriteriaExporter();
        $this->report_table_exporter     = new XmlReportTableExporter();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testItDoesNothingWhenNoStatusField(): void
    {
        $report_export = new XmlReportOpenIssuesExporter(
            $this->default_criteria_exporter,
            $this->report_table_exporter,
        );

        $report_export->exportJiraLikeReport(
            $this->reports_node,
            $this->status_values_collection,
            $this->summary_field_mapping,
            $this->description_field_mapping,
            null,
            $this->priority_field_mapping,
            $this->jira_issue_url_field_mapping,
            null,
            null,
            null,
        );

        $reports_node = $this->reports_node;
        self::assertEquals(0, $reports_node->count());
    }

    public function testItExportReports(): void
    {
        $this->status_values_collection->shouldReceive('getOpenValues')->andReturn([
            JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(123, $this->getPreWiredIDGenerator(303)),
            JiraFieldAPIAllowedValueRepresentation::buildWithJiraIdOnly(124, $this->getPreWiredIDGenerator(304)),
        ]);

        $report_export = new XmlReportOpenIssuesExporter(
            $this->default_criteria_exporter,
            $this->report_table_exporter,
        );

        $report_export->exportJiraLikeReport(
            $this->reports_node,
            $this->status_values_collection,
            $this->summary_field_mapping,
            $this->description_field_mapping,
            $this->status_field_mapping,
            $this->priority_field_mapping,
            $this->jira_issue_url_field_mapping,
            null,
            null,
            null,
        );

        $reports_node = $this->reports_node;
        self::assertNotNull($reports_node);

        $report_node = $reports_node->report;
        self::assertNotNull($report_node);
        self::assertNull($report_node['is_default']);

        $report_node_name = $report_node->name;
        self::assertEquals("Open issues", $report_node_name);

        $reports_node_description = $report_node->description;
        self::assertEquals('All open issues in this tracker', $reports_node_description);

        $criterias = $report_node->criterias;
        self::assertNotNull($criterias);
        self::assertCount(4, $criterias->children());

        $criterion_01 = $criterias->criteria[0];
        self::assertSame("Fstatus", (string) $criterion_01->field['REF']);
        self::assertSame("1", (string) $criterion_01['is_advanced']);
        self::assertSame("list", (string) $criterion_01->criteria_value['type']);
        self::assertSame("V303", (string) $criterion_01->criteria_value->selected_value[0]['REF']);
        self::assertSame("V304", (string) $criterion_01->criteria_value->selected_value[1]['REF']);

        $criterion_02 = $criterias->criteria[1];
        self::assertSame("Fsummary", (string) $criterion_02->field['REF']);

        $criterion_03 = $criterias->criteria[2];
        self::assertSame("Fdescription", (string) $criterion_03->field['REF']);

        $criterion_04 = $criterias->criteria[3];
        self::assertSame("Fpriority", (string) $criterion_04->field['REF']);

        $renderers_node = $report_node->renderers;
        self::assertNotNull($renderers_node);

        $renderer_node = $renderers_node->renderer;
        self::assertNotNull($renderer_node);

        self::assertEquals("table", $renderer_node['type']);
        self::assertEquals("0", $renderer_node['rank']);
        self::assertEquals("15", $renderer_node['chunksz']);

        $renderer_name = $renderer_node->name;
        self::assertNotNull($renderer_name);

        $columns_node = $renderer_node->columns;
        self::assertNotNull($columns_node);
        self::assertCount(4, $columns_node->children());

        $field_01 = $columns_node->field[0];
        self::assertEquals("Fsummary", (string) $field_01['REF']);

        $field_02 = $columns_node->field[1];
        self::assertEquals("Fstatus", (string) $field_02['REF']);

        $field_03 = $columns_node->field[2];
        self::assertEquals("Fjira_issue_url", (string) $field_03['REF']);

        $field_04 = $columns_node->field[3];
        self::assertEquals("Fpriority", (string) $field_04['REF']);

        self::assertEquals("Results", (string) $renderer_name);
    }

    private function getPreWiredIDGenerator(int $pre_wired_value): IDGenerator
    {
        $id_generator     = new class implements IDGenerator {
            /** @var int */
            public $id;

            public function getNextId(): int
            {
                return $this->id;
            }
        };
        $id_generator->id = $pre_wired_value;
        return $id_generator;
    }
}
