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
 */

declare(strict_types=1);

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;
use Tuleap\Tracker\XML\Exporter\FileInfoXMLExporter;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FileInfoXMLExporter&MockObject $file_exporter;

    private Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter $exporter;

    private SimpleXMLElement $artifact_xml;

    private SimpleXMLElement $changeset_xml;

    private Artifact $artifact;

    private Tracker_Artifact_ChangesetValue_File&MockObject $changeset_value;

    protected function setUp(): void
    {
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->file_exporter = $this->createMock(FileInfoXMLExporter::class);
        $this->exporter      = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter(
            $this->file_exporter
        );

        $field = FileFieldBuilder::aFileField(1001)->withName('attachments')->build();

        $this->changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_File::class);
        $this->changeset_value->method('getField')->willReturn($field);

        $this->artifact = ArtifactTestBuilder::anArtifact(101)->build();
    }

    public function testItExportsEmptyValueIfThereIsNoFileChange(): void
    {
        $this->changeset_value->method('getFiles')->willReturn([]);

        $this->file_exporter->expects($this->never())->method('add');

        $this->exporter->export($this->artifact_xml, $this->changeset_xml, $this->artifact, $this->changeset_value);

        $this->assertEquals('attachments', (string) $this->changeset_xml->field_change['field_name']);
        $this->assertEquals('file', (string) $this->changeset_xml->field_change['type']);
        $this->assertCount(1, $this->changeset_xml->field_change->value);
        $this->assertNull($this->changeset_xml->field_change->value['ref']);
    }

    public function testItExportsFileChanges(): void
    {
        $file_1 = new Tracker_FileInfo(
            190,
            $this->createMock(Tracker_FormElement_Field_File::class),
            101,
            '',
            'document.txt',
            '2',
            'text/plain'
        );
        $file_2 = new Tracker_FileInfo(
            191,
            $this->createMock(Tracker_FormElement_Field_File::class),
            102,
            '',
            'landscape.png',
            '256',
            'image/png'
        );
        $this->changeset_value->method('getFiles')->willReturn([$file_1, $file_2]);

        $this->file_exporter
            ->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(static fn (Artifact $artifact, Tracker_FileInfo $file) => match ($file) {
                $file_1, $file_2 => true,
            });

        $this->exporter->export($this->artifact_xml, $this->changeset_xml, $this->artifact, $this->changeset_value);

        $this->assertEquals('attachments', (string) $this->changeset_xml->field_change['field_name']);
        $this->assertEquals('file', (string) $this->changeset_xml->field_change['type']);
        $this->assertCount(2, $this->changeset_xml->field_change->value);
        $this->assertEquals('fileinfo_190', $this->changeset_xml->field_change->value[0]['ref']);
        $this->assertEquals('fileinfo_191', $this->changeset_xml->field_change->value[1]['ref']);
    }
}
