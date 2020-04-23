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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Reports;

use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_String;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

final class XmlReportExporterTest extends TestCase
{
    public function testItExportReports(): void
    {
        $mapping = new FieldMappingCollection();
        $mapping->addMapping(
            new FieldMapping(
                'summary',
                'Fsummary',
                'summary',
                Tracker_FormElement_Field_String::TYPE
            )
        );

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

        $criterias = $tracker_node->criterias;
        $this->assertNotNull($criterias);

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

        $field = $columns_node->field;
        $this->assertEquals("Fsummary", $field['REF']);

        $this->assertEquals("Results", (string) $rendreder_name);
    }
}
