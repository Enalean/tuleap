<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeListBuilder;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_XML_Exporter_ChangesetValue_ChangesetValueListXMLExporterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueListXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_Artifact_ChangesetValue_PermissionsOnArtifact */
    private $changeset_value;

    /** @var Tracker_FormElement_Field */
    private $field;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueListXMLExporter(
            new FieldChangeListBuilder(
                new XML_SimpleXMLCDATAFactory(),
                \Mockery::spy(\UserXMLExporter::class)
            )
        );
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');

        $bind_static = new Tracker_FormElement_Field_List_Bind_Static(
            null,
            null,
            null,
            null,
            null
        );

        $this->field = Mockery::spy(Tracker_FormElement_Field_MultiSelectbox::class);
        $this->field->shouldReceive('getBind')->andReturn($bind_static);
        $this->field->shouldReceive('getName')->andReturn('status');

        $this->changeset_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_List::class);
        $this->changeset_value->shouldReceive('getField')->andReturns($this->field);
    }

    public function testItCreatesFieldChangeNodeWithOneValueInChangesetNode(): void
    {
        $this->changeset_value->shouldReceive('getValue')->andReturns([
            '101'
        ]);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEquals('list', (string) $field_change['type']);
        $this->assertEquals('static', (string) $field_change['bind']);
        $this->assertEquals('101', (string) $field_change->value);
        $this->assertEquals('id', (string) $field_change->value['format']);
    }

    public function testItCreatesFieldChangeNodeWithMultipleValuesInChangesetNode(): void
    {
        $this->changeset_value->shouldReceive('getValue')->andReturns([
            '101',
            '102'
        ]);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEquals('list', (string) $field_change['type']);
        $this->assertEquals('static', (string) $field_change['bind']);
        $this->assertEquals('101', (string) $field_change->value[0]);
        $this->assertEquals('id', (string) $field_change->value[0]['format']);
        $this->assertEquals('102', (string) $field_change->value[1]);
        $this->assertEquals('id', (string) $field_change->value[1]['format']);
    }
}
