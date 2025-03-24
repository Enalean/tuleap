<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
use Tracker_ArtifactFactory;
use Tracker_XML_ChildrenCollector;
use Tracker_XML_Updater_TemporaryFileXMLUpdater;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChildrenXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_XML_ChildrenCollector $collector;

    private ChildrenXMLExporter $exporter;

    private ArtifactXMLExporter $artifact_xml_exporter;

    private Tracker_XML_Updater_TemporaryFileXMLUpdater&MockObject $file_updater;

    private SimpleXMLElement $artifact_xml;

    private ChangesetXMLExporter&MockObject $changeset_exporter;

    private Tracker_ArtifactFactory&MockObject $artifact_factory;

    private int $artifact_id_1 = 123;

    private int $artifact_id_2 = 456;
    private Tracker_Artifact_Changeset $last_changeset_1;
    private Tracker_Artifact_Changeset $last_changeset_2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artifact_xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><artifacts />');

        $this->file_updater     = $this->createMock(Tracker_XML_Updater_TemporaryFileXMLUpdater::class);
        $this->artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);

        $tracker    = TrackerTestBuilder::aTracker()->withId(101)->build();
        $artifact_1 = ArtifactTestBuilder::anArtifact($this->artifact_id_1)->inTracker($tracker)->build();
        $artifact_2 = ArtifactTestBuilder::anArtifact($this->artifact_id_2)->inTracker($tracker)->build();

        $this->last_changeset_1 = new Tracker_Artifact_Changeset(
            1,
            $artifact_1,
            null,
            null,
            null
        );

        $this->last_changeset_2 = new Tracker_Artifact_Changeset(
            2,
            $artifact_2,
            null,
            null,
            null
        );

        $artifact_1->setChangesets([$this->last_changeset_1]);
        $artifact_2->setChangesets([$this->last_changeset_2]);

        $this->artifact_factory
            ->method('getArtifactById')
            ->willReturnCallback(fn(int $id) => match ($id) {
                $this->artifact_id_1 => $artifact_1,
                $this->artifact_id_2 => $artifact_2,
                default => null,
            });
        $this->collector = new Tracker_XML_ChildrenCollector();

        $this->changeset_exporter = $this->createMock(ChangesetXMLExporter::class);

        $file_info_xml_exporter = $this->createMock(FileInfoXMLExporter::class);
        $file_info_xml_exporter->method('export');

        $this->artifact_xml_exporter = new ArtifactXMLExporter(
            $this->changeset_exporter,
            $file_info_xml_exporter
        );

        $this->exporter = new ChildrenXMLExporter(
            $this->artifact_xml_exporter,
            $this->file_updater,
            $this->artifact_factory,
            $this->collector
        );
    }

    public function testDoesNothingIfCollectorIsEmpty(): void
    {
        $this->file_updater->expects($this->never())->method('update');
        $this->changeset_exporter->expects($this->never())->method('exportWithoutComments');

        $this->exporter->exportChildren($this->artifact_xml);
    }

    public function testExportsOneChild(): void
    {
        $this->collector->addChild($this->artifact_id_1, 'whatever');

        $this->file_updater->method('update')
            ->willReturnCallback(fn(SimpleXMLElement $xml_artifact) => match ((int) $xml_artifact['id']) {
                $this->artifact_id_1 => true,
            });
        $this->changeset_exporter->expects($this->once())->method('exportWithoutComments');

        $this->exporter->exportChildren($this->artifact_xml);
    }

    public function testExportsTwoChildren(): void
    {
        $this->collector->addChild($this->artifact_id_1, 'whatever');
        $this->collector->addChild($this->artifact_id_2, 'whatever');

        $this->file_updater->method('update')
            ->willReturnCallback(fn(SimpleXMLElement $xml_artifact) => match ((int) $xml_artifact['id']) {
                $this->artifact_id_1, $this->artifact_id_2 => true,
            });
        $this->changeset_exporter->expects($this->exactly(2))->method('exportWithoutComments');

        $this->exporter->exportChildren($this->artifact_xml);
    }

    public function testDoesNotFailWhenChildDoesNotExistAnymore(): void
    {
        $unknown_artifact_id = 666;
        $this->artifact_factory->method('getArtifactById')->with($unknown_artifact_id);
        $this->collector->addChild($unknown_artifact_id, 'whatever');

        $this->file_updater->expects($this->never())->method('update');
        $this->changeset_exporter->expects($this->never())->method('exportWithoutComments');

        $this->exporter->exportChildren($this->artifact_xml);
    }
}
