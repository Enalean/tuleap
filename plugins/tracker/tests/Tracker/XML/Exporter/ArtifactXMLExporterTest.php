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

require_once __DIR__.'/../../../bootstrap.php';

class Tracker_XML_Exporter_ArtifactXMLExporterTest extends TuleapTestCase
{

    /** @var Tracker_XML_Exporter_ArtifactXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $artifacts_xml;

    /** @var Tracker_XML_Exporter_ChangesetXMLExporter */
    private $changeset_exporter;

    /** @var Tracker_Artifact_Changeset */
    private $changeset;

    private $changeset_id = 66;

    public function setUp()
    {
        parent::setUp();
        $this->artifacts_xml      = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifacts />');
        $artifact                 = anArtifact()->withId(123)->withTrackerId(456)->build();
        $this->changeset          = stub('Tracker_Artifact_Changeset')->getId()->returns($this->changeset_id);
        $this->changeset_exporter = mock('Tracker_XML_Exporter_ChangesetXMLExporter');
        $this->exporter           = new Tracker_XML_Exporter_ArtifactXMLExporter($this->changeset_exporter);

        stub($this->changeset)->getArtifact()->returns($artifact);
    }

    public function itAppendsArtifactNodeToArtifactsNode()
    {
        $this->exporter->exportSnapshotWithoutComments($this->artifacts_xml, $this->changeset);

        $this->assertEqual(count($this->artifacts_xml->artifact), 1);
        $this->assertEqual($this->artifacts_xml->artifact['id'], 123);
        $this->assertEqual($this->artifacts_xml->artifact['tracker_id'], 456);
    }

    public function itDelegatesTheExportOfChangeset()
    {
        expect($this->changeset_exporter)->exportWithoutComments('*', $this->changeset)->once();

        $this->exporter->exportSnapshotWithoutComments($this->artifacts_xml, $this->changeset);
    }

    public function itExportsTheFullHistory()
    {
        $changeset_01 = mock('Tracker_Artifact_Changeset');
        $changeset_02 = mock('Tracker_Artifact_Changeset');
        $changeset_03 = mock('Tracker_Artifact_Changeset');

        $artifacts_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <tracker>
                                             <artifacts/>
                                             </tracker>');

        $changesets = array($changeset_01, $changeset_02, $changeset_03);
        $artifact   = anArtifact()->withId(101)->withChangesets($changesets)->build();

        $this->changeset_exporter->expectCallCount('exportFullHistory', 3);

        $this->exporter->exportFullHistory($artifacts_xml, $artifact);
    }
}
