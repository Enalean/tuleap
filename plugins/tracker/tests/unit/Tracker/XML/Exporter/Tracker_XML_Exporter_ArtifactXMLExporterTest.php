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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\XML\Exporter\FileInfoXMLExporter;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_XML_Exporter_ArtifactXMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Tracker_XML_Exporter_ArtifactXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $artifacts_xml;

    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_XML_Exporter_ChangesetXMLExporter */
    private $changeset_exporter;

    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset */
    private $changeset;

    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|FileInfoXMLExporter  */
    private $file_exporter;

    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact */
    private $artifact;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artifacts_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifacts />');

        $this->artifact = Mockery::mock(Tracker_Artifact::class)
            ->shouldReceive(['getId' => 123, 'getTrackerId' => 456])
            ->getMock();

        $this->changeset = Mockery::mock(Tracker_Artifact_Changeset::class)
            ->shouldReceive(['getId' => 66, 'getArtifact' => $this->artifact])
            ->getMock();

        $this->changeset_exporter = Mockery::mock(Tracker_XML_Exporter_ChangesetXMLExporter::class);
        $this->file_exporter      = Mockery::mock(FileInfoXMLExporter::class);

        $this->exporter = new Tracker_XML_Exporter_ArtifactXMLExporter(
            $this->changeset_exporter,
            $this->file_exporter
        );

        $previous_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->artifact
            ->shouldReceive('getChangesets')
            ->andReturn([
                $previous_changeset,
                $this->changeset
            ]);
    }

    public function testAppendsArtifactNodeToArtifactsNode(): void
    {
        $this->file_exporter->shouldReceive('export')->once();
        $this->changeset_exporter->shouldReceive('exportWithoutComments')->once();

        $this->exporter->exportSnapshotWithoutComments($this->artifacts_xml, $this->changeset);

        $this->assertCount(1, $this->artifacts_xml->artifact);
        $this->assertEquals(123, (int) $this->artifacts_xml->artifact['id']);
        $this->assertEquals(456, (int) $this->artifacts_xml->artifact['tracker_id']);
    }

    public function testExportsTheFullHistory(): void
    {
        $this->file_exporter->shouldReceive('export')->once();
        $this->changeset_exporter->shouldReceive('exportFullHistory')->twice();

        $this->exporter->exportFullHistory($this->artifacts_xml, $this->artifact);
    }
}
