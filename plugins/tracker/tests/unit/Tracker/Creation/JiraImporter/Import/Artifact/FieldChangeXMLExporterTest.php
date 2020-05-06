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
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use XML_SimpleXMLCDATAFactory;

class FieldChangeXMLExporterTest extends TestCase
{
    /**
     * @var FieldChangeXMLExporter
     */
    private $exporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exporter = new FieldChangeXMLExporter(
            new FieldChangeDateBuilder(
                new XML_SimpleXMLCDATAFactory()
            ),
            new FieldChangeStringBuilder(
                new XML_SimpleXMLCDATAFactory()
            ),
            new XML_SimpleXMLCDATAFactory()
        );
    }

    public function testItExportsFloatChangeInXML(): void
    {
        $mapping = new FieldMapping(
            'number',
            'Fnumber',
            'Number',
            'float'
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $submitted_on = new SimpleXMLElement('<submitted_on/>');
        $this->exporter->exportFieldChange(
            $mapping,
            $changeset_node,
            $submitted_on,
            '4.5'
        );

        $this->assertNotNull($changeset_node->field_change);
        $field_change_node = $changeset_node->field_change;

        $this->assertSame("float", (string) $field_change_node['type']);
        $this->assertSame("Number", (string) $field_change_node['field_name']);
        $this->assertSame("4.5", (string) $field_change_node->value);
    }

    public function testItExportsTheUpdateDateAsSubmittedOnDateInXML(): void
    {
        $mapping = new FieldMapping(
            'update',
            'Fupdate',
            'Updated',
            'lud'
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $submitted_on = new SimpleXMLElement('<submitted_on/>');
        $this->exporter->exportFieldChange(
            $mapping,
            $changeset_node,
            $submitted_on,
            '2020-04-21T09:31:44.481+0200'
        );

        $this->assertSame("2020-04-21T09:31:44.481+0200", (string) $submitted_on);
    }

    public function testItDoesNotUpdateTheSubmissionDateWhenUpdatedDataIsNotProvided(): void
    {
        $mapping = new FieldMapping(
            'number',
            'Fnumber',
            'Number',
            'float'
        );

        $changeset_node = new SimpleXMLElement('<changeset/>');
        $submitted_on = new SimpleXMLElement('<submitted_on format="ISO8601">2020-04-29T08:45:46+02:00</submitted_on>');

        $this->exporter->exportFieldChange(
            $mapping,
            $changeset_node,
            $submitted_on,
            '4.5'
        );

        $this->assertSame("2020-04-29T08:45:46+02:00", (string) $submitted_on);
    }
}
