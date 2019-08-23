<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once __DIR__.'/../../../bootstrap.php';

class Tracker_XML_Exporter_ChildrenXMLExporter_BaseTest extends TuleapTestCase
{

    /** @var Tracker_XML_Exporter_ChildrenCollectorTest */
    private $collector;

    /** @var Tracker_XML_Exporter_ChildrenXMLExporter */
    private $exporter;

    /** @var Tracker_XML_Exporter_ArtifactXMLExporter */
    private $artifact_xml_exporter;

    /** @var Tracker_XML_Updater_TemporaryFileXMLUpdater */
    private $file_updater;
    private $artifact_xml;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var int */
    private $artifact_id_1 = 123;

    /** @var int */
    private $artifact_id_2 = 456;

    /** @var int */
    private $unknown_artifact_id;

    public function setUp()
    {
        parent::setUp();
        $this->artifact_xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><artifacts />');

        $this->file_updater = \Mockery::spy(Tracker_XML_Updater_TemporaryFileXMLUpdater::class);
        $this->artifact_factory = mock('Tracker_ArtifactFactory');

        $artifact_1 = anArtifact()
            ->withId($this->artifact_id_1)
            ->build();
        $artifact_2 = anArtifact()
            ->withId($this->artifact_id_2)
            ->build();
        $this->last_changeset_1 = aChangeset()->withArtifact($artifact_1)->build();
        $this->last_changeset_2 = aChangeset()->withArtifact($artifact_2)->build();

        $artifact_1->setChangesets(array($this->last_changeset_1));
        $artifact_2->setChangesets(array($this->last_changeset_2));

        stub($this->artifact_factory)->getArtifactById($this->artifact_id_1)->returns($artifact_1);
        stub($this->artifact_factory)->getArtifactById($this->artifact_id_2)->returns($artifact_2);
        $this->collector = new Tracker_XML_ChildrenCollector();

        $this->artifact_xml_exporter = new Tracker_XML_Exporter_ArtifactXMLExporter(\Mockery::spy(Tracker_XML_Exporter_ChangesetXMLExporter::class));//new Tracker_XML_Exporter_ChildrenXMLExporterTest_ArtifactXMLExporter();

        $this->exporter = new Tracker_XML_Exporter_ChildrenXMLExporter(
            $this->artifact_xml_exporter,
            $this->file_updater,
            $this->artifact_factory,
            $this->collector
        );
    }

    public function itDoesNothingIfCollectorIsEmpty()
    {
        expect($this->file_updater)->update()->never();

        $this->exporter->exportChildren($this->artifact_xml);
    }

    public function itExportsOneChild()
    {
        $this->collector->addChild($this->artifact_id_1, 'whatever');

        expect($this->file_updater)->update(\Mockery::on(function (SimpleXMLElement $xml_artifact) {
            return (int)$xml_artifact['id'] == $this->artifact_id_1;
        }))->once();

        $this->exporter->exportChildren($this->artifact_xml);
    }

    public function itExportsTwoChildren()
    {
        $this->collector->addChild($this->artifact_id_1, 'whatever');
        $this->collector->addChild($this->artifact_id_2, 'whatever');

        expect($this->file_updater)->update(\Mockery::on(function (SimpleXMLElement $xml_artifact) {
            return (int)$xml_artifact['id'] == $this->artifact_id_1;
        }))->once();
        expect($this->file_updater)->update(\Mockery::on(function (SimpleXMLElement $xml_artifact) {
            return (int)$xml_artifact['id'] == $this->artifact_id_2;
        }))->once();

        $this->exporter->exportChildren($this->artifact_xml);
    }

    public function itDoesNotFailWhenChildDoesNotExistAnymore()
    {
        $this->unknown_artifact_id = 666;
        $this->collector->addChild($this->unknown_artifact_id, 'whatever');

        expect($this->file_updater)->update()->never();

        $this->exporter->exportChildren($this->artifact_xml);
    }
}
