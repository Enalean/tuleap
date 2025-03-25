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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue;

use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List_Bind_Static;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use XML_SimpleXMLCDATAFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetValueListXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ChangesetValueListXMLExporter $exporter;

    private SimpleXMLElement $changeset_xml;

    private SimpleXMLElement $artifact_xml;

    private Tracker_Artifact_ChangesetValue_List&MockObject $changeset_value;

    private Tracker_FormElement_Field $field;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter      = new ChangesetValueListXMLExporter(
            new FieldChangeListBuilder(
                new XML_SimpleXMLCDATAFactory(),
                $this->createMock(\UserXMLExporter::class)
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

        $this->field = \Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder::aListField(1001)
            ->withMultipleValues()
            ->withName('status')
            ->build();

        $this->field->setBind($bind_static);

        $this->changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue_List::class);
        $this->changeset_value->method('getField')->willReturn($this->field);
    }

    public function testItCreatesFieldChangeNodeWithOneValueInChangesetNode(): void
    {
        $this->changeset_value->method('getValue')->willReturn([
            '101',
        ]);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            ArtifactTestBuilder::anArtifact(101)->build(),
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
        $this->changeset_value->method('getValue')->willReturn([
            '101',
            '102',
        ]);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            ArtifactTestBuilder::anArtifact(101)->build(),
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
