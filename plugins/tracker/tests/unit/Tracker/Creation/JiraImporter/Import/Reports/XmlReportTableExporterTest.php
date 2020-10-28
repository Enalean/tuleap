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
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use XML_SimpleXMLCDATAFactory;

final class XmlReportTableExporterTest extends TestCase
{
    public function testItCreatesATableRenderer(): void
    {
        $summary_field_mapping = new FieldMapping(
            'summary',
            'Fsummary',
            'summary',
            Tracker_FormElementFactory::FIELD_STRING_TYPE,
            null
        );

        $description_field_mapping = new FieldMapping(
            'description',
            'Fdescription',
            'description',
            Tracker_FormElementFactory::FIELD_TEXT_TYPE,
            null
        );

        $table_renderer_exporter = new XmlReportTableExporter(
            new XML_SimpleXMLCDATAFactory()
        );

        $report_node = new \SimpleXMLElement('<report></report>');

        $table_renderer_exporter->exportResultsTable(
            $report_node,
            [
                $summary_field_mapping,
                $description_field_mapping
            ]
        );

        $renderers = $report_node->renderers;
        $this->assertNotNull($renderers);

        $renderer = $renderers->renderer;
        $this->assertNotNull($renderer);

        $this->assertSame('0', (string) $renderer['rank']);
        $this->assertSame('table', (string) $renderer['type']);
        $this->assertSame('15', (string) $renderer['chunksz']);


        $this->assertSame('Results', (string) $renderer->name);

        $column_01 = $renderer->columns->field[0];
        $this->assertSame('Fsummary', (string) $column_01['REF']);

        $column_02 = $renderer->columns->field[1];
        $this->assertSame('Fdescription', (string) $column_02['REF']);
    }
}
