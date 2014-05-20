<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_XMLExporter_ArtifactXMLExporterTest extends TuleapTestCase {

    /** @var Tracker_XMLExporter_ArtifactXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $artifacts_xml;

    /** @var Tracker_XMLExporter_ChangesetXMLExporter */
    private $changeset_exporter;

    /** @var Tracker_Artifact_Changeset */
    private $changeset;

    private $changeset_id = 66;

    public function setUp() {
        parent::setUp();
        $this->artifacts_xml      = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifacts />');
        $artifact                 = anArtifact()->withId(123)->build();
        $this->changeset          = stub('Tracker_Artifact_Changeset')->getId()->returns($this->changeset_id);
        $this->changeset_exporter = mock('Tracker_XMLExporter_ChangesetXMLExporter');
        $this->exporter           = new Tracker_XMLExporter_ArtifactXMLExporter($this->changeset_exporter);

        stub($this->changeset)->getArtifact()->returns($artifact);
    }

    public function itAppendsArtifactNodeToArtifactsNode() {
        $this->exporter->exportSnapshotWithoutComments($this->artifacts_xml, $this->changeset);

        $this->assertEqual(count($this->artifacts_xml->artifact), 1);
        $this->assertEqual($this->artifacts_xml->artifact['id'], 123);
    }

    public function itDelegatesTheExportOfChangeset() {
        expect($this->changeset_exporter)->exportWithoutComments('*', $this->changeset)->once();

        $this->exporter->exportSnapshotWithoutComments($this->artifacts_xml, $this->changeset);
    }
}