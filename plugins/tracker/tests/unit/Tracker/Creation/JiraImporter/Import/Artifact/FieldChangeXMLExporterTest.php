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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use XML_SimpleXMLCDATAFactory;

class FieldChangeXMLExporterTest extends TestCase
{
    public function testItExportsFloatChangeInXML(): void
    {
        $exporter = new FieldChangeXMLExporter(
            new XML_SimpleXMLCDATAFactory()
        );

        $mapping = new FieldMapping(
            'number',
            'Fnumber',
            'Number',
            'float'
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $exporter->exportFieldChange(
            $mapping,
            $changeset_node,
            '4.5'
        );

        $this->assertNotNull($changeset_node->field_change);
        $field_change_node = $changeset_node->field_change;

        $this->assertSame("float", (string) $field_change_node['type']);
        $this->assertSame("Number", (string) $field_change_node['field_name']);
        $this->assertSame("4.5", (string) $field_change_node->value);
    }
}
