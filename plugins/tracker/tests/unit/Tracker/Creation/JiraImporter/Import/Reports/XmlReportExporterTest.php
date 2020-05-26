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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Reports;

use PHPUnit\Framework\TestCase;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

final class XmlReportExporterTest extends TestCase
{
    public function testItExportReports(): void
    {
        $mapping = $this->buildMapping();

        $tracker_node  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><trackers />');
        $report_export = new XmlReportExporter(new \XML_SimpleXMLCDATAFactory());
        $report_export->exportReports($tracker_node, $mapping);

        $reports_node = $tracker_node->reports;
        $this->assertNotNull($reports_node);

        $report_node = $reports_node->report;
        $this->assertNotNull($report_node);

        $report_node_name = $report_node->name;
        $this->assertEquals("Default", $report_node_name);

        $reports_node_description = $report_node->description;
        $this->assertEquals('The system default artifact report', $reports_node_description);

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

        $rendreder_name = $renderer_node->name;
        $this->assertNotNull($rendreder_name);

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

        $this->assertEquals("Results", (string) $rendreder_name);
    }

    private function buildMapping(): FieldMappingCollection
    {
        $mapping = new FieldMappingCollection();
        $mapping->addMapping(
            new FieldMapping(
                'summary',
                'Fsummary',
                'summary',
                Tracker_FormElementFactory::FIELD_STRING_TYPE
            )
        );
        $mapping->addMapping(
            new FieldMapping(
                'description',
                'Fdescription',
                'description',
                Tracker_FormElementFactory::FIELD_TEXT_TYPE
            )
        );
        $mapping->addMapping(
            new FieldMapping(
                'status',
                'Fstatus',
                'status',
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE
            )
        );
        $mapping->addMapping(
            new FieldMapping(
                'priority',
                'Fpriority',
                'priority',
                Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE
            )
        );
        $mapping->addMapping(
            new FieldMapping(
                'jira_issue_url',
                'Fjira_issue_url',
                'jira_issue_url',
                Tracker_FormElementFactory::FIELD_STRING_TYPE
            )
        );

        return $mapping;
    }
}
