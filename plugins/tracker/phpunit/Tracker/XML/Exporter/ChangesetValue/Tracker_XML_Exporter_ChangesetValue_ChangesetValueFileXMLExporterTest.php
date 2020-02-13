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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\XML\Exporter\FileInfoXMLExporter;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|FileInfoXMLExporter */
    private $file_exporter;

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact */
    private $artifact;

    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_ChangesetValue_File */
    private $changeset_value;

    protected function setUp(): void
    {
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->file_exporter = Mockery::mock(FileInfoXMLExporter::class);
        $this->exporter = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter(
            $this->file_exporter
        );

        $field = Mockery::mock(Tracker_FormElement_Field_File::class)
            ->shouldReceive(['getName' => 'attachments'])
            ->getMock();

        $this->changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_File::class)
            ->shouldReceive(['getField' => $field])
            ->getMock();

        $this->artifact = Mockery::mock(Tracker_Artifact::class);
    }

    public function testItExportsEmptyValueIfThereIsNoFileChange(): void
    {
        $this->changeset_value->shouldReceive('getFiles')->andReturn([]);

        $this->file_exporter->shouldReceive('add')->never();

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
            Mockery::mock(\Tracker_FormElement_Field_File::class),
            101,
            '',
            'document.txt',
            "2",
            'text/plain'
        );
        $file_2 = new Tracker_FileInfo(
            191,
            Mockery::mock(\Tracker_FormElement_Field_File::class),
            102,
            '',
            'landscape.png',
            "256",
            'image/png'
        );
        $this->changeset_value->shouldReceive('getFiles')->andReturn([$file_1, $file_2]);

        $this->file_exporter
            ->shouldReceive('add')
            ->with($this->artifact, $file_1)
            ->once();
        $this->file_exporter
            ->shouldReceive('add')
            ->with($this->artifact, $file_2)
            ->once();

        $this->exporter->export($this->artifact_xml, $this->changeset_xml, $this->artifact, $this->changeset_value);

        $this->assertEquals('attachments', (string) $this->changeset_xml->field_change['field_name']);
        $this->assertEquals('file', (string) $this->changeset_xml->field_change['type']);
        $this->assertCount(2, $this->changeset_xml->field_change->value);
        $this->assertEquals('fileinfo_190', $this->changeset_xml->field_change->value[0]['ref']);
        $this->assertEquals('fileinfo_191', $this->changeset_xml->field_change->value[1]['ref']);
    }
}
