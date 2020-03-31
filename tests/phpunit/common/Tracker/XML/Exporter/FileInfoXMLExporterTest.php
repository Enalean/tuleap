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

namespace Tuleap\Tracker\XML\Exporter;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_Artifact;
use Tracker_FileInfo;
use Tracker_XML_Exporter_FilePathXMLExporter;
use Tracker_XML_Exporter_InArchiveFilePathXMLExporter;

class FileInfoXMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItDoesNotExportAnything()
    {
        $artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');

        $artifact = Mockery::mock(Tracker_Artifact::class)->shouldReceive(['getId' => 123])->getMock();

        $path_exporter = Mockery::mock(Tracker_XML_Exporter_FilePathXMLExporter::class);
        $exporter = new FileInfoXMLExporter($path_exporter);

        $exporter->export($artifact_xml, $artifact);
        $this->assertEmpty($artifact_xml->file);
    }

    public function testItExportsFileInfoForArtifact()
    {
        $artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');

        $artifact         = Mockery::mock(Tracker_Artifact::class)->shouldReceive(['getId' => 123])->getMock();
        \assert($artifact instanceof Tracker_Artifact);
        $another_artifact = Mockery::mock(Tracker_Artifact::class)->shouldReceive(['getId' => 124])->getMock();
        \assert($another_artifact instanceof Tracker_Artifact);

        $path_exporter = new Tracker_XML_Exporter_InArchiveFilePathXMLExporter();
        $exporter = new FileInfoXMLExporter($path_exporter);

        $exporter->add(
            $another_artifact,
            new Tracker_FileInfo(
                188,
                Mockery::mock(\Tracker_FormElement_Field_File::class),
                101,
                '',
                'avatar.png',
                "42",
                'image/png'
            )
        );
        $exporter->add(
            $artifact,
            new Tracker_FileInfo(
                190,
                Mockery::mock(\Tracker_FormElement_Field_File::class),
                101,
                '',
                'document.txt',
                "2",
                'text/plain'
            )
        );
        $exporter->add(
            $artifact,
            new Tracker_FileInfo(
                191,
                Mockery::mock(\Tracker_FormElement_Field_File::class),
                102,
                '',
                'landscape.png',
                "256",
                'image/png'
            )
        );

        $exporter->export($artifact_xml, $artifact);
        $this->assertCount(2, $artifact_xml->file);
        $this->assertEquals('fileinfo_190', (string) $artifact_xml->file[0]['id']);
        $this->assertEquals('document.txt', (string) $artifact_xml->file[0]->filename);
        $this->assertEquals('data/Artifact190', (string) $artifact_xml->file[0]->path);
        $this->assertEquals('fileinfo_191', (string) $artifact_xml->file[1]['id']);
        $this->assertEquals('landscape.png', (string) $artifact_xml->file[1]->filename);
        $this->assertEquals('data/Artifact191', (string) $artifact_xml->file[1]->path);
    }
}
