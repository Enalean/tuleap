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
 */

use Tuleap\Tracker\XML\Exporter\FileInfoXMLExporter;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_XML_Exporter_ChildrenXMLExporterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Tracker_XML_Exporter_ChildrenCollectorTest */
    private $collector;

    /** @var Tracker_XML_Exporter_ChildrenXMLExporter */
    private $exporter;

    /** @var Tracker_XML_Exporter_ArtifactXMLExporter */
    private $artifact_xml_exporter;

    /** @var Tracker_XML_Updater_TemporaryFileXMLUpdater */
    private $file_updater;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_XML_Exporter_ChangesetXMLExporter */
    private $changeset_exporter;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var int */
    private $artifact_id_1 = 123;

    /** @var int */
    private $artifact_id_2 = 456;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artifact_xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><artifacts />');

        $this->file_updater = Mockery::mock(Tracker_XML_Updater_TemporaryFileXMLUpdater::class);
        $this->artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);

        $artifact_1 = Mockery::mock(Tracker_Artifact::class)
            ->shouldReceive(['getId' => $this->artifact_id_1, 'getTrackerId' => 101])
            ->getMock();
        $artifact_2 = Mockery::mock(Tracker_Artifact::class)
            ->shouldReceive(['getId' => $this->artifact_id_2, 'getTrackerId' => 101])
            ->getMock();

        $this->last_changeset_1 = new Tracker_Artifact_Changeset(
            null,
            $artifact_1,
            null,
            null,
            null
        );

        $this->last_changeset_2 = new Tracker_Artifact_Changeset(
            null,
            $artifact_2,
            null,
            null,
            null
        );

        $artifact_1->shouldReceive('getLastChangeset')->andReturn($this->last_changeset_1);
        $artifact_2->shouldReceive('getLastChangeset')->andReturn($this->last_changeset_2);

        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with($this->artifact_id_1)
            ->andReturn($artifact_1);
        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with($this->artifact_id_2)
            ->andReturn($artifact_2);
        $this->collector = new Tracker_XML_ChildrenCollector();

        $this->changeset_exporter = Mockery::mock(Tracker_XML_Exporter_ChangesetXMLExporter::class);

        $file_info_xml_exporter = Mockery::mock(FileInfoXMLExporter::class)
            ->shouldReceive('export')
            ->getMock();

        $this->artifact_xml_exporter = new Tracker_XML_Exporter_ArtifactXMLExporter(
            $this->changeset_exporter,
            $file_info_xml_exporter
        );

        $this->exporter = new Tracker_XML_Exporter_ChildrenXMLExporter(
            $this->artifact_xml_exporter,
            $this->file_updater,
            $this->artifact_factory,
            $this->collector
        );
    }

    public function testDoesNothingIfCollectorIsEmpty(): void
    {
        $this->file_updater->shouldReceive('update')->never();
        $this->changeset_exporter->shouldReceive('exportWithoutComments')->never();

        $this->exporter->exportChildren($this->artifact_xml);
    }

    public function testExportsOneChild(): void
    {
        $this->collector->addChild($this->artifact_id_1, 'whatever');

        $this->file_updater->shouldReceive('update')
            ->withArgs(
                function (SimpleXMLElement $xml_artifact): bool {
                    return (int) $xml_artifact['id'] === $this->artifact_id_1;
                }
            )->once();
        $this->changeset_exporter->shouldReceive('exportWithoutComments')->once();

        $this->exporter->exportChildren($this->artifact_xml);
    }

    public function testExportsTwoChildren(): void
    {
        $this->collector->addChild($this->artifact_id_1, 'whatever');
        $this->collector->addChild($this->artifact_id_2, 'whatever');

        $this->file_updater->shouldReceive('update')
            ->withArgs(
                function (SimpleXMLElement $xml_artifact): bool {
                    return (int) $xml_artifact['id'] === $this->artifact_id_1;
                }
            )->once();
        $this->file_updater->shouldReceive('update')
            ->withArgs(
                function (SimpleXMLElement $xml_artifact): bool {
                    return (int) $xml_artifact['id'] === $this->artifact_id_2;
                }
            )->once();
        $this->changeset_exporter->shouldReceive('exportWithoutComments')->twice();

        $this->exporter->exportChildren($this->artifact_xml);
    }

    public function testDoesNotFailWhenChildDoesNotExistAnymore(): void
    {
        $unknown_artifact_id = 666;
        $this->artifact_factory->shouldReceive('getArtifactById')->with($unknown_artifact_id);
        $this->collector->addChild($unknown_artifact_id, 'whatever');

        $this->file_updater->shouldReceive('update')->never();
        $this->changeset_exporter->shouldReceive('exportWithoutComments')->never();

        $this->exporter->exportChildren($this->artifact_xml);
    }
}
