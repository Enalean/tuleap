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

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;

final class XmlReportAllIssuesExporterTest extends TestCase
{
    /**
     * @var XmlReportAllIssuesExporter
     */
    private $report_export;

    /**
     * @var SimpleXMLElement
     */
    private $reports_node;

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
     * @var FieldMapping
     */
    private $jira_issue_url_field_mapping;

    protected function setUp(): void
    {
        $this->summary_field_mapping = new FieldMapping(
            'summary',
            'Fsummary',
            'summary',
            Tracker_FormElementFactory::FIELD_STRING_TYPE,
            null
        );

        $this->description_field_mapping = new FieldMapping(
            'description',
            'Fdescription',
            'description',
            Tracker_FormElementFactory::FIELD_TEXT_TYPE,
            null
        );

        $this->status_field_mapping = new FieldMapping(
            'status',
            'Fstatus',
            'status',
            Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
            \Tracker_FormElement_Field_List_Bind_Static::TYPE
        );

        $this->priority_field_mapping = new FieldMapping(
            'priority',
            'Fpriority',
            'priority',
            Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE,
            \Tracker_FormElement_Field_List_Bind_Static::TYPE
        );

        $this->jira_issue_url_field_mapping = new FieldMapping(
            'jira_issue_url',
            'Fjira_issue_url',
            'jira_issue_url',
            Tracker_FormElementFactory::FIELD_STRING_TYPE,
            null
        );

        $tracker_node = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><trackers />');
        $this->reports_node = $tracker_node->addChild('reports');

        $cdata_factory = new \XML_SimpleXMLCDATAFactory();
        $this->report_export       = new XmlReportAllIssuesExporter(
            new XmlReportDefaultCriteriaExporter(),
            $cdata_factory,
            new XmlReportTableExporter($cdata_factory)
        );
    }

    public function testItExportReports(): void
    {
        $this->report_export->exportJiraLikeReport(
            $this->reports_node,
            $this->summary_field_mapping,
            $this->description_field_mapping,
            $this->status_field_mapping,
            $this->priority_field_mapping,
            $this->jira_issue_url_field_mapping,
            null,
            null
        );

        $reports_node = $this->reports_node;
        $this->assertNotNull($reports_node);

        $report_node = $reports_node->report;
        $this->assertNotNull($report_node);
        $this->assertEquals('1', $report_node['is_default']);

        $report_node_name = $report_node->name;
        $this->assertEquals("All issues", $report_node_name);

        $reports_node_description = $report_node->description;
        $this->assertEquals('All the issues in this tracker', $reports_node_description);

        $criterias = $report_node->criterias;
        $this->assertNotNull($criterias);
        $this->assertCount(4, $criterias->children());

        $criterion_01 = $criterias->criteria[0];
        $this->assertSame("Fsummary", (string) $criterion_01->field['REF']);

        $criterion_02 = $criterias->criteria[1];
        $this->assertSame("Fdescription", (string) $criterion_02->field['REF']);

        $criterion_03 = $criterias->criteria[2];
        $this->assertSame("Fstatus", (string) $criterion_03->field['REF']);

        $criterion_04 = $criterias->criteria[3];
        $this->assertSame("Fpriority", (string) $criterion_04->field['REF']);

        $renderers_node = $report_node->renderers;
        $this->assertNotNull($renderers_node);

        $renderer_node = $renderers_node->renderer;
        $this->assertNotNull($renderer_node);

        $this->assertEquals("table", $renderer_node['type']);
        $this->assertEquals("0", $renderer_node['rank']);
        $this->assertEquals("15", $renderer_node['chunksz']);

        $renderer_name = $renderer_node->name;
        $this->assertNotNull($renderer_name);

        $columns_node = $renderer_node->columns;
        $this->assertNotNull($columns_node);
        $this->assertCount(4, $columns_node->children());

        $field_01 = $columns_node->field[0];
        $this->assertEquals("Fsummary", (string) $field_01['REF']);

        $field_02 = $columns_node->field[1];
        $this->assertEquals("Fstatus", (string) $field_02['REF']);

        $field_03 = $columns_node->field[2];
        $this->assertEquals("Fjira_issue_url", (string) $field_03['REF']);

        $field_04 = $columns_node->field[3];
        $this->assertEquals("Fpriority", (string) $field_04['REF']);

        $this->assertEquals("Results", (string) $renderer_name);
    }
}
