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
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_XMLImporter_ChildrenXMLImporterTest extends TuleapTestCase {

    /** @var Tracker_Artifact_XMLImport */
    private $xml_importer;

    /** @var Tracker_XMLImporter_ChildrenXMLImporter */
    private $importer;

    /** @var Tracker_XMLImporter_ArtifactImportedMapping */
    private $artifacts_imported_mapping;

    /** @var Tracker_Artifact */
    private $created_artifact;

    public function setUp() {
        parent::setUp();
        $tracker_factory = mock('TrackerFactory');
        $this->xml_importer = mock('Tracker_Artifact_XMLImport');
        $this->importer = new Tracker_XMLImporter_ChildrenXMLImporter($this->xml_importer, $tracker_factory);

        $this->artifacts_imported_mapping = mock('Tracker_XMLImporter_ArtifactImportedMapping');

        $this->tracker = aTracker()->withId(23)->build();
        stub($tracker_factory)->getTrackerById(23)->returns($this->tracker);

        $this->created_artifact = anArtifact()->withId(1023)->build();
    }

    public function itImportsAllArtifactsExceptTheFirstOne() {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <artifacts>
                <artifact id="123" tracker_id="22"></artifact>
                <artifact id="456" tracker_id="23"></artifact>
            </artifacts>');

        expect($this->xml_importer)->importOneArtifactFromXML($this->tracker, $xml->artifact[1], '/extraction/path')->once();

        $this->importer->importChildren($this->artifacts_imported_mapping, $xml, '/extraction/path');
    }

    public function itRaisesExceptionIfNoTrackerId() {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <artifacts>
                <artifact id="123"></artifact>
                <artifact id="456"></artifact>
            </artifacts>');

        $this->expectException('Tracker_XMLImporter_TrackerIdNotDefinedException');

        $this->importer->importChildren($this->artifacts_imported_mapping, $xml, 'whatever');
    }

    public function itStacksMappingBetweenOriginalAndNewArtifact() {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <artifacts>
                <artifact id="100" tracker_id="22"></artifact>
                <artifact id="123" tracker_id="23"></artifact>
            </artifacts>');
        stub($this->xml_importer)->importOneArtifactFromXML()->returns($this->created_artifact);

        expect($this->artifacts_imported_mapping)->add(123, 1023)->once();

        $this->importer->importChildren($this->artifacts_imported_mapping, $xml, 'whatever');
    }

    public function itDoesNotStackMappingIfNoArtifact() {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <artifacts>
                <artifact id="100" tracker_id="22"></artifact>
                <artifact id="123" tracker_id="23"></artifact>
            </artifacts>');
        stub($this->xml_importer)->importOneArtifactFromXML()->returns(null);

        expect($this->artifacts_imported_mapping)->add()->never();

        $this->importer->importChildren($this->artifacts_imported_mapping, $xml, 'whatever');
    }
}