<?php
/*
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

namespace Tuleap\Tracker\Artifact\XML\Exporter;

use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ArtifactXMLExporter $exporter;

    private SimpleXMLElement $artifacts_xml;

    private ChangesetXMLExporter&MockObject $changeset_exporter;

    private Tracker_Artifact_Changeset $changeset;

    private FileInfoXMLExporter&MockObject $file_exporter;

    private Artifact $artifact;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->artifacts_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifacts />');

        $tracker        = TrackerTestBuilder::aTracker()->withId(456)->build();
        $this->artifact = ArtifactTestBuilder::anArtifact(123)->inTracker($tracker)->build();

        $this->changeset    = ChangesetTestBuilder::aChangeset(66)->ofArtifact($this->artifact)->build();
        $previous_changeset = ChangesetTestBuilder::aChangeset(67)->ofArtifact($this->artifact)->build();
        $this->artifact->setChangesets([$previous_changeset, $this->changeset]);

        $this->changeset_exporter = $this->createMock(ChangesetXMLExporter::class);
        $this->file_exporter      = $this->createMock(FileInfoXMLExporter::class);

        $this->exporter = new ArtifactXMLExporter(
            $this->changeset_exporter,
            $this->file_exporter
        );
    }

    public function testAppendsArtifactNodeToArtifactsNode(): void
    {
        $this->file_exporter->expects($this->once())->method('export');
        $this->changeset_exporter->expects($this->once())->method('exportWithoutComments');

        $this->exporter->exportSnapshotWithoutComments($this->artifacts_xml, $this->changeset);

        $this->assertCount(1, $this->artifacts_xml->artifact);
        $this->assertEquals(123, (int) $this->artifacts_xml->artifact['id']);
        $this->assertEquals(456, (int) $this->artifacts_xml->artifact['tracker_id']);
    }

    public function testExportsTheFullHistory(): void
    {
        $this->file_exporter->expects($this->once())->method('export');
        $this->changeset_exporter->expects($this->exactly(2))->method('exportFullHistory');

        $this->exporter->exportFullHistory($this->artifacts_xml, $this->artifact, []);
    }
}
